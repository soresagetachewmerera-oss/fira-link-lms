import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../models/trade_model.dart';
import '../../services/deal_room_service.dart';
import '../../services/trade_service.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import '../../widgets/trade_labels.dart';
import '../../widgets/verification_labels.dart';
import '../deal_room/deal_room_hub_screen.dart';

/// Quotes received for one request. The server says which quotes can be accepted: never below
/// the ECTA minimum from a sourced price list, never from a seller who is not verified now.
class CompareQuotesScreen extends StatefulWidget {
  const CompareQuotesScreen({super.key, required this.rfqId, this.service, this.dealRooms});

  final int rfqId;
  final TradeService? service;
  final DealRoomService? dealRooms;

  @override
  State<CompareQuotesScreen> createState() => _CompareQuotesScreenState();
}

class _CompareQuotesScreenState extends State<CompareQuotesScreen> {
  late final TradeService _service = widget.service ?? TradeService();
  late final DealRoomService _dealRooms = widget.dealRooms ?? DealRoomService();
  Rfq? _rfq;
  bool _canDecide = false;
  List<TradeQuote> _quotes = [];
  bool _verifiedOnly = true;
  String? _minLevel;
  Object? _error;
  bool _loading = true;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = _rfq == null;
      _error = null;
    });
    try {
      final r = await _service.quotes(widget.rfqId, verifiedOnly: _verifiedOnly, minLevel: _minLevel);
      if (!mounted) return;
      setState(() {
        _rfq = r.rfq;
        _canDecide = r.canDecide;
        _quotes = r.quotes;
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

  Future<void> _accept(TradeQuote q) async {
    final l10n = context.l10n;
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        content: Text(l10n.compareAcceptConfirm),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: Text(l10n.cancel)),
          TextButton(key: const Key('confirm-accept'), onPressed: () => Navigator.pop(ctx, true), child: Text(l10n.compareAccept)),
        ],
      ),
    );
    if (ok != true) return;
    await _run(() => _service.accept(q.id));
  }

  Future<void> _run(Future<void> Function() action) async {
    setState(() => _busy = true);
    try {
      await action();
      await _load();
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _openDealRoom(TradeQuote q) async {
    setState(() => _busy = true);
    final res = await _dealRooms.openDealRoom(
      quotationId: q.id,
      title: _rfq?.title ?? '',
      targetValueUsd: q.totalValue,
      incoterm: _rfq?.incoterm ?? 'FOB',
    );
    if (!mounted) return;
    setState(() => _busy = false);
    if (res['success'] == true && res['deal_room'] != null) {
      Navigator.of(context).push(MaterialPageRoute(builder: (_) => DealRoomHubScreen(dealRoomId: res['deal_room'].id)));
    } else if (res['error'] != null) {
      showFiraActionError(context, res['error']);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.compareTitle),
        actions: [
          if (_canDecide && (_rfq?.isOpen ?? false))
            PopupMenuButton<String>(
              onSelected: (_) => _run(() => _service.closeRequest(widget.rfqId)),
              itemBuilder: (_) => [PopupMenuItem(value: 'close', child: Text(l10n.requestClose))],
            ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? FiraErrorState(error: _error, onRetry: _load)
              : RefreshIndicator(onRefresh: _load, child: _body(context)),
    );
  }

  Widget _body(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    final rfq = _rfq!;

    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
      children: [
        Card(
          child: ListTile(
            title: Text(rfq.title, style: const TextStyle(fontWeight: FontWeight.w700)),
            subtitle: Text([
              l10n.compareYourRfq,
              TradeLabels.volume(l10n, rfq.volumeValue, rfq.volumeUnit),
              rfq.incoterm,
              rfq.destination,
              if (rfq.neededBy != null) l10n.translate('requestsNeededBy', {'date': VerificationLabels.date(context, rfq.neededBy)}),
            ].join(' · ')),
            trailing: Text(TradeLabels.rfqStatus(l10n, rfq.status), style: TextStyle(color: fira.pending, fontWeight: FontWeight.w600)),
          ),
        ),
        const SizedBox(height: 8),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [
            FilterChip(
              key: const Key('compare-verified-only'),
              label: Text(l10n.compareVerifiedOnly),
              selected: _verifiedOnly,
              onSelected: (v) {
                setState(() => _verifiedOnly = v);
                _load();
              },
            ),
            for (final (value, label) in [(null, l10n.filtersAny), ('documented', l10n.filtersDocumentedPlus), ('lab_backed', l10n.filtersLabBackedPlus)])
              ChoiceChip(
                label: Text(label),
                selected: _minLevel == value,
                onSelected: (_) {
                  setState(() => _minLevel = value);
                  _load();
                },
              ),
            Chip(label: Text(l10n.compareSortPrice)),
          ],
        ),
        const SizedBox(height: 8),
        if (_quotes.isEmpty)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 32),
            child: Text(l10n.compareEmpty, textAlign: TextAlign.center, style: TextStyle(color: fira.mutedText)),
          ),
        for (final q in _quotes) _quoteCard(context, q),
        const SizedBox(height: 12),
        Text(l10n.compareRule, style: TextStyle(color: fira.mutedText, fontSize: 13)),
      ],
    );
  }

  Widget _stat(String label, String value) => Expanded(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: TextStyle(color: context.fira.mutedText, fontSize: 12)),
            Text(value, style: const TextStyle(fontWeight: FontWeight.w700)),
          ],
        ),
      );

  Widget _quoteCard(BuildContext context, TradeQuote q) {
    final l10n = context.l10n;
    final fira = context.fira;
    final ref = q.reference;
    String date(DateTime? d) => VerificationLabels.date(context, d);
    final blocker = TradeLabels.blocker(l10n, q.blocker);

    return Card(
      key: Key('quote-${q.id}'),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(child: Text(q.seller?.name ?? '', style: const TextStyle(fontWeight: FontWeight.w700))),
                Text(TradeLabels.quoteStatus(l10n, q.status), style: TextStyle(color: fira.mutedText, fontSize: 12)),
              ],
            ),
            BusinessTierLabel(business: q.seller),
            const SizedBox(height: 4),
            Row(children: [
              Text(l10n.translate('compareEvidenceLine', {'level': ''}).trim()),
              const SizedBox(width: 6),
              LotLevelChip(level: q.lot?.evidenceLevel ?? 'claimed'),
            ]),
            if (q.lot != null) Text('${q.lot!.title} · ${q.lot!.lotCode}', style: TextStyle(color: fira.mutedText, fontSize: 12)),
            const SizedBox(height: 12),
            Row(children: [
              _stat(l10n.comparePricePerLb, '\$${q.pricePerLb.toStringAsFixed(2)}'),
              _stat(l10n.compareTotalValue, TradeLabels.money(q.totalValue)),
              _stat(l10n.compareDispatch, q.dispatchDays == null ? '–' : l10n.translate('compareDays', {'n': q.dispatchDays})),
            ]),
            const SizedBox(height: 8),
            if (q.belowMinimum && ref != null)
              Text(
                l10n.translate('compareBelowMinimum', {'price': '\$${ref.minimum.toStringAsFixed(2)}', 'source': ref.source, 'date': date(ref.effectiveDate)}),
                key: Key('below-minimum-${q.id}'),
                style: TextStyle(color: fira.danger, fontWeight: FontWeight.w600),
              )
            else if (ref != null)
              Text(l10n.translate('compareReference', {'price': '\$${ref.minimum.toStringAsFixed(2)}', 'source': ref.source, 'date': date(ref.effectiveDate)}),
                  style: TextStyle(color: fira.mutedText, fontSize: 12))
            else
              Text(l10n.compareNoReference, style: TextStyle(color: fira.mutedText, fontSize: 12)),
            if (blocker != null && q.status == 'submitted') ...[
              const SizedBox(height: 4),
              Text(blocker, style: TextStyle(color: fira.danger)),
            ],
            if (q.note != null && q.note!.isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(q.note!),
            ],
            if (_canDecide && q.status == 'submitted') ...[
              const SizedBox(height: 12),
              Row(children: [
                Expanded(
                  child: OutlinedButton(
                    key: Key('decline-${q.id}'),
                    onPressed: _busy ? null : () => _run(() => _service.decline(q.id)),
                    child: Text(l10n.compareDecline),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: FilledButton(
                    key: Key('accept-${q.id}'),
                    onPressed: _busy || !q.canAccept ? null : () => _accept(q),
                    child: Text(l10n.compareAccept),
                  ),
                ),
              ]),
            ],
            if (_canDecide && q.status == 'accepted') ...[
              const SizedBox(height: 12),
              FilledButton.icon(
                key: Key('deal-room-${q.id}'),
                onPressed: _busy ? null : () => _openDealRoom(q),
                icon: const Icon(Icons.handshake_outlined),
                label: Text(l10n.compareCreateDealRoom),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
