import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../models/lot_model.dart';
import '../../services/api_exception.dart';
import '../../services/lot_service.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import '../../widgets/verification_labels.dart';
import 'lot_evidence_screen.dart';
import 'widgets/quota_limit_sheet.dart';

/// Create or edit a lot: Details, Evidence, Price, Review. "Save draft" works at every step.
/// The lot belongs to the active business; publishing needs Tier 1, checked by the server.
class CreateLotScreen extends StatefulWidget {
  const CreateLotScreen({super.key, this.lotId, this.service, this.takePhoto});

  final int? lotId;
  final LotService? service;
  final TakeLotPhoto? takePhoto;

  @override
  State<CreateLotScreen> createState() => _CreateLotScreenState();
}

class _CreateLotScreenState extends State<CreateLotScreen> {
  static const _processes = ['washed', 'natural', 'honey'];
  static const _grades = ['grade_1', 'grade_2', 'grade_3', 'grade_4', 'grade_5', 'undergrade'];
  static const _evidenceValidDays = 30;

  late final LotService _service = widget.service ?? LotService();
  final _detailsKey = GlobalKey<FormState>();
  final _priceKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _region = TextEditingController();
  final _cropYear = TextEditingController(text: '2025/2026');
  final _quantity = TextEditingController();
  final _price = TextEditingController();
  final _minOrder = TextEditingController(text: '10');
  String _process = 'washed';
  String _grade = 'grade_1';

  SellerLot? _lot;
  int _step = 0;
  bool _saving = false;
  bool _loading = false;
  Object? _loadError;

  @override
  void initState() {
    super.initState();
    if (widget.lotId != null) _loadLot();
  }

  @override
  void dispose() {
    for (final c in [_name, _region, _cropYear, _quantity, _price, _minOrder]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _loadLot() async {
    setState(() {
      _loading = true;
      _loadError = null;
    });
    try {
      final lot = await _service.getLot(widget.lotId!);
      if (!mounted) return;
      _apply(lot);
      setState(() => _loading = false);
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loadError = e;
        _loading = false;
      });
    }
  }

  void _apply(SellerLot lot) {
    _lot = lot;
    _name.text = lot.title;
    _region.text = lot.originRegion;
    if (lot.cropYear != null) _cropYear.text = lot.cropYear!;
    if (lot.totalQuantityBags != null && lot.totalQuantityBags! > 0) _quantity.text = '${lot.totalQuantityBags}';
    if (lot.pricePerLb != null) _price.text = lot.pricePerLb!.toStringAsFixed(2);
    if (lot.minimumOrderBags != null) _minOrder.text = '${lot.minimumOrderBags}';
    if (_processes.contains(lot.processingMethod)) _process = lot.processingMethod!;
    if (_grades.contains(lot.grade)) _grade = lot.grade!;
  }

  Map<String, dynamic> _fields() => {
    'title': _name.text.trim(),
    'origin_region': _region.text.trim(),
    'crop_year': _cropYear.text.trim(),
    'processing_method': _process,
    'grade': _grade,
    if (int.tryParse(_quantity.text.trim()) != null) 'total_quantity_bags': int.parse(_quantity.text.trim()),
    if (double.tryParse(_price.text.trim()) != null) 'indicative_price_usd_per_lb': double.parse(_price.text.trim()),
    if (int.tryParse(_minOrder.text.trim()) != null) 'minimum_order_bags': int.parse(_minOrder.text.trim()),
  };

  /// Creates the draft the first time, then updates it. Returns false if it could not save.
  Future<bool> _save({bool announce = false}) async {
    if (_name.text.trim().isEmpty || _region.text.trim().isEmpty) {
      setState(() => _step = 0);
      _detailsKey.currentState?.validate();
      return false;
    }
    setState(() => _saving = true);
    try {
      final lot = _lot == null ? await _service.createDraft(_fields()) : await _service.update(_lot!.id, _fields());
      if (!mounted) return false;
      setState(() => _lot = lot);
      if (announce) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(context.l10n.createLotDraftSaved)));
      }
      return true;
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
      return false;
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _next() async {
    if (_step == 0 && !(_detailsKey.currentState?.validate() ?? false)) return;
    if (_step == 2 && !(_priceKey.currentState?.validate() ?? false)) return;
    if (_step != 1 && !await _save()) return;
    if (!mounted) return;
    setState(() => _step = (_step + 1).clamp(0, 3));
  }

  Future<void> _publish() async {
    if (!await _save()) return;
    if (!mounted) return;
    final l10n = context.l10n;
    setState(() => _saving = true);
    try {
      await _service.publish(_lot!.id);
      if (!mounted) return;
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(content: Text(l10n.createLotPublished)));
      Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      if (!mounted) return;
      if (e.code == 'VERIFICATION_REQUIRED') {
        ScaffoldMessenger.of(context)
          ..hideCurrentSnackBar()
          ..showSnackBar(SnackBar(content: Text(l10n.createLotNeedsTier)));
        Navigator.of(context).pop(true);
      } else if (e.code == 'limit_reached') {
        QuotaLimitSheet.show(context, {'feature': 'active_lots', 'message': e.serverMessage ?? ''});
      } else {
        showFiraActionError(context, e);
      }
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _pause() async {
    try {
      final lot = await _service.pause(_lot!.id);
      if (mounted) setState(() => _lot = lot);
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    }
  }

  Future<void> _delete() async {
    final l10n = context.l10n;
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        content: Text(l10n.createLotDeleteConfirm),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: Text(l10n.cancel)),
          TextButton(onPressed: () => Navigator.pop(ctx, true), child: Text(l10n.delete)),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    try {
      await _service.delete(_lot!.id);
      if (mounted) Navigator.of(context).pop(true);
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    }
  }

  String _processLabel(AppLocalizations l10n, String p) => switch (p) {
    'natural' => l10n.processNatural,
    'honey' => l10n.processHoney,
    _ => l10n.processWashed,
  };

  String _gradeLabel(AppLocalizations l10n, String g) => g == 'undergrade' ? l10n.gradeUnder : l10n.translate('gradeNumber', {'n': g.replaceAll('grade_', '')});

  String? _required(String? v) => (v == null || v.trim().isEmpty) ? context.l10n.captureRequired : null;

  String? _positiveInt(String? v) => (int.tryParse(v?.trim() ?? '') ?? 0) > 0 ? null : context.l10n.captureRequired;

  Widget _details(AppLocalizations l10n) {
    return Form(
      key: _detailsKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          TextFormField(
            key: const Key('lot-name'),
            controller: _name,
            maxLength: 255,
            decoration: InputDecoration(labelText: l10n.createLotName),
            validator: _required,
          ),
          TextFormField(
            key: const Key('lot-region'),
            controller: _region,
            maxLength: 100,
            decoration: InputDecoration(labelText: l10n.createLotRegion),
            validator: _required,
          ),
          TextFormField(
            controller: _cropYear,
            maxLength: 20,
            decoration: InputDecoration(labelText: l10n.createLotCropYear),
            validator: _required,
          ),
          const SizedBox(height: 8),
          Text(l10n.createLotProcess, style: const TextStyle(fontWeight: FontWeight.w600)),
          const SizedBox(height: 6),
          Wrap(
            spacing: 8,
            children: [
              for (final p in _processes)
                ChoiceChip(label: Text(_processLabel(l10n, p)), selected: _process == p, onSelected: (_) => setState(() => _process = p)),
            ],
          ),
          const SizedBox(height: 12),
          DropdownButtonFormField<String>(
            initialValue: _grade,
            decoration: InputDecoration(labelText: l10n.createLotGrade),
            items: [for (final g in _grades) DropdownMenuItem(value: g, child: Text(_gradeLabel(l10n, g)))],
            onChanged: (g) => setState(() => _grade = g ?? _grade),
          ),
          const SizedBox(height: 12),
          TextFormField(
            key: const Key('lot-quantity'),
            controller: _quantity,
            keyboardType: TextInputType.number,
            decoration: InputDecoration(labelText: l10n.createLotQuantity),
            validator: _positiveInt,
          ),
        ],
      ),
    );
  }

  Widget _priceStep(AppLocalizations l10n) {
    return Form(
      key: _priceKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          TextFormField(
            key: const Key('lot-price'),
            controller: _price,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            decoration: InputDecoration(labelText: l10n.createLotPricePerLb),
            validator: (v) => (double.tryParse(v?.trim() ?? '') ?? 0) >= 0.5 ? null : l10n.captureRequired,
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: _minOrder,
            keyboardType: TextInputType.number,
            decoration: InputDecoration(labelText: l10n.createLotMinOrder),
            validator: _positiveInt,
          ),
          const SizedBox(height: 12),
          Text(l10n.translate('createLotValidFor', {'days': _evidenceValidDays}), style: TextStyle(color: context.fira.mutedText)),
        ],
      ),
    );
  }

  Widget _review(AppLocalizations l10n) {
    final lot = _lot;
    final rows = <(String, String)>[
      (l10n.createLotName, _name.text),
      (l10n.createLotRegion, _region.text),
      (l10n.createLotCropYear, _cropYear.text),
      (l10n.createLotProcess, _processLabel(l10n, _process)),
      (l10n.createLotGrade, _gradeLabel(l10n, _grade)),
      (l10n.createLotQuantity, _quantity.text),
      (l10n.createLotPricePerLb, _price.text),
      (l10n.createLotMinOrder, _minOrder.text),
    ];
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (lot != null)
          Row(
            children: [
              LotLevelChip(level: lot.evidenceLevel),
              const SizedBox(width: 8),
              Text(l10n.translate('createLotCode', {'code': lot.lotCode}), style: TextStyle(color: context.fira.mutedText)),
            ],
          ),
        const SizedBox(height: 8),
        for (final (label, value) in rows) ListTile(contentPadding: EdgeInsets.zero, dense: true, title: Text(label), trailing: Text(value)),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final editing = widget.lotId != null;

    if (_loading)
      return Scaffold(
        appBar: AppBar(),
        body: const Center(child: CircularProgressIndicator()),
      );
    if (_loadError != null)
      return Scaffold(
        appBar: AppBar(),
        body: FiraErrorState(error: _loadError, onRetry: _loadLot),
      );

    final steps = [l10n.createLotStepDetails, l10n.createLotStepEvidence, l10n.createLotStepPrice, l10n.createLotStepReview];
    final Widget body = switch (_step) {
      0 => _details(l10n),
      1 =>
        _lot == null
            ? Text(l10n.createLotSaveFirst)
            : Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text(l10n.evidenceWithThreePhotos, style: TextStyle(color: context.fira.mutedText)),
                  const SizedBox(height: 8),
                  LotEvidencePanel(lotId: _lot!.id, service: _service, takePhoto: widget.takePhoto, onChanged: (lot) => _lot = lot),
                ],
              ),
      2 => _priceStep(l10n),
      _ => _review(l10n),
    };

    return Scaffold(
      appBar: AppBar(
        title: Text(editing ? l10n.createLotEditTitle : l10n.createLotTitle),
        actions: [
          TextButton(key: const Key('save-draft'), onPressed: _saving ? null : () => _save(announce: true), child: Text(l10n.createLotSaveDraft)),
          if (_lot != null)
            PopupMenuButton<String>(
              onSelected: (v) => v == 'pause' ? _pause() : _delete(),
              itemBuilder: (_) => [
                if (_lot!.status == 'active') PopupMenuItem(value: 'pause', child: Text(l10n.createLotPause)),
                PopupMenuItem(value: 'delete', child: Text(l10n.createLotDelete)),
              ],
            ),
        ],
      ),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
          children: [
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                for (var i = 0; i < steps.length; i++)
                  ChoiceChip(
                    key: Key('step-$i'),
                    label: Text('${i + 1} · ${steps[i]}'),
                    selected: _step == i,
                    onSelected: _saving || (i > 0 && _lot == null) ? null : (_) => setState(() => _step = i),
                  ),
              ],
            ),
            const SizedBox(height: 16),
            body,
            const SizedBox(height: 24),
            if (_step < 3)
              FilledButton(
                key: const Key('lot-next'),
                onPressed: _saving ? null : _next,
                child: Text(_step == 2 ? l10n.createLotContinueReview : l10n.createLotNext),
              )
            else
              FilledButton(key: const Key('lot-publish'), onPressed: _saving || _lot?.status == 'active' ? null : _publish, child: Text(l10n.createLotPublish)),
          ],
        ),
      ),
    );
  }
}
