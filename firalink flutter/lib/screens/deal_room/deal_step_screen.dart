import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../models/deal_room_model.dart';
import '../../services/deal_room_service.dart';
import '../../services/upload_file.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import '../../widgets/trade_labels.dart';

typedef PickProof = Future<UploadFile?> Function();

/// A deal room step that needs proof but moves no money: pre-shipment inspection and shipped
/// (seller), delivered and confirmed (buyer). Proof moves the step to "Submitted"; it counts as done
/// only after Fira Link confirms it. When the last step is done, either side can close the deal.
class DealStepScreen extends StatefulWidget {
  const DealStepScreen({super.key, required this.dealRoom, required this.milestone, this.service, this.pickProof});

  final DealRoomModel dealRoom;
  final DealMilestoneModel milestone;
  final DealRoomService? service;
  final PickProof? pickProof;

  @override
  State<DealStepScreen> createState() => _DealStepScreenState();
}

class _DealStepScreenState extends State<DealStepScreen> {
  late final DealRoomService _service = widget.service ?? DealRoomService();
  late DealRoomModel _deal = widget.dealRoom;
  final _first = TextEditingController();
  final _second = TextEditingController();
  UploadFile? _file;
  bool _tried = false;
  bool _sending = false;

  String get _key => widget.milestone.key;
  DealMilestoneModel get _step => _deal.step(_key) ?? widget.milestone;

  @override
  void dispose() {
    _first.dispose();
    _second.dispose();
    super.dispose();
  }

  Future<void> _reload() async {
    try {
      final d = await _service.getDealRoom(_deal.id);
      if (mounted) setState(() => _deal = d);
    } catch (_) {
      // Keep what we have; the hub reloads on return.
    }
  }

  /// Field labels per step: inspection body; carrier and bill of lading number; none for delivery.
  List<String> _fieldLabels(AppLocalizations l10n) => switch (_key) {
        'preshipment_inspection' => [l10n.stepInspectionBody],
        'shipped' => [l10n.stepCarrier, l10n.stepBlNumber],
        _ => const [],
      };

  String _notes(AppLocalizations l10n) {
    final labels = _fieldLabels(l10n);
    final values = [_first.text.trim(), _second.text.trim()];
    return [for (var i = 0; i < labels.length; i++) '${labels[i]}: ${values[i]}'].join(' | ');
  }

  Future<void> _pick() async {
    final file = await (widget.pickProof ?? UploadFile.pickDocument)();
    if (file == null || !mounted) return;
    final rejection = file.localRejection;
    if (rejection != null) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(FiraErrorText.fileRejection(context, rejection))));
      return;
    }
    setState(() => _file = file);
  }

  Future<void> _send() async {
    final l10n = context.l10n;
    setState(() => _tried = true);
    final labels = _fieldLabels(l10n);
    final missingField = (labels.isNotEmpty && _first.text.trim().isEmpty) || (labels.length > 1 && _second.text.trim().isEmpty);
    if (_file == null || missingField) return;
    setState(() => _sending = true);
    final res = await _service.advanceMilestone(dealRoomId: _deal.id, milestoneId: _step.id, document: _file, notes: _notes(l10n));
    if (!mounted) return;
    setState(() => _sending = false);
    if (res['success'] == true) {
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(content: Text(l10n.stepProofSent)));
      setState(() => _file = null);
    } else {
      showFiraActionError(context, res['error']);
    }
    await _reload();
  }

  Future<void> _close() async {
    final l10n = context.l10n;
    setState(() => _sending = true);
    final ok = await _service.completeDeal(_deal.id);
    if (!mounted) return;
    setState(() => _sending = false);
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(ok ? l10n.stepDealClosed : l10n.stepDealCloseFailed)));
    if (ok) Navigator.of(context).pop(true);
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    final d = _deal;
    final step = _step;
    final mine = step.side == 'both' || step.side == d.mySide;
    final current = step.key == d.currentStep;
    final canSend = mine && current && !step.isSubmitted && !step.isDone && !d.paused;
    final labels = _fieldLabels(l10n);
    final controllers = [_first, _second];

    return Scaffold(
      appBar: AppBar(title: Text(l10n.translate('dealStep_$_key'))),
      body: RefreshIndicator(
        onRefresh: _reload,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
          children: [
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text('${d.dealReference} · ${d.title}', style: const TextStyle(fontWeight: FontWeight.w700)),
                  Text('${TradeLabels.money(d.targetValueUsd)} · ${d.incoterm}', style: TextStyle(color: fira.mutedText)),
                  const SizedBox(height: 8),
                  Text(l10n.translate('stepAbout_$_key')),
                  const SizedBox(height: 4),
                  Text(
                    l10n.translate('stepWho', {'side': step.side == 'seller' ? l10n.dealSeller : l10n.dealBuyer}),
                    style: TextStyle(color: fira.mutedText, fontSize: 12),
                  ),
                ]),
              ),
            ),
            if (d.paused)
              Card(color: fira.brandTint, child: ListTile(leading: Icon(Icons.pause_circle_outline, color: fira.danger), title: Text(l10n.dealPaused))),
            const SizedBox(height: 8),
            if (step.locked)
              Text(l10n.dealStepLockedHint, key: const Key('step-locked'), style: TextStyle(color: fira.mutedText))
            else if (!mine && !step.isDone)
              Text(l10n.dealStepOtherSide, key: const Key('step-other-side'), style: TextStyle(color: fira.mutedText))
            else if (canSend) ...[
              for (var i = 0; i < labels.length; i++)
                TextField(
                  key: Key('step-field-$i'),
                  controller: controllers[i],
                  maxLength: 120,
                  decoration: InputDecoration(
                    labelText: labels[i],
                    errorText: _tried && controllers[i].text.trim().isEmpty ? l10n.captureRequired : null,
                  ),
                ),
              OutlinedButton.icon(
                key: const Key('step-pick'),
                onPressed: _sending ? null : _pick,
                icon: Icon(_file == null ? Icons.attach_file : Icons.check_circle, color: _file == null ? null : fira.success),
                label: Text(_file?.name ?? l10n.translate('stepProof_$_key')),
              ),
              if (_tried && _file == null)
                Text(l10n.stepProofRequired, key: const Key('step-proof-required'), style: TextStyle(color: fira.danger)),
              const SizedBox(height: 12),
              FilledButton(
                key: const Key('step-send'),
                onPressed: _sending ? null : _send,
                child: _sending ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2)) : Text(l10n.stepSendProof),
              ),
            ],
            const SizedBox(height: 16),
            _tracker(context, step),
            if (_key == 'final_settlement' && step.isDone && !d.isCompleted && d.mySide != null) ...[
              const SizedBox(height: 16),
              FilledButton(
                key: const Key('step-close-deal'),
                onPressed: _sending ? null : _close,
                child: Text(l10n.stepCloseDeal),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _tracker(BuildContext context, DealMilestoneModel step) {
    final l10n = context.l10n;
    final fira = context.fira;
    final stages = [
      (l10n.payTrackSubmitted, step.isSubmitted || step.isDone),
      (l10n.stepTrackConfirms, step.isDone),
      (l10n.payTrackDone, step.isDone),
    ];
    return Row(children: [
      for (final s in stages)
        Expanded(
          child: Column(children: [
            Icon(s.$2 ? Icons.check_circle : Icons.radio_button_unchecked, color: s.$2 ? fira.success : fira.mutedText),
            const SizedBox(height: 4),
            Text(s.$1, textAlign: TextAlign.center, style: const TextStyle(fontSize: 12)),
          ]),
        ),
    ]);
  }
}
