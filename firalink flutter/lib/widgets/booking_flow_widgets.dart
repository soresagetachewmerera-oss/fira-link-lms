import 'package:flutter/material.dart';

import '../l10n/app_localizations.dart';
import '../models/booking_flow_model.dart';
import '../services/upload_file.dart';
import '../theme/fira_colors.dart';
import 'fira_error_state.dart';

/// "Organizer verified" / "Host verified" / "Organized by Fira Link". Verified only while the
/// linked business has an active Tier 1 (the server decides; plans never change it).
class VerifiedBusinessLine extends StatelessWidget {
  const VerifiedBusinessLine({super.key, required this.business, this.host = false, this.showName = false});

  final BookingBusiness? business;
  final bool host;
  final bool showName;

  @override
  Widget build(BuildContext context) {
    final b = business;
    if (b == null) return const SizedBox.shrink();
    final l10n = context.l10n;
    final fira = context.fira;
    final label = b.isFiraLink
        ? l10n.bookingOrganizedByFira
        : b.verified
            ? (host ? l10n.bookingHostVerified : l10n.bookingOrganizerVerified)
            : l10n.bookingNotVerified;
    return Row(mainAxisSize: MainAxisSize.min, children: [
      Icon(b.verified ? Icons.verified_outlined : Icons.info_outline, size: 16, color: b.verified ? fira.success : fira.mutedText),
      const SizedBox(width: 4),
      Flexible(
        child: Text(
          showName && !b.isFiraLink ? '${b.name} · $label' : label,
          style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: b.verified ? fira.success : fira.mutedText),
          overflow: TextOverflow.ellipsis,
        ),
      ),
    ]);
  }
}

/// Organizer or host card: name, verified line, hosting since / past events.
class BookingBusinessCard extends StatelessWidget {
  const BookingBusinessCard({super.key, required this.business, this.host = false});

  final BookingBusiness business;
  final bool host;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    final b = business;
    final extra = [
      if (host && b.since != null) l10n.translate('bookingHostingSince', {'year': b.since}),
      if (!host && b.pastEvents != null) l10n.translate('bookingPastEvents', {'n': b.pastEvents}),
    ];
    return Card(
      key: Key(host ? 'tour-host' : 'event-organizer'),
      child: ListTile(
        leading: CircleAvatar(backgroundColor: fira.brandTint, child: Icon(host ? Icons.agriculture_outlined : Icons.storefront_outlined, color: fira.brandPrimary)),
        title: Text(b.name, style: const TextStyle(fontWeight: FontWeight.w700)),
        subtitle: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          VerifiedBusinessLine(business: b, host: host),
          if (extra.isNotEmpty) Text(extra.join(' · '), style: TextStyle(color: fira.mutedText, fontSize: 12)),
        ]),
      ),
    );
  }
}

/// Pending status: purple on lavender, never amber.
class InReviewPill extends StatelessWidget {
  const InReviewPill({super.key});

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    return Container(
      key: const Key('in-review-pill'),
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(color: fira.brandTint, borderRadius: BorderRadius.circular(12)),
      child: Text(context.l10n.bookingInReview, style: TextStyle(color: fira.pending, fontWeight: FontWeight.w700, fontSize: 12)),
    );
  }
}

/// "Pay only through Fira Link" note with an optional report action.
class PayOnlyHereCard extends StatelessWidget {
  const PayOnlyHereCard({super.key, required this.text, this.onReport});

  final String text;
  final VoidCallback? onReport;

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    return Card(
      key: const Key('pay-only-here'),
      color: fira.brandTint,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Icon(Icons.shield_outlined, color: fira.brandPrimary),
            const SizedBox(width: 8),
            Expanded(child: Text(text, style: TextStyle(color: fira.onSurface))),
          ]),
          if (onReport != null)
            Align(alignment: Alignment.centerRight, child: TextButton(onPressed: onReport, child: Text(context.l10n.payReportIt))),
        ]),
      ),
    );
  }
}

typedef PickSlipFn = Future<UploadFile?> Function();

/// Pay with: Chapa, or bank transfer with the slip and an optional bank reference.
class PaymentMethodPicker extends StatelessWidget {
  const PaymentMethodPicker({
    super.key,
    required this.method,
    required this.onMethod,
    required this.slip,
    required this.onSlip,
    required this.bankReference,
    this.showSlipError = false,
    this.pickSlip,
  });

  final String method;
  final ValueChanged<String> onMethod;
  final UploadFile? slip;
  final ValueChanged<UploadFile> onSlip;
  final TextEditingController bankReference;
  final bool showSlipError;
  final PickSlipFn? pickSlip;

  Future<void> _pick(BuildContext context) async {
    final file = await (pickSlip ?? UploadFile.pickDocument)();
    if (file == null || !context.mounted) return;
    final rejection = file.localRejection;
    if (rejection != null) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(FiraErrorText.fileRejection(context, rejection))));
      return;
    }
    onSlip(file);
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text(l10n.bookingPayWith, style: const TextStyle(fontWeight: FontWeight.w700)),
      RadioGroup<String>(
        groupValue: method,
        onChanged: (v) => v == null ? null : onMethod(v),
        child: Column(children: [
          RadioListTile<String>(
            key: const Key('method-chapa'),
            value: 'chapa',
            contentPadding: EdgeInsets.zero,
            title: const Text('Chapa'),
            subtitle: Text(l10n.bookingChapaSub),
          ),
          RadioListTile<String>(
            key: const Key('method-bank_transfer'),
            value: 'bank_transfer',
            contentPadding: EdgeInsets.zero,
            title: Text(l10n.payBankTransfer),
            subtitle: Text(l10n.bookingBankSub),
          ),
        ]),
      ),
      if (method == 'bank_transfer') ...[
        OutlinedButton.icon(
          key: const Key('booking-slip'),
          onPressed: () => _pick(context),
          icon: Icon(slip == null ? Icons.upload_file : Icons.check_circle, color: slip == null ? null : fira.success),
          label: Text(slip?.name ?? l10n.bookingUploadSlip),
        ),
        if (showSlipError && slip == null)
          Text(l10n.bookingSlipRequired, key: const Key('booking-slip-required'), style: TextStyle(color: fira.danger)),
        TextField(
          key: const Key('booking-bank-ref'),
          controller: bankReference,
          maxLength: 120,
          decoration: InputDecoration(labelText: l10n.bookingBankReference),
        ),
      ],
    ]);
  }
}

/// One label-amount line in a price summary.
class PriceLine extends StatelessWidget {
  const PriceLine({super.key, required this.label, required this.value, this.bold = false});

  final String label;
  final String value;
  final bool bold;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 3),
        child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Expanded(child: Text(label, style: TextStyle(fontWeight: bold ? FontWeight.w700 : FontWeight.w400))),
          const SizedBox(width: 12),
          Flexible(child: Text(value, textAlign: TextAlign.end, style: TextStyle(fontWeight: bold ? FontWeight.w700 : FontWeight.w600))),
        ]),
      );
}

String bookingMoney(String currency, double v) => '$currency ${v.toStringAsFixed(2)}';
