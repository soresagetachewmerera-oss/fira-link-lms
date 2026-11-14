import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../models/lot_model.dart';
import '../../models/trade_model.dart';
import '../../services/api_exception.dart';
import '../../services/lot_service.dart';
import '../../services/trade_service.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import '../../widgets/trade_labels.dart';
import '../../widgets/verification_labels.dart';
import 'widgets/quota_limit_sheet.dart';

/// Quote this request (QuoteRespond). A quote needs one of the business's published lots; the
/// buyer sees that lot's evidence level next to the price. Sending again updates the quote.
class QuoteRequestScreen extends StatefulWidget {
  const QuoteRequestScreen({super.key, required this.rfqId, this.trade, this.lots});

  final int rfqId;
  final TradeService? trade;
  final LotService? lots;

  @override
  State<QuoteRequestScreen> createState() => _QuoteRequestScreenState();
}

class _QuoteRequestScreenState extends State<QuoteRequestScreen> {
  late final TradeService _trade = widget.trade ?? TradeService();
  late final LotService _lotService = widget.lots ?? LotService();
  final _formKey = GlobalKey<FormState>();
  final _price = TextEditingController();
  final _volume = TextEditingController();
  final _validDays = TextEditingController(text: '14');
  final _note = TextEditingController();
  DateTime? _readyToShip;

  Rfq? _rfq;
  TradeQuote? _mine;
  bool _canQuote = false;
  List<SellerLot> _lots = [];
  int? _lotId;
  Object? _error;
  bool _loading = true;
  bool _sending = false;
  bool _tried = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    for (final c in [_price, _volume, _validDays, _note]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final r = await _trade.request(widget.rfqId);
      final lots = (await _lotService.getLots()).lots.where((l) => l.status == 'active' && l.visibleToBuyers).toList();
      if (!mounted) return;
      setState(() {
        _rfq = r.rfq;
        _mine = r.myQuote;
        _canQuote = r.canQuote;
        _lots = lots;
        final mine = r.myQuote;
        if (mine != null && mine.status == 'submitted') {
          _lotId = mine.lot?.id;
          _price.text = mine.pricePerLb.toStringAsFixed(2);
          _volume.text = '${mine.volumeBags}';
          _note.text = mine.note ?? '';
          _readyToShip = mine.readyToShip;
        } else if (lots.length == 1) {
          _lotId = lots.first.id;
        }
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e;
        _loading = false;
      });
    }
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final d = await showDatePicker(
      context: context,
      initialDate: _readyToShip ?? now.add(const Duration(days: 14)),
      firstDate: DateTime(now.year, now.month, now.day),
      lastDate: DateTime(now.year + 1),
    );
    if (d != null) setState(() => _readyToShip = d);
  }

  Future<void> _send() async {
    final l10n = context.l10n;
    final formOk = _formKey.currentState?.validate() ?? false;
    setState(() => _tried = true);
    if (!formOk || _lotId == null || _readyToShip == null) return;
    setState(() => _sending = true);
    try {
      final d = _readyToShip!;
      await _trade.sendQuote(widget.rfqId, {
        'listing_id': _lotId,
        'price_usd_per_lb': double.parse(_price.text.trim()),
        'volume_bags': int.parse(_volume.text.trim()),
        'valid_days': int.parse(_validDays.text.trim()),
        'ready_to_ship': '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}',
        if (_note.text.trim().isNotEmpty) 'note': _note.text.trim(),
      });
      if (!mounted) return;
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(content: Text(l10n.quoteSent)));
      Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      if (!mounted) return;
      if (e.code == 'CONTACT_DETAILS') {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(l10n.tradeContactDetails)));
      } else if (e.code == 'limit_reached') {
        QuotaLimitSheet.show(context, {'feature': 'rfq_responses_per_month', 'message': e.serverMessage ?? ''});
      } else {
        showFiraActionError(context, e);
      }
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    return Scaffold(
      appBar: AppBar(title: Text(l10n.quoteTitle)),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? FiraErrorState(error: _error, onRetry: _load)
              : _body(context),
    );
  }

  Widget _body(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    final rfq = _rfq!;
    final submitted = _mine != null && _mine!.status == 'submitted';

    return Form(
      key: _formKey,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
        children: [
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(l10n.quoteBuyer, style: TextStyle(color: fira.mutedText)),
                  Row(children: [
                    Expanded(child: Text(rfq.buyer?.name ?? '', style: const TextStyle(fontWeight: FontWeight.w700))),
                    BusinessTierLabel(business: rfq.buyer),
                  ]),
                  const SizedBox(height: 8),
                  Text(rfq.title, style: const TextStyle(fontWeight: FontWeight.w600)),
                  Text([
                    l10n.translate('requestsSummary', {
                      'volume': TradeLabels.volume(l10n, rfq.volumeValue, rfq.volumeUnit),
                      'destination': rfq.destination,
                      'incoterm': rfq.incoterm,
                    }),
                    if (rfq.neededBy != null) l10n.translate('requestsNeededBy', {'date': VerificationLabels.date(context, rfq.neededBy)}),
                  ].join(' · ')),
                  const SizedBox(height: 4),
                  Text(
                    '${rfq.targetPrice != null ? l10n.translate('quoteTargetPrice', {'price': '\$${rfq.targetPrice!.toStringAsFixed(2)}'}) : l10n.quoteTargetNotShown}'
                    ' · ${rfq.sampleFirst ? l10n.quoteSampleFirstYes : l10n.quoteSampleFirstNo}',
                    style: TextStyle(color: fira.mutedText),
                  ),
                ],
              ),
            ),
          ),
          if (_mine != null) ...[
            const SizedBox(height: 8),
            Text(l10n.translate('quoteYourQuote', {'status': TradeLabels.quoteStatus(l10n, _mine!.status)}),
                key: const Key('my-quote-status'), style: TextStyle(color: fira.pending, fontWeight: FontWeight.w600)),
          ],
          if (!_canQuote) ...[
            const SizedBox(height: 16),
            Text(rfq.isOpen ? l10n.requestsSellersOnly : l10n.blockerRequestClosed, style: TextStyle(color: fira.mutedText)),
          ] else ...[
            const SizedBox(height: 16),
            Text(l10n.quoteAttachLot, style: const TextStyle(fontWeight: FontWeight.w700)),
            Text(l10n.quoteAttachLotHint, style: TextStyle(color: fira.mutedText, fontSize: 13)),
            const SizedBox(height: 8),
            if (_lots.isEmpty)
              Text(l10n.quoteNoLots, key: const Key('quote-no-lots'), style: TextStyle(color: fira.danger))
            else
              RadioGroup<int>(
                groupValue: _lotId,
                onChanged: (v) => setState(() => _lotId = v),
                child: Column(
                  children: [
                    for (final lot in _lots)
                      Card(
                        key: Key('quote-lot-${lot.id}'),
                        child: RadioListTile<int>(
                          value: lot.id,
                          title: Text(lot.title),
                          subtitle: Text(l10n.translate('quoteLotAvailable', {'bags': lot.totalQuantityBags ?? 0, 'code': lot.lotCode})),
                          secondary: LotLevelChip(level: lot.evidenceLevel),
                        ),
                      ),
                  ],
                ),
              ),
            if (_tried && _lotId == null && _lots.isNotEmpty) Text(l10n.captureRequired, style: TextStyle(color: fira.danger)),
            const SizedBox(height: 12),
            TextFormField(
              key: const Key('quote-price'),
              controller: _price,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: InputDecoration(labelText: l10n.quotePrice),
              validator: (v) => (double.tryParse(v?.trim() ?? '') ?? 0) >= 0.5 ? null : l10n.captureRequired,
            ),
            const SizedBox(height: 12),
            TextFormField(
              key: const Key('quote-volume'),
              controller: _volume,
              keyboardType: TextInputType.number,
              decoration: InputDecoration(labelText: l10n.quoteVolume),
              validator: (v) => (int.tryParse(v?.trim() ?? '') ?? 0) > 0 ? null : l10n.captureRequired,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _validDays,
              keyboardType: TextInputType.number,
              decoration: InputDecoration(labelText: l10n.quoteValidFor),
              validator: (v) {
                final n = int.tryParse(v?.trim() ?? '') ?? 0;
                return n >= 1 && n <= 60 ? null : l10n.captureRequired;
              },
            ),
            const SizedBox(height: 12),
            InkWell(
              key: const Key('quote-ready'),
              onTap: _pickDate,
              child: InputDecorator(
                decoration: InputDecoration(
                  labelText: l10n.quoteReadyToShip,
                  errorText: _tried && _readyToShip == null ? l10n.captureRequired : null,
                  suffixIcon: const Icon(Icons.calendar_today_outlined),
                ),
                child: Text(_readyToShip == null ? l10n.captureChooseDate : VerificationLabels.date(context, _readyToShip)),
              ),
            ),
            const SizedBox(height: 12),
            TextFormField(controller: _note, maxLines: 3, maxLength: 2000, decoration: InputDecoration(labelText: l10n.quoteNote)),
            Card(
              color: fira.brandTint,
              child: ListTile(
                leading: Icon(Icons.shield_outlined, color: fira.brandPrimary),
                title: Text(l10n.quoteKeepInside, style: const TextStyle(fontWeight: FontWeight.w700)),
                subtitle: Text(l10n.quoteKeepInsideBody),
              ),
            ),
            const SizedBox(height: 12),
            FilledButton(
              key: const Key('quote-send'),
              onPressed: _sending || _lots.isEmpty ? null : _send,
              child: _sending
                  ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2))
                  : Text(submitted ? l10n.quoteUpdate : l10n.quoteSend),
            ),
          ],
        ],
      ),
    );
  }
}
