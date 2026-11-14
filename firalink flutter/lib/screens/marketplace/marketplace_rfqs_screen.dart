import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../models/trade_model.dart';
import '../../services/api_exception.dart';
import '../../services/trade_service.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import '../../widgets/trade_labels.dart';
import '../../widgets/verification_labels.dart';
import 'compare_quotes_screen.dart';
import 'create_rfq_screen.dart';
import 'quote_request_screen.dart';

/// Requests: open requests (verified seller businesses), my requests (buyers) and my quotes.
/// Who sees what is decided by the server; a refusal is shown as a plain message.
class MarketplaceRfqsScreen extends StatefulWidget {
  const MarketplaceRfqsScreen({super.key, this.service});

  final TradeService? service;

  @override
  State<MarketplaceRfqsScreen> createState() => _MarketplaceRfqsScreenState();
}

class _MarketplaceRfqsScreenState extends State<MarketplaceRfqsScreen> {
  late final TradeService _service = widget.service ?? TradeService();

  Future<void> _open(Widget screen) async {
    await Navigator.of(context).push(MaterialPageRoute(builder: (_) => screen));
    if (mounted) setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    return DefaultTabController(
      length: 3,
      child: Scaffold(
        appBar: AppBar(
          title: Text(l10n.marketRequests),
          bottom: TabBar(
            isScrollable: true,
            tabs: [Tab(text: l10n.requestsOpen), Tab(text: l10n.requestsMine), Tab(text: l10n.requestsMyQuotes)],
          ),
        ),
        floatingActionButton: FloatingActionButton.extended(
          key: const Key('post-request'),
          onPressed: () => _open(CreateRfqScreen(service: _service)),
          icon: const Icon(Icons.add),
          label: Text(l10n.marketPostRequest),
        ),
        body: TabBarView(
          children: [
            _RequestList(
              key: const Key('tab-open'),
              load: _service.openRequests,
              onTap: (r) => _open(QuoteRequestScreen(rfqId: r.id, trade: _service)),
              showBuyer: true,
            ),
            _RequestList(
              key: const Key('tab-mine'),
              load: _service.myRequests,
              onTap: (r) => _open(CompareQuotesScreen(rfqId: r.id, service: _service)),
            ),
            _MyQuotes(service: _service, onTap: (q) => _open(QuoteRequestScreen(rfqId: q.rfqId, trade: _service))),
          ],
        ),
      ),
    );
  }
}

class _RequestList extends StatefulWidget {
  const _RequestList({super.key, required this.load, required this.onTap, this.showBuyer = false});

  final Future<List<Rfq>> Function() load;
  final ValueChanged<Rfq> onTap;
  final bool showBuyer;

  @override
  State<_RequestList> createState() => _RequestListState();
}

class _RequestListState extends State<_RequestList> {
  List<Rfq>? _items;
  Object? _error;
  bool _sellersOnly = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final items = await widget.load();
      if (mounted) setState(() => _items = items);
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _sellersOnly = e.code == 'VERIFIED_SELLER_REQUIRED' || e.code == 'ORGANIZATION_REQUIRED';
        _error = _sellersOnly ? null : e;
        _items = _sellersOnly ? const [] : null;
      });
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    if (_error != null) return FiraErrorState(error: _error, onRetry: _load);
    if (_items == null) return const Center(child: CircularProgressIndicator());

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 96),
        children: [
          if (_sellersOnly)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 32),
              child: Text(l10n.requestsSellersOnly, key: const Key('sellers-only'), textAlign: TextAlign.center, style: TextStyle(color: fira.mutedText)),
            )
          else if (_items!.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 32),
              child: Text(l10n.requestsEmpty, textAlign: TextAlign.center, style: TextStyle(color: fira.mutedText)),
            ),
          for (final r in _items!)
            Card(
              key: Key('rfq-${r.id}'),
              child: ListTile(
                onTap: () => widget.onTap(r),
                title: Text(r.title, style: const TextStyle(fontWeight: FontWeight.w600)),
                subtitle: Text([
                  if (widget.showBuyer && r.buyer != null) r.buyer!.name,
                  l10n.translate('requestsSummary', {
                    'volume': TradeLabels.volume(l10n, r.volumeValue, r.volumeUnit),
                    'destination': r.destination,
                    'incoterm': r.incoterm,
                  }),
                  if (r.neededBy != null) l10n.translate('requestsNeededBy', {'date': VerificationLabels.date(context, r.neededBy)}),
                ].join('\n')),
                trailing: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(TradeLabels.rfqStatus(l10n, r.status), style: TextStyle(color: fira.pending, fontWeight: FontWeight.w600, fontSize: 12)),
                    if (!widget.showBuyer) Text(l10n.translate('requestsQuotesCount', {'n': r.quotesCount}), style: TextStyle(color: fira.mutedText, fontSize: 12)),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _MyQuotes extends StatefulWidget {
  const _MyQuotes({required this.service, required this.onTap});

  final TradeService service;
  final ValueChanged<TradeQuote> onTap;

  @override
  State<_MyQuotes> createState() => _MyQuotesState();
}

class _MyQuotesState extends State<_MyQuotes> {
  List<TradeQuote>? _items;
  Object? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final items = await widget.service.myQuotes();
      if (mounted) setState(() => _items = items);
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() => e.code == 'ORGANIZATION_REQUIRED' ? _items = const [] : _error = e);
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    if (_error != null) return FiraErrorState(error: _error, onRetry: _load);
    if (_items == null) return const Center(child: CircularProgressIndicator());

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 96),
        children: [
          if (_items!.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 32),
              child: Text(l10n.compareEmpty, textAlign: TextAlign.center, style: TextStyle(color: fira.mutedText)),
            ),
          for (final q in _items!)
            Card(
              child: ListTile(
                onTap: () => widget.onTap(q),
                title: Text(q.rfq?.title ?? '', style: const TextStyle(fontWeight: FontWeight.w600)),
                subtitle: Text('\$${q.pricePerLb.toStringAsFixed(2)} /lb · ${TradeLabels.volume(l10n, q.volumeBags.toDouble(), 'bags')} · ${q.lot?.lotCode ?? ''}'),
                trailing: Text(TradeLabels.quoteStatus(l10n, q.status), style: TextStyle(color: fira.pending, fontWeight: FontWeight.w600, fontSize: 12)),
              ),
            ),
        ],
      ),
    );
  }
}
