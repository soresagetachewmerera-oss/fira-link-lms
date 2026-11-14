import 'dart:io' show File;

import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:image_picker/image_picker.dart';

import '../../l10n/app_localizations.dart';
import '../../models/lot_model.dart';
import '../../models/verification_model.dart';
import '../../services/lot_service.dart';
import '../../services/upload_file.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import '../../widgets/verification_labels.dart';
import 'document_capture_screen.dart';

/// A photo taken in the app, with the time and (if allowed) the place.
class CapturedLotPhoto {
  const CapturedLotPhoto({required this.file, required this.capturedAt, this.latitude, this.longitude, this.accuracyMeters});

  final UploadFile file;
  final DateTime capturedAt;
  final double? latitude;
  final double? longitude;
  final int? accuracyMeters;

  bool get hasLocation => latitude != null && longitude != null;
}

typedef TakeLotPhoto = Future<CapturedLotPhoto?> Function();

/// Camera only (never the gallery), full quality so the EXIF capture time is kept.
/// The temporary file is deleted as soon as its bytes are read.
Future<CapturedLotPhoto?> takeLotPhotoWithCamera() async {
  final shot = await ImagePicker().pickImage(source: ImageSource.camera, requestFullMetadata: true);
  if (shot == null) return null;
  final capturedAt = DateTime.now();
  final bytes = await shot.readAsBytes();
  if (!kIsWeb) {
    try {
      await File(shot.path).delete();
    } catch (_) {
      // Already gone.
    }
  }

  Position? position;
  try {
    if (await Geolocator.isLocationServiceEnabled()) {
      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.whileInUse || permission == LocationPermission.always) {
        position = await Geolocator.getCurrentPosition(
          locationSettings: const LocationSettings(accuracy: LocationAccuracy.high, timeLimit: Duration(seconds: 15)),
        );
      }
    }
  } catch (_) {
    position = null; // Sent without a place; the reviewer decides.
  }

  return CapturedLotPhoto(
    file: UploadFile(name: shot.name.isEmpty ? 'lot-photo.jpg' : shot.name, bytes: bytes),
    capturedAt: capturedAt,
    latitude: position?.latitude,
    longitude: position?.longitude,
    accuracyMeters: position?.accuracy.round(),
  );
}

/// S-111 Lot evidence level, as a full screen.
class LotEvidenceScreen extends StatelessWidget {
  const LotEvidenceScreen({super.key, required this.lotId, this.service, this.takePhoto});

  final int lotId;
  final LotService? service;
  final TakeLotPhoto? takePhoto;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(context.l10n.evidenceTitle)),
      body: LotEvidencePanel(lotId: lotId, service: service, takePhoto: takePhoto, scrollable: true),
    );
  }
}

/// The levels, the photo checklist and the lab report for one lot. Every state comes from the
/// server; the seller can only add or withdraw evidence, never pick a level.
class LotEvidencePanel extends StatefulWidget {
  const LotEvidencePanel({super.key, required this.lotId, this.service, this.takePhoto, this.scrollable = false, this.onChanged});

  final int lotId;
  final LotService? service;
  final TakeLotPhoto? takePhoto;
  final bool scrollable;
  final ValueChanged<SellerLot>? onChanged;

  @override
  State<LotEvidencePanel> createState() => _LotEvidencePanelState();
}

class _LotEvidencePanelState extends State<LotEvidencePanel> {
  late final LotService _service = widget.service ?? LotService();
  SellerLot? _lot;
  Object? _error;
  bool _loading = true;
  String? _busyKind;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = _lot == null;
      _error = null;
    });
    try {
      final lot = await _service.getLot(widget.lotId);
      if (!mounted) return;
      setState(() {
        _lot = lot;
        _loading = false;
      });
      widget.onChanged?.call(lot);
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e;
        _loading = false;
      });
    }
  }

  Future<void> _photo(LotEvidenceItem item) async {
    final l10n = context.l10n;
    setState(() => _busyKind = item.kind);
    try {
      final shot = await (widget.takePhoto ?? takeLotPhotoWithCamera)();
      if (shot == null || !mounted) return;
      final rejection = shot.file.localRejection;
      if (rejection != null) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(FiraErrorText.fileRejection(context, rejection))));
        return;
      }
      await _service.uploadPhoto(
        lotId: widget.lotId,
        kind: item.kind,
        photo: shot.file,
        capturedAt: shot.capturedAt,
        latitude: shot.latitude,
        longitude: shot.longitude,
        accuracyMeters: shot.accuracyMeters,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(shot.hasLocation ? l10n.evidenceSent : '${l10n.evidenceSent} ${l10n.evidenceLocationOff}')),
      );
      await _load();
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    } finally {
      if (mounted) setState(() => _busyKind = null);
    }
  }

  Future<void> _labReport() async {
    final doc = DossierDocument(
      type: 'lab_report',
      tier: 0,
      state: 'not_uploaded',
      fields: const ['lab_name'],
    );
    final sent = await Navigator.of(context).push<bool>(MaterialPageRoute(
      builder: (_) => DocumentCaptureScreen(
        document: doc,
        expectedReviewDays: 3,
        upload: ({required pages, documentNumber, issuedAt, expiresAt, details = const {}}) => _service.uploadLabReport(
          lotId: widget.lotId,
          pages: pages,
          labName: details['lab_name'] ?? '',
          issuedAt: issuedAt,
        ),
      ),
    ));
    if (sent == true) _load();
  }

  Future<void> _withdraw(LotEvidenceItem item) async {
    try {
      await _service.withdrawEvidence(widget.lotId, item.evidenceId!);
      await _load();
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    }
  }

  String? _subtitle(BuildContext context, SellerLot lot, LotEvidenceItem item) {
    final l10n = context.l10n;
    String date(DateTime? d) => VerificationLabels.date(context, d);
    switch (item.state) {
      case 'approved':
        return item.expiresAt != null ? l10n.translate('verifyDocValidUntil', {'date': date(item.expiresAt)}) : null;
      case 'in_review':
        final s = l10n.translate('verifyDocSubmitted', {'date': date(item.submittedAt)});
        return item.approvedCopyCounts ? '$s\n${l10n.verifyDocOldCopyCounts}' : s;
      case 'needs_changes':
      case 'rejected':
        return VerificationLabels.reason(l10n, item.reasonCode, item.reason);
      case 'expired':
        return l10n.translate('verifyDocExpiredOn', {'date': date(item.expiresAt)});
      default:
        return item.kind == 'photo_bags' ? l10n.translate('evidenceShowCode', {'code': lot.lotCode}) : null;
    }
  }

  Widget _itemTile(BuildContext context, SellerLot lot, LotEvidenceItem item) {
    final l10n = context.l10n;
    final busy = _busyKind == item.kind;
    final subtitle = _subtitle(context, lot, item);
    final action = item.state == 'in_review'
        ? TextButton(onPressed: () => _withdraw(item), child: Text(l10n.evidenceWithdraw))
        : FilledButton.tonal(
            key: Key('evidence-action-${item.kind}'),
            onPressed: busy || _busyKind != null ? null : () => item.isPhoto ? _photo(item) : _labReport(),
            child: busy
                ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                : Text(item.isPhoto
                    ? (item.state == 'not_uploaded' ? l10n.evidenceTakePhoto : l10n.evidenceRetake)
                    : l10n.evidenceUploadReport),
          );

    return Card(
      key: Key('evidence-${item.kind}'),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 12, 12, 12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(child: Text(VerificationLabels.lotKind(l10n, item.kind), style: const TextStyle(fontWeight: FontWeight.w600))),
                DocumentStatePill(state: item.state),
              ],
            ),
            if (subtitle != null) ...[
              const SizedBox(height: 4),
              Text(subtitle, style: TextStyle(color: context.fira.mutedText)),
            ],
            Align(alignment: Alignment.centerRight, child: action),
          ],
        ),
      ),
    );
  }

  Widget _level(BuildContext context, String level, String body, bool current, {List<Widget> children = const []}) {
    final fira = context.fira;
    return Card(
      key: Key('level-$level'),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(22),
        side: current ? BorderSide(color: fira.brandPrimary, width: 2) : BorderSide.none,
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(children: [LotLevelChip(level: level)]),
            const SizedBox(height: 8),
            Text(body),
            ...children,
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Center(child: Padding(padding: EdgeInsets.all(32), child: CircularProgressIndicator()));
    if (_error != null) return FiraErrorState(error: _error, onRetry: _load);

    final l10n = context.l10n;
    final fira = context.fira;
    final lot = _lot!;
    final photos = lot.evidence.where((e) => e.isPhoto).toList();
    final lab = lot.evidence.where((e) => !e.isPhoto).toList();

    final children = <Widget>[
      Text('${lot.title} · ${l10n.translate('createLotCode', {'code': lot.lotCode})}', style: TextStyle(color: fira.mutedText)),
      const SizedBox(height: 8),
      Text(l10n.evidenceIntro),
      const SizedBox(height: 4),
      Text(l10n.translate('evidenceCurrentLevel', {'level': VerificationLabels.level(l10n, lot.evidenceLevel)}),
          key: const Key('current-level'), style: const TextStyle(fontWeight: FontWeight.w700)),
      const SizedBox(height: 12),
      _level(context, 'claimed', l10n.evidenceClaimedBody, lot.evidenceLevel == 'claimed'),
      _level(context, 'documented', l10n.evidenceDocumentedBody, lot.evidenceLevel == 'documented', children: [
        const SizedBox(height: 12),
        Text(l10n.evidencePhotosNeeded, style: const TextStyle(fontWeight: FontWeight.w700)),
        const SizedBox(height: 4),
        Text(l10n.evidenceInAppOnly, style: TextStyle(color: fira.mutedText, fontSize: 13)),
        for (final item in photos) _itemTile(context, lot, item),
      ]),
      _level(context, 'lab_backed', l10n.evidenceLabBody, lot.evidenceLevel == 'lab_backed', children: [
        for (final item in lab) _itemTile(context, lot, item),
      ]),
      _level(context, 'inspected', l10n.evidenceInspectedBody, lot.evidenceLevel == 'inspected'),
      if (lot.evidenceValidUntil != null) ...[
        const SizedBox(height: 8),
        Text(l10n.translate('evidenceExpiresOn', {'date': VerificationLabels.date(context, lot.evidenceValidUntil)}),
            style: TextStyle(color: fira.pending)),
      ],
    ];

    if (widget.scrollable) {
      return RefreshIndicator(
        onRefresh: _load,
        child: ListView(padding: const EdgeInsets.fromLTRB(20, 16, 20, 32), children: children),
      );
    }

    return Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: children);
  }
}
