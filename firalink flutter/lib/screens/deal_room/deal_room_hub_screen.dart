import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../models/deal_room_model.dart';
import '../../models/trade_model.dart';
import '../../services/deal_room_service.dart';
import '../../services/trade_chat_service.dart';
import '../../services/trust_safety_service.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import '../../widgets/trade_labels.dart';
import '../../widgets/verification_labels.dart';
import '../chat/trade_chat_screen.dart';
import '../trust_safety/report_modal.dart';
import 'deal_step_screen.dart';
import 'export_contract_esign_screen.dart';
import 'payment_milestones_screen.dart';

/// Shared labels for deal rooms (slice 7).
class DealLabels {
  static String step(AppLocalizations l10n, String key) => l10n.translate('dealStep_$key');

  /// "Waiting for deposit" style line for the current step.
  static String waiting(AppLocalizations l10n, DealRoomModel d) {
    if (d.isCompleted) return l10n.dealStatusCompleted;
    final key = d.currentStep;
    return key == null ? l10n.dealStatusAllDone : l10n.translate('dealWaiting_$key');
  }

  static String activity(AppLocalizations l10n, DealActivity a) {
    final step = a.step == null ? '' : DealLabels.step(l10n, a.step!);
    if (a.action == 'deal_milestone_updated') {
      return l10n.translate(a.status == 'proof_submitted' ? 'dealAct_proof' : 'dealAct_updated', {'step': step});
    }
    return l10n.translate('dealAct_${a.action}', {'step': step});
  }
}

/// Deal room (slice 7): both businesses, six steps in order, documents and activity.
/// Money moves only through the steps; the server decides which step is open.
class DealRoomHubScreen extends StatefulWidget {
  const DealRoomHubScreen({super.key, required this.dealRoomId, this.service, this.chat, this.trust});

  final int dealRoomId;
  final DealRoomService? service;
  final TradeChatService? chat;
  final TrustSafetyService? trust;

  @override
  State<DealRoomHubScreen> createState() => _DealRoomHubScreenState();
}

class _DealRoomHubScreenState extends State<DealRoomHubScreen> {
  late final DealRoomService _service = widget.service ?? DealRoomService();
  late final TradeChatService _chat = widget.chat ?? TradeChatService();
  DealRoomModel? _deal;
  Object? _error;
  bool _opening = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _error = null);
    try {
      final d = await _service.getDealRoom(widget.dealRoomId);
      if (mounted) setState(() => _deal = d);
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  Future<void> _push(Widget screen) async {
    await Navigator.of(context).push(MaterialPageRoute(builder: (_) => screen));
    if (mounted) _load();
  }

  void _openStep(DealRoomModel d, DealMilestoneModel m) {
    final l10n = context.l10n;
    if (m.locked) {
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(content: Text(l10n.dealStepLockedHint)));
      return;
    }
    if (m.money != null) {
      _push(PaymentMilestonesScreen(dealRoom: d, milestone: m, service: _service, trust: widget.trust));
      return;
    }
    switch (m.key) {
      case 'contract_signed':
        _push(ExportContractEsignScreen(dealRoom: d));
      case 'preshipment_inspection' || 'shipped' || 'final_settlement':
        _push(DealStepScreen(dealRoom: d, milestone: m, service: _service));
    }
  }

  Future<void> _openChat(DealRoomModel d) async {
    setState(() => _opening = true);
    try {
      final c = await _chat.forDealRoom(d.id);
      if (!mounted) return;
      await _push(TradeChatScreen(conversationId: c.id, service: _chat, trust: widget.trust));
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    } finally {
      if (mounted) setState(() => _opening = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    return Scaffold(
      appBar: AppBar(title: Text(_deal?.dealReference ?? l10n.dealTitle)),
      body: _error != null
          ? FiraErrorState(error: _error, onRetry: _load)
          : _deal == null
              ? const Center(child: CircularProgressIndicator())
              : RefreshIndicator(onRefresh: _load, child: _body(context, _deal!)),
    );
  }

  Widget _party(BuildContext context, String label, BusinessSummary? b, bool me) {
    final fira = context.fira;
    final l10n = context.l10n;
    return Expanded(
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(me ? '$label · ${l10n.dealYou}' : label, style: TextStyle(color: fira.mutedText, fontSize: 12)),
        Text(b?.name ?? '–', style: const TextStyle(fontWeight: FontWeight.w700), maxLines: 2, overflow: TextOverflow.ellipsis),
        BusinessTierLabel(business: b),
      ]),
    );
  }

  Widget _body(BuildContext context, DealRoomModel d) {
    final l10n = context.l10n;
    final fira = context.fira;
    final lot = d.lot;
    final docs = d.documents;

    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
      children: [
        Text('${d.title} · ${d.incoterm}', style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700)),
        const SizedBox(height: 4),
        Text(TradeLabels.money(d.targetValueUsd), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 18)),
        if (lot != null)
          Text(
            [
              lot.title,
              if (lot.lotCode != null) lot.lotCode!,
              if (lot.pricePerLb != null) l10n.translate('dealPricePerLb', {'price': '\$${lot.pricePerLb!.toStringAsFixed(2)}'}),
            ].join(' · '),
            style: TextStyle(color: fira.mutedText),
          ),
        const SizedBox(height: 8),
        Text(
          l10n.translate('dealStepLine', {'n': d.stepNumber, 'total': d.stepCount, 'status': DealLabels.waiting(l10n, d)}),
          key: const Key('deal-step-line'),
          style: TextStyle(color: fira.pending, fontWeight: FontWeight.w700),
        ),
        if (d.paused) ...[
          const SizedBox(height: 12),
          Card(
            key: const Key('deal-paused'),
            color: fira.brandTint,
            child: ListTile(
              leading: Icon(Icons.pause_circle_outline, color: fira.danger),
              title: Text(l10n.dealPaused, style: TextStyle(color: fira.onSurface)),
            ),
          ),
        ],
        const SizedBox(height: 12),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
              _party(context, l10n.dealBuyer, d.buyer, d.isBuyer),
              const SizedBox(width: 12),
              _party(context, l10n.dealSeller, d.seller, d.isSeller),
            ]),
          ),
        ),
        const SizedBox(height: 16),
        Text(l10n.dealSteps, style: const TextStyle(fontWeight: FontWeight.w700)),
        for (var i = 0; i < d.milestones.length; i++) _stepTile(context, d, d.milestones[i], i + 1),
        if (!d.isFullySigned && d.mySide != null && !d.iSigned) ...[
          const SizedBox(height: 8),
          FilledButton.icon(
            key: const Key('deal-sign'),
            onPressed: d.paused ? null : () => _push(ExportContractEsignScreen(dealRoom: d)),
            icon: const Icon(Icons.draw_outlined),
            label: Text(l10n.dealSign),
          ),
        ] else if (!d.isFullySigned && d.iSigned)
          Padding(
            padding: const EdgeInsets.only(top: 8),
            child: Text(l10n.dealSignedWaiting, style: TextStyle(color: fira.pending)),
          ),
        const SizedBox(height: 16),
        Text(l10n.dealDocuments, style: const TextStyle(fontWeight: FontWeight.w700)),
        Card(
          child: Column(children: [
            ListTile(
              leading: Icon(docs?.contractSigned == true ? Icons.check_circle : Icons.description_outlined,
                  color: docs?.contractSigned == true ? fira.success : fira.mutedText),
              title: Text(docs?.contractSigned == true ? l10n.dealDocContract : l10n.dealDocContractPending),
            ),
            ListTile(
              key: const Key('deal-seal'),
              leading: Icon(Icons.inventory_2_outlined, color: docs?.sampleSealId != null ? fira.brandPrimary : fira.mutedText),
              title: Text(docs?.sampleSealId != null ? l10n.translate('dealDocSeal', {'seal': docs!.sampleSealId}) : l10n.dealDocNoSeal),
              trailing: docs?.sampleSealId != null ? Text(l10n.dealDocSealLinked, style: TextStyle(color: fira.success, fontWeight: FontWeight.w600)) : null,
            ),
          ]),
        ),
        if (d.activity.isNotEmpty) ...[
          const SizedBox(height: 16),
          Text(l10n.dealActivity, style: const TextStyle(fontWeight: FontWeight.w700)),
          Card(
            child: Column(children: [
              for (final a in d.activity.take(10))
                ListTile(
                  dense: true,
                  title: Text(DealLabels.activity(l10n, a)),
                  trailing: Text(VerificationLabels.date(context, a.at), style: TextStyle(color: fira.mutedText, fontSize: 12)),
                ),
            ]),
          ),
        ],
        const SizedBox(height: 16),
        if (d.mySide != null)
          Row(children: [
            Expanded(
              child: FilledButton.icon(
                key: const Key('deal-open-chat'),
                onPressed: _opening ? null : () => _openChat(d),
                icon: const Icon(Icons.chat_bubble_outline),
                label: Text(l10n.dealOpenChat),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: OutlinedButton.icon(
                key: const Key('deal-report'),
                onPressed: () => ReportModal.show(context, reportedDealRoomId: d.id, targetTitle: '${d.dealReference} · ${d.title}', service: widget.trust),
                icon: const Icon(Icons.flag_outlined),
                label: Text(l10n.dealReport),
              ),
            ),
          ]),
      ],
    );
  }

  Widget _stepTile(BuildContext context, DealRoomModel d, DealMilestoneModel m, int n) {
    final l10n = context.l10n;
    final fira = context.fira;
    final current = m.key == d.currentStep;
    final (IconData icon, Color color, String state) = m.isDone
        ? (Icons.check_circle, fira.success, l10n.dealStepDone)
        : m.isSubmitted
            ? (Icons.hourglass_top, fira.pending, l10n.dealStepSubmitted)
            : current
                ? (Icons.radio_button_checked, fira.pending, l10n.dealStepNow)
                : (Icons.lock_outline, fira.mutedText, l10n.dealStepLocked);
    final amount = m.amountUsd != null ? ' · ${TradeLabels.money(m.amountUsd!)}' : '';

    return Card(
      key: Key('step-${m.key}'),
      color: current ? fira.brandTint : null,
      child: ListTile(
        onTap: () => _openStep(d, m),
        leading: Icon(icon, color: color),
        title: Text('$n. ${DealLabels.step(l10n, m.key)}', style: const TextStyle(fontWeight: FontWeight.w600)),
        subtitle: Text('$state$amount', style: TextStyle(color: color)),
        trailing: m.locked ? null : const Icon(Icons.chevron_right),
      ),
    );
  }
}
