import 'dart:io' show File;

import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../../l10n/app_localizations.dart';
import '../../models/sample_model.dart';
import '../../services/api_exception.dart';
import '../../services/sample_service.dart';
import '../../services/upload_file.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import 'sample_tracking_screen.dart';

typedef TakeSealPhoto = Future<UploadFile?> Function();

/// Camera photo of a seal; the temporary file is deleted once its bytes are read.
Future<UploadFile?> takeSealPhotoWithCamera() async {
  final shot = await ImagePicker().pickImage(source: ImageSource.camera, imageQuality: 90);
  if (shot == null) return null;
  final bytes = await shot.readAsBytes();
  if (!kIsWeb) {
    try {
      await File(shot.path).delete();
    } catch (_) {
      // Already gone.
    }
  }
  return UploadFile(name: shot.name.isEmpty ? 'seal.jpg' : shot.name, bytes: bytes);
}

/// Seal and ship (seller): prepare, seal with a tamper-evident seal, photograph the sealed sample
/// and the retained copy, hand to the courier on the buyer's account and add the tracking number.
/// The buyer's delivery contact is loaded only here (the server logs every view).
class ShipSampleScreen extends StatefulWidget {
  const ShipSampleScreen({super.key, required this.sampleId, this.service, this.takePhoto});

  final int sampleId;
  final SampleService? service;
  final TakeSealPhoto? takePhoto;

  @override
  State<ShipSampleScreen> createState() => _ShipSampleScreenState();
}

class _ShipSampleScreenState extends State<ShipSampleScreen> {
  late final SampleService _service = widget.service ?? SampleService();
  final _formKey = GlobalKey<FormState>();
  final _seal = TextEditingController();
  final _tracking = TextEditingController();
  SampleView? _s;
  Object? _error;
  UploadFile? _sealed;
  UploadFile? _retained;
  bool _tried = false;
  bool _sending = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _seal.dispose();
    _tracking.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() => _error = null);
    try {
      final s = await _service.get(widget.sampleId, withShipping: true);
      if (mounted) setState(() => _s = s);
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  Future<void> _photo(bool sealed) async {
    final file = await (widget.takePhoto ?? takeSealPhotoWithCamera)();
    if (file == null || !mounted) return;
    final rejection = file.localRejection;
    if (rejection != null) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(FiraErrorText.fileRejection(context, rejection))));
      return;
    }
    setState(() => sealed ? _sealed = file : _retained = file);
  }

  Future<void> _send() async {
    setState(() => _tried = true);
    final ok = _formKey.currentState?.validate() ?? false;
    if (!ok || _sealed == null || _retained == null) return;
    setState(() => _sending = true);
    try {
      await _service.dispatch(widget.sampleId, sealId: _seal.text.trim(), sealedPhoto: _sealed!, retainedPhoto: _retained!, trackingNumber: _tracking.text.trim());
      if (mounted) Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      if (mounted) showFiraActionError(context, e);
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  Widget _photoSlot(BuildContext context, String label, UploadFile? file, bool sealed) {
    final fira = context.fira;
    return Expanded(
      child: Card(
        key: Key(sealed ? 'photo-sealed' : 'photo-retained'),
        child: InkWell(
          onTap: _sending ? null : () => _photo(sealed),
          child: SizedBox(
            height: 120,
            child: file == null
                ? Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                    Icon(Icons.photo_camera_outlined, color: fira.brandPrimary),
                    const SizedBox(height: 6),
                    Text(label, textAlign: TextAlign.center),
                  ])
                : Stack(fit: StackFit.expand, children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(22),
                      child: Image.memory(file.bytes, fit: BoxFit.cover, errorBuilder: (_, _, _) => Icon(Icons.check_circle, color: fira.success, size: 40)),
                    ),
                    Align(
                      alignment: Alignment.bottomCenter,
                      child: Container(
                        width: double.infinity,
                        color: fira.overlayScrim,
                        padding: const EdgeInsets.all(4),
                        child: Text(label, textAlign: TextAlign.center, style: TextStyle(color: fira.card)),
                      ),
                    ),
                  ]),
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    return Scaffold(
      appBar: AppBar(title: Text(l10n.sealAndShip)),
      body: _error != null
          ? FiraErrorState(error: _error, onRetry: _load)
          : _s == null
              ? const Center(child: CircularProgressIndicator())
              : _body(context, _s!),
    );
  }

  Widget _body(BuildContext context, SampleView s) {
    final l10n = context.l10n;
    final fira = context.fira;
    final ship = s.shipping;
    final code = s.lot?.lotCode ?? '';
    final steps = [
      l10n.translate('shipStepPrepare', {'grams': s.grams, 'code': code}),
      l10n.shipStepSeal,
      l10n.shipStepRetained,
      l10n.shipStepCourier,
    ];

    return Form(
      key: _formKey,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
        children: [
          Card(
            child: ListTile(
              title: Text(s.buyer?.name ?? '', style: const TextStyle(fontWeight: FontWeight.w700)),
              subtitle: Text('${s.lot?.title ?? ''} · ${l10n.translate('sampleGrams', {'n': s.grams})} · $code'),
            ),
          ),
          for (var i = 0; i < steps.length; i++)
            ListTile(
              dense: true,
              contentPadding: EdgeInsets.zero,
              leading: CircleAvatar(radius: 12, backgroundColor: fira.brandTint, child: Text('${i + 1}', style: TextStyle(color: fira.brandPrimary, fontSize: 12))),
              title: Text(steps[i]),
            ),
          const SizedBox(height: 8),
          TextFormField(
            key: const Key('seal-id'),
            controller: _seal,
            textCapitalization: TextCapitalization.characters,
            maxLength: 40,
            decoration: InputDecoration(labelText: l10n.sealId),
            validator: (v) => RegExp(r'^[A-Za-z0-9][A-Za-z0-9 -]{3,39}$').hasMatch(v?.trim() ?? '') ? null : l10n.captureRequired,
          ),
          Row(children: [
            _photoSlot(context, l10n.sealedSamplePhoto, _sealed, true),
            const SizedBox(width: 12),
            _photoSlot(context, l10n.retainedCopyPhoto, _retained, false),
          ]),
          if (_tried && (_sealed == null || _retained == null))
            Text(l10n.photosRequired, key: const Key('photos-required'), style: TextStyle(color: fira.danger)),
          const SizedBox(height: 12),
          if (ship != null)
            Card(
              key: const Key('ship-to'),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(l10n.shipTo, style: const TextStyle(fontWeight: FontWeight.w700)),
                    SelectableText([ship.company, ship.address, ship.country, ship.phone].whereType<String>().where((x) => x.isNotEmpty).join('\n')),
                    const SizedBox(height: 8),
                    Text(l10n.translate('shipOnBuyerAccount', {'courier': (ship.courier ?? '').toUpperCase(), 'account': ship.courierAccount ?? ''}),
                        style: const TextStyle(fontWeight: FontWeight.w600)),
                  ],
                ),
              ),
            ),
          TextFormField(
            key: const Key('tracking-number'),
            controller: _tracking,
            maxLength: 60,
            decoration: InputDecoration(labelText: l10n.trackingNumber),
            validator: (v) => (v == null || v.trim().isEmpty) ? l10n.captureRequired : null,
          ),
          const SizedBox(height: 8),
          FilledButton(
            key: const Key('mark-handed'),
            onPressed: _sending ? null : _send,
            child: _sending ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2)) : Text(l10n.markHandedToCourier),
          ),
          TextButton(
            onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => SampleTrackingScreen(sampleId: s.id, service: _service))),
            child: Text(l10n.seeWhatBuyerSees),
          ),
          Card(
            color: fira.brandTint,
            child: ListTile(
              leading: Icon(Icons.inventory_2_outlined, color: fira.brandPrimary),
              title: Text(l10n.retainedProtects, style: const TextStyle(fontWeight: FontWeight.w700)),
              subtitle: Text(l10n.retainedProtectsBody),
            ),
          ),
        ],
      ),
    );
  }
}
