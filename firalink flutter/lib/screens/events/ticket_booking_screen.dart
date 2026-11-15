import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../l10n/app_localizations.dart';
import '../../models/booking_flow_model.dart';
import '../../models/event.dart';
import '../../providers/auth_provider.dart';
import '../../services/booking_service.dart';
import '../../services/upload_file.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/booking_flow_widgets.dart';
import '../../widgets/fira_error_state.dart';
import '../../widgets/verification_labels.dart';
import '../common/webview_screen.dart';
import '../dashboard/ticket_view_screen.dart';

/// Register for an event (flow "Register"): ticket type from the server (General, or Member for
/// Academy members), quantity, full name, phone, company, Chapa or bank transfer, service fee,
/// total and the organizer's refund policy. Prices always come from the server.
class TicketBookingScreen extends StatefulWidget {
  const TicketBookingScreen({super.key, required this.event, this.service, this.pickSlip});

  final Event event;
  final BookingService? service;
  final PickSlipFn? pickSlip;

  @override
  State<TicketBookingScreen> createState() => _TicketBookingScreenState();
}

class _TicketBookingScreenState extends State<TicketBookingScreen> {
  late final BookingService _service = widget.service ?? BookingService();
  final _formKey = GlobalKey<FormState>();
  final _first = TextEditingController();
  final _last = TextEditingController();
  final _phone = TextEditingController();
  final _company = TextEditingController();
  final _bankRef = TextEditingController();
  int _quantity = 1;
  String _type = 'general';
  String _method = 'chapa';
  UploadFile? _slip;
  bool _tried = false;
  bool _sending = false;

  EventFlow get _flow => widget.event.flow ?? EventFlow(tickets: [TicketOption(type: 'general', price: widget.event.price ?? 0)]);

  List<TicketOption> get _tickets => _flow.tickets.isEmpty ? [TicketOption(type: 'general', price: widget.event.price ?? 0)] : _flow.tickets;

  TicketOption get _ticket => _tickets.firstWhere((t) => t.type == _type, orElse: () => _tickets.first);

  int get _maxQuantity => (_flow.seatsLeft ?? 20).clamp(0, 20);

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final user = context.read<AuthProvider>().user;
      if (user == null) return;
      final parts = user.name.trim().split(RegExp(r'\s+'));
      _first.text = parts.isNotEmpty ? parts.first : '';
      _last.text = parts.length > 1 ? parts.sublist(1).join(' ') : '';
      _phone.text = user.phone ?? '';
    });
  }

  @override
  void dispose() {
    for (final c in [_first, _last, _phone, _company, _bankRef]) {
      c.dispose();
    }
    super.dispose();
  }

  double get _fee => _ticket.price > 0 ? _flow.serviceFee : 0;
  double get _total => (_ticket.price + _fee) * _quantity;
  String _money(double v) => bookingMoney(widget.event.currency, v);

  Future<void> _submit() async {
    setState(() => _tried = true);
    if (!(_formKey.currentState?.validate() ?? false)) return;
    final paid = _total > 0;
    if (paid && _method == 'bank_transfer' && _slip == null) return;
    final l10n = context.l10n;
    setState(() => _sending = true);
    try {
      final res = await _service.bookEvent(
        widget.event.id,
        {
          'first_name': _first.text.trim(),
          'last_name': _last.text.trim(),
          'phone': _phone.text.trim(),
          if (_company.text.trim().isNotEmpty) 'company': _company.text.trim(),
          'seats': _quantity,
          'ticket_type': _type,
          'payment_method': paid ? _method : 'free',
          if (_bankRef.text.trim().isNotEmpty) 'bank_reference': _bankRef.text.trim(),
        },
        slip: paid && _method == 'bank_transfer' ? _slip : null,
      );
      final id = int.tryParse('${res['booking_id'] ?? (res['booking'] is Map ? res['booking']['id'] : null)}') ?? 0;
      if (res['payment_required'] == true && res['checkout_url'] != null && mounted) {
        await Navigator.of(context).push(MaterialPageRoute(
          builder: (_) => WebViewScreen(url: res['checkout_url'].toString(), title: 'Chapa', shouldExit: (u) => u.contains('/events/payment/chapa/callback')),
        ));
        try {
          await _service.verifyChapa(tour: false, txRef: res['tx_ref']?.toString() ?? '', bookingId: id);
        } catch (e) {
          if (mounted) showFiraActionError(context, e);
        }
      }
      if (!mounted) return;
      if (paid && _method == 'bank_transfer') {
        ScaffoldMessenger.of(context)
          ..hideCurrentSnackBar()
          ..showSnackBar(SnackBar(content: Text(l10n.bookingSlipSent)));
      }
      Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => TicketViewScreen(bookingId: id)));
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    final e = widget.event;
    String? required(String? v) => (v == null || v.trim().isEmpty) ? l10n.fieldRequired : null;
    final soldOut = _maxQuantity == 0;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.eventRegisterTitle)),
      body: SafeArea(
        child: Form(
          key: _formKey,
          child: ListView(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
            children: [
              Card(
                child: ListTile(
                  title: Text(e.title, style: const TextStyle(fontWeight: FontWeight.w700)),
                  subtitle: Text([VerificationLabels.date(context, e.startDate), if ((e.location ?? '').isNotEmpty) e.location!].join(' · ')),
                ),
              ),
              const SizedBox(height: 12),
              Text(l10n.eventTickets, style: const TextStyle(fontWeight: FontWeight.w700)),
              RadioGroup<String>(
                groupValue: _type,
                onChanged: (v) => v == null ? null : setState(() => _type = v),
                child: Column(children: [
                  for (final t in _tickets)
                    RadioListTile<String>(
                      key: Key('ticket-${t.type}'),
                      value: t.type,
                      enabled: t.eligible,
                      contentPadding: EdgeInsets.zero,
                      title: Text('${l10n.translate('eventTicket_${t.type}')} · ${t.price > 0 ? _money(t.price) : l10n.free}'),
                      subtitle: Text(t.type == 'member' ? (t.eligible ? l10n.eventTicketMemberSub : l10n.eventTicketMemberOnly) : l10n.eventTicketGeneralSub),
                    ),
                ]),
              ),
              Row(children: [
                Expanded(
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text(l10n.eventQuantity, style: const TextStyle(fontWeight: FontWeight.w700)),
                    if (_flow.seatsLeft != null) Text(l10n.translate('eventSeatsLeft', {'n': _flow.seatsLeft}), style: TextStyle(color: fira.mutedText, fontSize: 12)),
                  ]),
                ),
                IconButton(key: const Key('qty-minus'), icon: const Icon(Icons.remove_circle_outline), onPressed: _quantity > 1 ? () => setState(() => _quantity--) : null),
                Text('$_quantity', key: const Key('qty'), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
                IconButton(key: const Key('qty-plus'), icon: const Icon(Icons.add_circle_outline), onPressed: _quantity < _maxQuantity ? () => setState(() => _quantity++) : null),
              ]),
              Row(children: [
                Expanded(child: TextFormField(key: const Key('ev-first'), controller: _first, decoration: InputDecoration(labelText: l10n.firstName), validator: required)),
                const SizedBox(width: 12),
                Expanded(child: TextFormField(key: const Key('ev-last'), controller: _last, decoration: InputDecoration(labelText: l10n.lastName), validator: required)),
              ]),
              TextFormField(
                key: const Key('ev-phone'),
                controller: _phone,
                keyboardType: TextInputType.phone,
                decoration: InputDecoration(labelText: l10n.phoneNumber),
                validator: (v) => RegExp(r'^\+?[0-9 ()-]{7,30}$').hasMatch(v?.trim() ?? '') ? null : l10n.fieldRequired,
              ),
              TextFormField(key: const Key('ev-company'), controller: _company, maxLength: 200, decoration: InputDecoration(labelText: l10n.eventCompanyOptional)),
              if (_total > 0) ...[
                const SizedBox(height: 4),
                PaymentMethodPicker(
                  method: _method,
                  onMethod: (m) => setState(() => _method = m),
                  slip: _slip,
                  onSlip: (f) => setState(() => _slip = f),
                  bankReference: _bankRef,
                  showSlipError: _tried,
                  pickSlip: widget.pickSlip,
                ),
              ],
              Card(
                key: const Key('event-summary'),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(children: [
                    PriceLine(label: '$_quantity · ${l10n.translate('eventTicket_$_type')}', value: _ticket.price > 0 ? _money(_ticket.price * _quantity) : l10n.free),
                    if (_fee > 0) PriceLine(label: l10n.eventServiceFee, value: _money(_fee * _quantity)),
                    const Divider(),
                    PriceLine(label: l10n.sampleTotal, value: _total > 0 ? _money(_total) : l10n.free, bold: true),
                  ]),
                ),
              ),
              if ((_flow.refundPolicy ?? '').isNotEmpty)
                Padding(
                  padding: const EdgeInsets.only(top: 4),
                  child: Text(l10n.translate('eventRefunds', {'policy': _flow.refundPolicy}), key: const Key('event-refunds'), style: TextStyle(color: fira.mutedText, fontSize: 12)),
                ),
              const SizedBox(height: 12),
              FilledButton(
                key: const Key('event-pay'),
                onPressed: _sending || soldOut ? null : _submit,
                child: _sending
                    ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2))
                    : Text(soldOut ? l10n.eventSoldOut : (_total > 0 ? l10n.eventPayAndGetTicket : l10n.eventGetFreeTicket)),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
