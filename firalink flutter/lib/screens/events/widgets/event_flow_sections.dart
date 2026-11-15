import 'package:flutter/material.dart';

import '../../../l10n/app_localizations.dart';
import '../../../models/booking_flow_model.dart';
import '../../../theme/fira_colors.dart';
import '../../../widgets/booking_flow_widgets.dart';

/// Event detail additions from the flow: seats left, Agenda and Tickets (General / Member).
class EventFlowSections extends StatelessWidget {
  const EventFlowSections({super.key, required this.flow, required this.currency});

  final EventFlow flow;
  final String currency;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        if (flow.seatsLeft != null)
          Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: Text(l10n.translate('eventSeatsLeft', {'n': flow.seatsLeft}), key: const Key('event-seats-left'),
                style: TextStyle(color: fira.pending, fontWeight: FontWeight.w700)),
          ),
        if (flow.agenda.isNotEmpty) ...[
          Text(l10n.eventAgenda, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
          Card(
            key: const Key('event-agenda'),
            child: Column(children: [
              for (final a in flow.agenda)
                ListTile(
                  dense: true,
                  leading: SizedBox(width: 56, child: Text(a.time ?? '', style: TextStyle(color: fira.brandPrimary, fontWeight: FontWeight.w700))),
                  title: Text(a.title),
                ),
            ]),
          ),
          const SizedBox(height: 12),
        ],
        if (flow.tickets.isNotEmpty) ...[
          Text(l10n.eventTickets, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
          Card(
            key: const Key('event-tickets'),
            child: Column(children: [
              for (final t in flow.tickets)
                ListTile(
                  dense: true,
                  title: Text(l10n.translate('eventTicket_${t.type}')),
                  subtitle: Text(t.type == 'member' ? (t.eligible ? l10n.eventTicketMemberSub : l10n.eventTicketMemberOnly) : l10n.eventTicketGeneralSub),
                  trailing: Text(t.price > 0 ? bookingMoney(currency, t.price) : l10n.free, style: const TextStyle(fontWeight: FontWeight.w700)),
                ),
            ]),
          ),
          const SizedBox(height: 12),
        ],
      ]),
    );
  }
}
