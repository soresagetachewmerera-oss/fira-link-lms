import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../models/booking_flow_model.dart';
import '../../models/tour.dart';
import '../../services/booking_service.dart';
import '../../services/upload_file.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/booking_flow_widgets.dart';
import '../../widgets/fira_error_state.dart';
import '../../widgets/verification_labels.dart';
import '../common/webview_screen.dart';
import 'tour_booking_confirmation_screen.dart';

/// Book a tour (flow "Book your tour"): one of the next dates with places left, guests, lead guest,
/// special requests, Chapa or bank transfer. A deposit holds the places; the balance is due before
/// the tour and paid in the app.
class TourBookingScreen extends StatefulWidget {
  const TourBookingScreen({required this.tour, super.key, this.service, this.pickSlip});

  final Tour tour;
  final BookingService? service;
  final PickSlipFn? pickSlip;

  @override
  State<TourBookingScreen> createState() => _TourBookingScreenState();
}

class _TourBookingScreenState extends State<TourBookingScreen> {
  late final BookingService _service = widget.service ?? BookingService();
  final _formKey = GlobalKey<FormState>();
  final _first = TextEditingController();
  final _last = TextEditingController();
  final _phone = TextEditingController();
  final _requests = TextEditingController();
  final _bankRef = TextEditingController();

  late TourDate? _date = widget.tour.flow.nextDates.where((d) => d.placesLeft > 0).firstOrNull;
  int _guests = 1;
  String _method = 'chapa';
  UploadFile? _slip;
  bool _tried = false;
  bool _sending = false;

  @override
  void dispose() {
    for (final c in [_first, _last, _phone, _requests, _bankRef]) {
      c.dispose();
    }
    super.dispose();
  }

  String _money(double v) => bookingMoney(widget.tour.currency, v);

  String _iso(DateTime d) => '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  Future<void> _submit() async {
    setState(() => _tried = true);
    final paid = widget.tour.price > 0;
    if (!(_formKey.currentState?.validate() ?? false) || _date == null) return;
    if (paid && _method == 'bank_transfer' && _slip == null) return;
    final l10n = context.l10n;
    final split = widget.tour.flow.split(widget.tour.price, _guests, _date!.date);
    setState(() => _sending = true);
    try {
      final res = await _service.bookTour(
        widget.tour.id,
        {
          'first_name': _first.text.trim(),
          'last_name': _last.text.trim(),
          'phone': _phone.text.trim(),
          'participants': _guests,
          'schedule_date': _iso(_date!.date),
          'special_requests': _requests.text.trim(),
          'payment_method': paid ? _method : 'free',
          if (_bankRef.text.trim().isNotEmpty) 'bank_reference': _bankRef.text.trim(),
        },
        slip: paid && _method == 'bank_transfer' ? _slip : null,
      );
      final booking = res['booking'] is Map ? res['booking'] as Map : const {};
      final id = int.tryParse('${booking['id'] ?? res['booking_id']}') ?? 0;
      if (res['payment_required'] == true && res['checkout_url'] != null && mounted) {
        await Navigator.of(context).push(MaterialPageRoute(
          builder: (_) => WebViewScreen(url: res['checkout_url'].toString(), title: 'Chapa', shouldExit: (u) => u.contains('/tours/payment/chapa/callback')),
        ));
        try {
          await _service.verifyChapa(tour: true, txRef: res['tx_ref']?.toString() ?? '', bookingId: id);
        } catch (e) {
          if (mounted) showFiraActionError(context, e);
        }
      }
      if (!mounted) return;
      if (_method == 'bank_transfer' && paid) {
        ScaffoldMessenger.of(context)
          ..hideCurrentSnackBar()
          ..showSnackBar(SnackBar(content: Text(l10n.bookingSlipSent)));
      }
      Navigator.of(context).pushReplacement(MaterialPageRoute(
        builder: (_) => TourBookingConfirmationScreen(
          bookingId: id,
          reference: booking['reference']?.toString() ?? '',
          tourTitle: widget.tour.title,
          participants: _guests,
          totalAmount: split.total,
          currency: widget.tour.currency,
        ),
      ));
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
    final tour = widget.tour;
    final flow = tour.flow;
    final date = _date;
    final split = date == null ? null : flow.split(tour.price, _guests, date.date);
    String? required(String? v) => (v == null || v.trim().isEmpty) ? l10n.fieldRequired : null;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.tourBookTitle)),
      body: SafeArea(
        child: Form(
          key: _formKey,
          child: ListView(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
            children: [
              Card(
                child: ListTile(
                  title: Text(tour.title, style: const TextStyle(fontWeight: FontWeight.w700)),
                  subtitle: Text('${flow.region ?? tour.destination} · ${l10n.translate('tourDays', {'n': tour.durationDays})}'),
                ),
              ),
              const SizedBox(height: 12),
              Text(l10n.tourDate, style: const TextStyle(fontWeight: FontWeight.w700)),
              const SizedBox(height: 6),
              if (flow.nextDates.isEmpty)
                Text(l10n.tourNoDates, key: const Key('tour-no-dates'), style: TextStyle(color: fira.mutedText))
              else
                Wrap(spacing: 8, runSpacing: 8, children: [
                  for (final d in flow.nextDates)
                    ChoiceChip(
                      key: Key('tour-date-${_iso(d.date)}'),
                      label: Text(VerificationLabels.date(context, d.date)),
                      selected: _date?.date == d.date,
                      onSelected: d.placesLeft == 0
                          ? null
                          : (_) => setState(() {
                                _date = d;
                                if (_guests > d.placesLeft) _guests = d.placesLeft;
                              }),
                    ),
                ]),
              if (_tried && date == null) Text(l10n.tourChooseDate, style: TextStyle(color: fira.danger)),
              const SizedBox(height: 12),
              Row(children: [
                Expanded(
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text(l10n.tourGuests, style: const TextStyle(fontWeight: FontWeight.w700)),
                    if (date != null) Text(l10n.translate('tourPlacesLeft', {'n': date.placesLeft}), style: TextStyle(color: fira.mutedText, fontSize: 12)),
                  ]),
                ),
                IconButton(key: const Key('guests-minus'), tooltip: 'Decrease guest count', icon: const Icon(Icons.remove_circle_outline), onPressed: _guests > 1 ? () => setState(() => _guests--) : null),
                Text('$_guests', key: const Key('guests-count'), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
                IconButton(
                  key: const Key('guests-plus'),
                  tooltip: 'Increase guest count',
                  icon: const Icon(Icons.add_circle_outline),
                  onPressed: date != null && _guests < date.placesLeft ? () => setState(() => _guests++) : null,
                ),
              ]),
              const SizedBox(height: 8),
              Text(l10n.tourLeadGuest, style: const TextStyle(fontWeight: FontWeight.w700)),
              Row(children: [
                Expanded(child: TextFormField(key: const Key('tour-first'), controller: _first, decoration: InputDecoration(labelText: l10n.firstName), validator: required)),
                const SizedBox(width: 12),
                Expanded(child: TextFormField(key: const Key('tour-last'), controller: _last, decoration: InputDecoration(labelText: l10n.lastName), validator: required)),
              ]),
              TextFormField(
                key: const Key('tour-phone'),
                controller: _phone,
                keyboardType: TextInputType.phone,
                decoration: InputDecoration(labelText: l10n.phoneNumber, hintText: '+251 9…'),
                validator: (v) => RegExp(r'^\+?[0-9 ()-]{7,30}$').hasMatch(v?.trim() ?? '') ? null : l10n.fieldRequired,
              ),
              TextFormField(
                key: const Key('tour-requests'),
                controller: _requests,
                maxLines: 2,
                maxLength: 1000,
                decoration: InputDecoration(labelText: l10n.tourSpecialRequests),
              ),
              if (tour.price > 0) ...[
                const SizedBox(height: 8),
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
              if (split != null) ...[
                const SizedBox(height: 8),
                Card(
                  key: const Key('tour-split'),
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(children: [
                      PriceLine(label: l10n.translate('tourGuestsLine', {'n': _guests}), value: tour.price > 0 ? _money(split.total) : l10n.free),
                      if (split.balance > 0) ...[
                        PriceLine(label: l10n.translate('tourPayNow', {'pct': flow.depositPercent}), value: _money(split.deposit), bold: true),
                        PriceLine(label: l10n.translate('tourBalanceDue', {'date': VerificationLabels.date(context, split.balanceDue)}), value: _money(split.balance)),
                      ] else if (tour.price > 0)
                        PriceLine(label: l10n.tourPayInFull, value: _money(split.total), bold: true),
                    ]),
                  ),
                ),
              ],
              PayOnlyHereCard(text: l10n.tourNeverPayHost),
              const SizedBox(height: 12),
              FilledButton(
                key: const Key('tour-confirm'),
                onPressed: _sending || flow.nextDates.isEmpty ? null : _submit,
                child: _sending ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2)) : Text(l10n.tourConfirmBooking),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
