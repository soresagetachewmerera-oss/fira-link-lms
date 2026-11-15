import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../models/organization_model.dart';
import '../../router/app_router.dart';
import '../../services/lot_service.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import '../../widgets/trade_labels.dart';
import '../../widgets/verification_labels.dart';
import '../../widgets/workspace_status_pill.dart';
import 'payout_account_screen.dart';
import 'quote_request_screen.dart';
import 'ship_sample_screen.dart';
import 'widgets/seller_lots_section.dart';

/// Seller home (business workspace): the business and its status, counts, the to-do list
/// (samples to seal and ship, requests to answer) and My lots (with the hidden-from-buyers
/// banner and evidence to renew, from [SellerLotsSection]).
class SellerHomeScreen extends StatefulWidget {
  const SellerHomeScreen({super.key, this.service});

  final LotService? service;

  @override
  State<SellerHomeScreen> createState() => _SellerHomeScreenState();
}

class _SellerHomeScreenState extends State<SellerHomeScreen> {
  late final LotService _service = widget.service ?? LotService();
  Map<String, dynamic>? _home;
  Object? _error;
  int _lotsKey = 0;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _error = null);
    try {
      final h = await _service.home();
      if (mounted) {
        setState(() {
          _home = h;
          _lotsKey++;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  Future<void> _push(Widget screen) async {
    await Navigator.of(context).push(MaterialPageRoute(builder: (_) => screen));
    if (mounted) _load();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final business = _home?['business'] is Map ? _home!['business'] as Map : null;
    return Scaffold(
      appBar: AppBar(
        title: Text(business?['name']?.toString() ?? l10n.sellerHomeTitle),
        actions: [
          IconButton(
            key: const Key('seller-home-payout'),
            icon: const Icon(Icons.account_balance_outlined),
            tooltip: l10n.payoutTitle,
            onPressed: () => _push(const PayoutAccountScreen()),
          ),
          IconButton(
            icon: const Icon(Icons.bolt),
            tooltip: l10n.sellerHomePlans,
            onPressed: () => Navigator.pushNamed(context, AppRouter.marketplacePricing),
          ),
          IconButton(
            key: const Key('seller-home-portal'),
            icon: const Icon(Icons.tune),
            tooltip: l10n.sellerHomeTools,
            onPressed: () => Navigator.pushNamed(context, AppRouter.marketplaceSellerPortal),
          ),
        ],
      ),
      body: _error != null
          ? FiraErrorState(error: _error, onRetry: _load)
          : _home == null
              ? const Center(child: CircularProgressIndicator())
              : RefreshIndicator(onRefresh: _load, child: _body(context, _home!)),
    );
  }

  Widget _count(BuildContext context, Object? n, String label) => Expanded(
        child: Column(children: [
          Text('${n ?? 0}', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 20)),
          Text(label, textAlign: TextAlign.center, style: TextStyle(color: context.fira.mutedText, fontSize: 12)),
        ]),
      );

  Widget _body(BuildContext context, Map<String, dynamic> h) {
    final l10n = context.l10n;
    final business = h['business'] as Map? ?? const {};
    final counts = h['counts'] as Map? ?? const {};
    final todo = (h['todo'] as List? ?? const []).whereType<Map>().where((t) => t['kind'] != 'renew_evidence').toList();

    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
      children: [
        Row(children: [
          Expanded(
            child: Text(business['name']?.toString() ?? '', style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700)),
          ),
          WorkspaceStatusPill(status: WorkspaceStatus.fromApi(business['status']?.toString())),
        ]),
        const SizedBox(height: 12),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Row(children: [
              _count(context, counts['active_lots'], l10n.sellerHomeActiveLots),
              _count(context, counts['open_requests'], l10n.sellerHomeOpenRequests),
              _count(context, counts['samples_to_send'], l10n.sellerHomeSamplesToSend),
            ]),
          ),
        ),
        if (todo.isNotEmpty) ...[
          const SizedBox(height: 16),
          Text(l10n.lotsToDo, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
          for (final t in todo) _todo(context, t),
        ],
        const SizedBox(height: 16),
        // Hidden-from-buyers banner, evidence to renew and My lots.
        SellerLotsSection(key: ValueKey(_lotsKey), service: _service),
      ],
    );
  }

  Widget _todo(BuildContext context, Map t) {
    final l10n = context.l10n;
    final fira = context.fira;
    String date(Object? v) => VerificationLabels.date(context, v == null ? null : DateTime.tryParse(v.toString()));

    if (t['kind'] == 'ship_sample') {
      final id = int.tryParse('${t['sample_id']}') ?? 0;
      return Card(
        key: Key('todo-sample-$id'),
        child: ListTile(
          onTap: () => _push(ShipSampleScreen(sampleId: id)),
          title: Text(l10n.translate('sellerHomePrepareSample', {'buyer': t['buyer'] ?? ''}), style: const TextStyle(fontWeight: FontWeight.w600)),
          subtitle: Text('${t['lot'] ?? ''} · ${l10n.translate('sampleGrams', {'n': t['grams'] ?? 250})} · ${l10n.translate('sellerHomeDue', {'date': date(t['due'])})}'),
          trailing: Text(l10n.sealAndShip, style: TextStyle(color: fira.brandPrimary, fontWeight: FontWeight.w700)),
        ),
      );
    }
    final id = int.tryParse('${t['rfq_id']}') ?? 0;
    final volume = TradeLabels.volume(l10n, double.tryParse('${t['volume_value']}') ?? 0, t['volume_unit']?.toString() ?? 'bags');
    return Card(
      key: Key('todo-request-$id'),
      child: ListTile(
        onTap: () => _push(QuoteRequestScreen(rfqId: id, lots: _service)),
        title: Text(l10n.translate('sellerHomeAnswerRequest', {'title': t['title'] ?? ''}), style: const TextStyle(fontWeight: FontWeight.w600)),
        subtitle: Text([
          volume,
          if (t['destination'] != null) t['destination'].toString(),
          if (t['closes'] != null) l10n.translate('sellerHomeCloses', {'date': date(t['closes'])}),
        ].join(' · ')),
        trailing: Text(l10n.sellerHomeQuote, style: TextStyle(color: fira.brandPrimary, fontWeight: FontWeight.w700)),
      ),
    );
  }
}
