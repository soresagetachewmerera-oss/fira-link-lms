import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../models/deal_room_model.dart';
import '../../services/deal_room_service.dart';
import '../../services/trust_safety_service.dart';
import '../../services/upload_file.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import '../../widgets/trade_labels.dart';
import '../../widgets/verification_labels.dart';
import '../trust_safety/report_modal.dart';

typedef PickSlip = Future<UploadFile?> Function();

/// Payment step (slice 7): the deposit at "Payment secured" or the balance at "Documents released".
/// The buyer pays only to the seller's confirmed payout account; the slip moves the step to
/// "Submitted", and it is done only after the bank confirms it to Fira Link.
class PaymentMilestonesScreen extends StatefulWidget {
  const PaymentMilestonesScreen({super.key, required this.dealRoom, required this.milestone, this.service, this.trust, this.pickSlip});

  final DealRoomModel dealRoom;
  final DealMilestoneModel milestone;
  final DealRoomService? service;
  final TrustSafetyService? trust;
  final PickSlip? pickSlip;

  @override
  State<PaymentMilestonesScreen> createState() => _PaymentMilestonesScreenState();
}

class _PaymentMilestonesScreenState extends State<PaymentMilestonesScreen> {
  late final DealRoomService _service = widget.service ?? DealRoomService();
  late DealRoomModel _deal = widget.dealRoom;
  late String _method = widget.dealRoom.paymentMethod ?? 'bank_transfer';
  bool _sending = false;

  DealMilestoneModel get _step => _deal.step(widget.milestone.key) ?? widget.milestone;

  Future<void> _reload() async {
    try {
      final d = await _service.getDealRoom(_deal.id);
      if (mounted) setState(() => _deal = d);
    } catch (_) {
      // Keep what we have; the hub reloads on return.
    }
  }

  Future<void> _upload() async {
    final file = await (widget.pickSlip ?? UploadFile.pickDocument)();
    if (file == null || !mounted) return;
    final rejection = file.localRejection;
    if (rejection != null) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(FiraErrorText.fileRejection(context, rejection))));
      return;
    }
    final l10n = context.l10n;
    setState(() => _sending = true);
    final res = await _service.advanceMilestone(
      dealRoomId: _deal.id,
      milestoneId: _step.id,
      document: file,
      paymentMethod: _step.money == 'deposit' ? _method : null,
    );
    if (!mounted) return;
    setState(() => _sending = false);
    if (res['success'] == true) {
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(content: Text(l10n.paySubmitted)));
      await _reload();
    } else {
      showFiraActionError(context, res['error']);
      await _reload();
    }
  }

  void _report() => ReportModal.show(
        context,
        reportedDealRoomId: _deal.id,
        initialReason: 'pay_outside',
        targetTitle: '${_deal.dealReference} · ${_deal.seller?.name ?? ''}',
        service: widget.trust,
      );

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    final d = _deal;
    final step = _step;
    final deposit = step.money == 'deposit';
    final payTo = d.payTo;
    final current = step.key == d.currentStep;
    final canPay = d.isBuyer && current && !step.isSubmitted && !d.paused && (payTo?.ready ?? false);

    return Scaffold(
      appBar: AppBar(title: Text(deposit ? l10n.payTitleDeposit : l10n.payTitleBalance)),
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
                  _amountRow(context, l10n.translate('payDepositLine', {'pct': d.depositPercent}), d.depositUsd, deposit),
                  const SizedBox(height: 8),
                  _amountRow(context, l10n.translate('payBalanceLine', {'pct': 100 - d.depositPercent}), d.balanceUsd, !deposit),
                  const SizedBox(height: 12),
                  Text(l10n.payRule, style: TextStyle(color: fira.mutedText, fontSize: 13)),
                ]),
              ),
            ),
            if (d.paused)
              Card(color: fira.brandTint, child: ListTile(leading: Icon(Icons.pause_circle_outline, color: fira.danger), title: Text(l10n.dealPaused))),
            if (deposit && d.isBuyer) ...[
              const SizedBox(height: 8),
              Text(l10n.payMethod, style: const TextStyle(fontWeight: FontWeight.w700)),
              const SizedBox(height: 6),
              Wrap(spacing: 8, children: [
                for (final (value, label) in [('bank_transfer', l10n.payBankTransfer), ('letter_of_credit', l10n.payLetterOfCredit)])
                  ChoiceChip(
                    key: Key('method-$value'),
                    label: Text(label),
                    selected: _method == value,
                    onSelected: step.isSubmitted || step.isDone ? null : (_) => setState(() => _method = value),
                  ),
              ]),
            ],
            const SizedBox(height: 12),
            _payTo(context, d, payTo),
            const SizedBox(height: 12),
            if (step.locked)
              Text(deposit ? l10n.payLockedDeposit : l10n.payLocked, key: const Key('pay-locked'), style: TextStyle(color: fira.mutedText))
            else if (d.isBuyer && !step.isDone && !step.isSubmitted)
              FilledButton.icon(
                key: const Key('pay-upload'),
                onPressed: canPay && !_sending ? _upload : null,
                icon: _sending ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2)) : const Icon(Icons.upload_file),
                label: Text(l10n.payUpload),
              )
            else if (d.isSeller && !step.isDone)
              Text(l10n.paySellerWaits, style: TextStyle(color: fira.mutedText)),
            const SizedBox(height: 12),
            _tracker(context, step),
            const SizedBox(height: 16),
            Card(
              key: const Key('pay-wrong-account'),
              color: fira.brandTint,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(l10n.translate('payWrongAccount', {'hours': 48}), style: TextStyle(color: fira.onSurface)),
                  const SizedBox(height: 8),
                  OutlinedButton.icon(onPressed: _report, icon: const Icon(Icons.flag_outlined), label: Text(l10n.payReportIt)),
                ]),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _amountRow(BuildContext context, String label, double amount, bool active) => Row(children: [
        Expanded(child: Text(label, style: TextStyle(fontWeight: active ? FontWeight.w700 : FontWeight.w400))),
        const SizedBox(width: 12),
        Text(TradeLabels.money(amount), style: TextStyle(fontWeight: active ? FontWeight.w700 : FontWeight.w500)),
      ]);

  Widget _payTo(BuildContext context, DealRoomModel d, PayTo? p) {
    final l10n = context.l10n;
    final fira = context.fira;
    final state = p?.state ?? 'missing';
    final warning = switch (state) {
      'paused' => l10n.translate('payPaused', {'date': VerificationLabels.date(context, p?.pausedUntil)}),
      'pending_review' => l10n.payPendingReview,
      'missing' => l10n.payMissing,
      _ => null,
    };
    return Card(
      key: Key('pay-to-$state'),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(l10n.payTo, style: TextStyle(color: fira.mutedText, fontSize: 12)),
          Text(p?.business ?? d.seller?.name ?? '', style: const TextStyle(fontWeight: FontWeight.w700)),
          if (p?.last4 != null) Text(l10n.translate('payToLine', {'bank': p!.bankName ?? '', 'last4': p.last4})),
          if (state == 'ready' && p!.matchesVerified)
            Padding(
              padding: const EdgeInsets.only(top: 4),
              child: Row(children: [
                Icon(Icons.verified_outlined, color: fira.success, size: 18),
                const SizedBox(width: 6),
                Expanded(child: Text(l10n.payMatches, style: TextStyle(color: fira.success, fontWeight: FontWeight.w600))),
              ]),
            ),
          if (warning != null)
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: Text(warning, style: TextStyle(color: fira.danger, fontWeight: FontWeight.w600)),
            ),
        ]),
      ),
    );
  }

  Widget _tracker(BuildContext context, DealMilestoneModel step) {
    final l10n = context.l10n;
    final fira = context.fira;
    final stages = [
      (l10n.payTrackSubmitted, step.isSubmitted || step.isDone),
      (l10n.payTrackBank, step.isDone),
      (l10n.payTrackDone, step.isDone),
    ];
    return Row(children: [
      for (var i = 0; i < stages.length; i++) ...[
        Expanded(
          child: Column(children: [
            Icon(stages[i].$2 ? Icons.check_circle : Icons.radio_button_unchecked, color: stages[i].$2 ? fira.success : fira.mutedText),
            const SizedBox(height: 4),
            Text(stages[i].$1, textAlign: TextAlign.center, style: const TextStyle(fontSize: 12)),
          ]),
        ),
      ],
    ]);
  }
}
