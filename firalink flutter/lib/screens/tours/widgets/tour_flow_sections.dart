import 'package:flutter/material.dart';

import '../../../l10n/app_localizations.dart';
import '../../../models/tour.dart';
import '../../../theme/fira_colors.dart';
import '../../../widgets/booking_flow_widgets.dart';
import '../../../widgets/verification_labels.dart';

/// Tour detail additions from the flow: host card, not included, cancellation, safety ("never pay
/// the host directly"), next dates with places left and the deposit rule.
class TourFlowSections extends StatelessWidget {
  const TourFlowSections({super.key, required this.tour});

  final Tour tour;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    final flow = tour.flow;
    final exclusions = (tour.exclusions ?? const []).map((e) => e.toString()).where((e) => e.trim().isNotEmpty).toList();

    return Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      if (flow.host != null) ...[BookingBusinessCard(business: flow.host!, host: true), const SizedBox(height: 12)],
      if (exclusions.isNotEmpty) ...[
        Text(l10n.tourNotIncluded, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
        const SizedBox(height: 6),
        for (final x in exclusions)
          Padding(
            padding: const EdgeInsets.only(bottom: 4),
            child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Icon(Icons.remove_circle_outline, size: 14, color: fira.mutedText),
              const SizedBox(width: 8),
              Expanded(child: Text(x)),
            ]),
          ),
        const SizedBox(height: 12),
      ],
      if ((tour.cancellationPolicy ?? '').isNotEmpty) ...[
        Text.rich(TextSpan(children: [
          TextSpan(text: '${l10n.tourCancellation} ', style: const TextStyle(fontWeight: FontWeight.w700)),
          TextSpan(text: tour.cancellationPolicy),
        ])),
        const SizedBox(height: 8),
      ],
      PayOnlyHereCard(text: [if ((flow.safetyNotes ?? '').isNotEmpty) '${l10n.tourSafety} ${flow.safetyNotes}', l10n.tourNeverPayHost].join('\n')),
      const SizedBox(height: 12),
      Text(l10n.tourNextDates, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
      const SizedBox(height: 6),
      if (flow.nextDates.isEmpty)
        Text(l10n.tourNoDates, style: TextStyle(color: fira.mutedText))
      else
        Wrap(spacing: 8, runSpacing: 8, children: [
          for (final d in flow.nextDates)
            Chip(
              key: Key('next-date-${d.date.toIso8601String().substring(0, 10)}'),
              label: Text(d.placesLeft > 0
                  ? '${VerificationLabels.date(context, d.date)} · ${l10n.translate('tourPlacesLeft', {'n': d.placesLeft})}'
                  : '${VerificationLabels.date(context, d.date)} · ${l10n.tourFull}'),
            ),
        ]),
      const SizedBox(height: 8),
      if (tour.price > 0)
        Text(l10n.translate('tourDepositRule', {'pct': flow.depositPercent, 'days': flow.balanceDueDays}), style: TextStyle(color: fira.mutedText, fontSize: 12)),
      const SizedBox(height: 16),
    ]);
  }
}
