import 'package:flutter/material.dart';

import '../../../l10n/app_localizations.dart';
import '../../../models/booking_flow_model.dart';
import '../../../services/booking_service.dart';
import '../../../services/upload_file.dart';
import '../../../theme/fira_colors.dart';
import '../../../widgets/booking_flow_widgets.dart';
import '../../../widgets/fira_error_state.dart';
import '../../../widgets/verification_labels.dart';
import '../../common/webview_screen.dart';
import '../../trust_safety/report_modal.dart';

/// Booking confirmed (tour flow): paid now, balance due, pay the balance in the app, what happens
/// next, and "pay only inside Fira Link; if the host asks for cash or a transfer, report it".
class TourPaymentPanel extends StatelessWidget {
  const TourPaymentPanel({super.key, required this.bookingId, required this.ticket, required this.onChanged, this.service, this.pickSlip});

  final int bookingId;
  final Map<String, dynamic> ticket;
  final VoidCallback onChanged;
  final BookingService? service;
  final PickSlipFn? pickSlip;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    final p = TourBookingPayment.fromJson(ticket);
    final currency = ticket['currency']?.toString() ?? 'ETB';
    final host = BookingBusiness.fromJson(ticket['host']);
    final date = DateTime.tryParse(ticket['schedule_date_iso']?.toString() ?? '');
    final meetingBy = date?.subtract(const Duration(days: 3));

    return Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      Card(
        key: const Key('tour-payment'),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            PriceLine(label: p.depositPaid ? l10n.tourPaid : l10n.tourPayNowLabel, value: bookingMoney(currency, p.depositAmount)),
            if (p.balanceAmount > 0)
              PriceLine(
                label: l10n.translate('tourBalanceDue', {'date': VerificationLabels.date(context, p.balanceDueDate)}),
                value: p.balancePaid ? l10n.tourBalancePaid : bookingMoney(currency, p.balanceAmount),
              ),
            if (p.inReview) ...[const SizedBox(height: 8), const InReviewPill()],
            if (p.canPayBalance) ...[
              const SizedBox(height: 12),
              FilledButton(
                key: const Key('tour-pay-balance'),
                onPressed: () => _payBalance(context, p, currency),
                child: Text(l10n.tourPayBalance),
              ),
            ],
          ]),
        ),
      ),
      const SizedBox(height: 12),
      Text(l10n.tourWhatNext, style: const TextStyle(fontWeight: FontWeight.w700)),
      for (final (done, text) in [
        (p.depositPaid, l10n.tourStepConfirmed),
        (false, meetingBy == null ? l10n.tourStepMeetingPoint : l10n.translate('tourStepMeetingPointBy', {'date': VerificationLabels.date(context, meetingBy)})),
        (p.balancePaid, l10n.tourStepPayBalance),
      ])
        ListTile(
          dense: true,
          contentPadding: EdgeInsets.zero,
          leading: Icon(done ? Icons.check_circle : Icons.radio_button_unchecked, color: done ? fira.success : fira.mutedText),
          title: Text(text),
        ),
      if (host != null) BookingBusinessCard(business: host, host: true),
      PayOnlyHereCard(
        text: l10n.tourPayOnlyHere,
        onReport: () => ReportModal.show(context, initialReason: 'pay_outside', targetTitle: '${ticket['tour_name'] ?? ''} · ${ticket['booking_id'] ?? ''}'),
      ),
    ]);
  }

  Future<void> _payBalance(BuildContext context, TourBookingPayment p, String currency) async {
    final paid = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (_) => _BalanceSheet(bookingId: bookingId, amount: bookingMoney(currency, p.balanceAmount), service: service ?? BookingService(), pickSlip: pickSlip),
    );
    if (paid == true) onChanged();
  }
}

class _BalanceSheet extends StatefulWidget {
  const _BalanceSheet({required this.bookingId, required this.amount, required this.service, this.pickSlip});

  final int bookingId;
  final String amount;
  final BookingService service;
  final PickSlipFn? pickSlip;

  @override
  State<_BalanceSheet> createState() => _BalanceSheetState();
}

class _BalanceSheetState extends State<_BalanceSheet> {
  final _ref = TextEditingController();
  String _method = 'chapa';
  UploadFile? _slip;
  bool _tried = false;
  bool _sending = false;

  @override
  void dispose() {
    _ref.dispose();
    super.dispose();
  }

  Future<void> _send() async {
    setState(() => _tried = true);
    if (_method == 'bank_transfer' && _slip == null) return;
    final l10n = context.l10n;
    final nav = Navigator.of(context);
    final messenger = ScaffoldMessenger.of(context);
    setState(() => _sending = true);
    try {
      final res = await widget.service.payTourBalance(widget.bookingId, method: _method, slip: _slip, bankReference: _ref.text.trim().isEmpty ? null : _ref.text.trim());
      if (res['payment_required'] == true && res['checkout_url'] != null && mounted) {
        await nav.push(MaterialPageRoute(
          builder: (_) => WebViewScreen(url: res['checkout_url'].toString(), title: 'Chapa', shouldExit: (u) => u.contains('/tours/payment/chapa/callback')),
        ));
        await widget.service.verifyChapa(tour: true, txRef: res['tx_ref']?.toString() ?? '', bookingId: widget.bookingId);
      }
      nav.pop(true);
      messenger
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(content: Text(_method == 'bank_transfer' ? l10n.bookingSlipSent : l10n.tourBalanceDone)));
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, mainAxisSize: MainAxisSize.min, children: [
          Text(l10n.tourPayBalance, style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700)),
          PriceLine(label: l10n.sampleTotal, value: widget.amount, bold: true),
          const SizedBox(height: 8),
          PaymentMethodPicker(
            method: _method,
            onMethod: (m) => setState(() => _method = m),
            slip: _slip,
            onSlip: (f) => setState(() => _slip = f),
            bankReference: _ref,
            showSlipError: _tried,
            pickSlip: widget.pickSlip,
          ),
          const SizedBox(height: 12),
          FilledButton(
            key: const Key('balance-send'),
            onPressed: _sending ? null : _send,
            child: _sending ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2)) : Text(l10n.tourPayBalance),
          ),
        ]),
      ),
    );
  }
}
