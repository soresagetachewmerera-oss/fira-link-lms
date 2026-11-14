import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../services/api_exception.dart';
import '../../services/sample_service.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import 'sample_tracking_screen.dart';

/// Request a sample of a published lot. The buyer ships on their own courier account; the delivery
/// contact is shown to the seller only on this sample, once it is paid (or free).
class RequestSampleScreen extends StatefulWidget {
  const RequestSampleScreen({
    super.key,
    required this.listingId,
    required this.lotTitle,
    this.lotCode,
    this.sellerName,
    this.feeUsd = 0,
    this.service,
  });

  final int listingId;
  final String lotTitle;
  final String? lotCode;
  final String? sellerName;
  final double feeUsd;
  final SampleService? service;

  @override
  State<RequestSampleScreen> createState() => _RequestSampleScreenState();
}

class _RequestSampleScreenState extends State<RequestSampleScreen> {
  late final SampleService _service = widget.service ?? SampleService();
  final _formKey = GlobalKey<FormState>();
  final _company = TextEditingController();
  final _address = TextEditingController();
  final _country = TextEditingController();
  final _phone = TextEditingController();
  final _account = TextEditingController();
  int _grams = 250;
  String _carrier = 'dhl';
  bool _sending = false;

  @override
  void dispose() {
    for (final c in [_company, _address, _country, _phone, _account]) {
      c.dispose();
    }
    super.dispose();
  }

  String? _required(String? v) => (v == null || v.trim().isEmpty) ? context.l10n.captureRequired : null;

  String _money(double v) => v <= 0 ? context.l10n.sampleFree : '\$${v.toStringAsFixed(2)}';

  Future<void> _send() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    final l10n = context.l10n;
    setState(() => _sending = true);
    try {
      final s = await _service.request({
        'listing_id': widget.listingId,
        'quantity_grams': _grams,
        'delivery_company': _company.text.trim(),
        'delivery_address': _address.text.trim(),
        'delivery_country': _country.text.trim(),
        'delivery_phone': _phone.text.trim(),
        'courier_carrier': _carrier,
        'courier_account': _account.text.trim(),
      });
      if (!mounted) return;
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(content: Text(l10n.sampleRequested)));
      Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => SampleTrackingScreen(sampleId: s.id, service: _service)));
    } on ApiException catch (e) {
      if (mounted) showFiraActionError(context, e);
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

    return Scaffold(
      appBar: AppBar(title: Text(l10n.sampleRequestTitle)),
      body: SafeArea(
        child: Form(
          key: _formKey,
          child: ListView(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
            children: [
              Card(
                child: ListTile(
                  title: Text(widget.lotTitle, style: const TextStyle(fontWeight: FontWeight.w700)),
                  subtitle: Text(l10n.translate('sampleLotLine', {'business': widget.sellerName ?? '', 'code': widget.lotCode ?? ''})),
                ),
              ),
              const SizedBox(height: 12),
              Text(l10n.sampleSize, style: const TextStyle(fontWeight: FontWeight.w600)),
              const SizedBox(height: 6),
              Wrap(spacing: 8, children: [
                for (final g in const [250, 500])
                  ChoiceChip(key: Key('size-$g'), label: Text(l10n.translate('sampleGrams', {'n': g})), selected: _grams == g, onSelected: (_) => setState(() => _grams = g)),
              ]),
              const SizedBox(height: 12),
              TextFormField(key: const Key('sample-company'), controller: _company, maxLength: 200, decoration: InputDecoration(labelText: l10n.sampleCompany), validator: _required),
              TextFormField(key: const Key('sample-address'), controller: _address, maxLines: 2, maxLength: 500, decoration: InputDecoration(labelText: l10n.sampleAddress), validator: _required),
              TextFormField(key: const Key('sample-country'), controller: _country, maxLength: 100, decoration: InputDecoration(labelText: l10n.sampleCountry), validator: _required),
              TextFormField(
                key: const Key('sample-phone'),
                controller: _phone,
                keyboardType: TextInputType.phone,
                maxLength: 30,
                decoration: InputDecoration(labelText: l10n.samplePhone),
                validator: (v) => RegExp(r'^\+?[0-9 ()-]{7,30}$').hasMatch(v?.trim() ?? '') ? null : l10n.captureRequired,
              ),
              const SizedBox(height: 8),
              Text(l10n.sampleShipping, style: const TextStyle(fontWeight: FontWeight.w700)),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(l10n.sampleUseMyCourier, style: const TextStyle(fontWeight: FontWeight.w600)),
                      Text(l10n.sampleUseMyCourierBody, style: TextStyle(color: fira.mutedText)),
                      const SizedBox(height: 8),
                      Wrap(spacing: 8, children: [
                        for (final (value, label) in const [('dhl', 'DHL'), ('fedex', 'FedEx')])
                          ChoiceChip(key: Key('carrier-$value'), label: Text(label), selected: _carrier == value, onSelected: (_) => setState(() => _carrier = value)),
                      ]),
                      TextFormField(
                        key: const Key('sample-account'),
                        controller: _account,
                        maxLength: 40,
                        decoration: InputDecoration(labelText: l10n.sampleCourierAccount),
                        validator: _required,
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 8),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(children: [
                    _row(l10n.sampleFee, _money(widget.feeUsd)),
                    _row(l10n.sampleShipping, l10n.sampleShippingOnAccount),
                    const Divider(),
                    _row(l10n.sampleTotal, _money(widget.feeUsd), bold: true),
                  ]),
                ),
              ),
              Card(
                color: fira.brandTint,
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Text(l10n.translate('sampleRetainedNote', {'code': widget.lotCode ?? ''}), style: TextStyle(color: fira.onSurface)),
                ),
              ),
              const SizedBox(height: 12),
              FilledButton(
                key: const Key('sample-send'),
                onPressed: _sending ? null : _send,
                child: _sending ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2)) : Text(l10n.sampleSend),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _row(String label, String value, {bool bold = false}) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 4),
        child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Expanded(child: Text(label)),
          const SizedBox(width: 12),
          Flexible(child: Text(value, textAlign: TextAlign.end, style: TextStyle(fontWeight: bold ? FontWeight.w700 : FontWeight.w500))),
        ]),
      );
}
