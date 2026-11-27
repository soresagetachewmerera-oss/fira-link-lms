import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../l10n/app_localizations.dart';
import '../../models/market_watch.dart';
import '../../providers/auth_provider.dart';
import '../../providers/market_watch_provider.dart';
import '../../services/market_account_service.dart';
import '../../widgets/market/market_detail_actions.dart';
import '../../widgets/market/market_detail_sheet.dart';
import '../../widgets/market/market_watch_card.dart';

/// All coffee market rows (guests too), with pull-to-refresh. [initialKey] (from a price alert
/// notification) opens that instrument's detail once the rows are loaded.
class MarketWatchScreen extends StatefulWidget {
  const MarketWatchScreen({super.key, this.initialKey, this.provider});

  final String? initialKey;
  final MarketWatchProvider? provider;

  @override
  State<MarketWatchScreen> createState() => _MarketWatchScreenState();
}

class _MarketWatchScreenState extends State<MarketWatchScreen> {
  MarketWatchProvider? _provider;
  bool _opened = false;
  Set<String> _pinned = const {};

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_provider != null) return;
    try {
      _provider = widget.provider ?? Provider.of<MarketWatchProvider>(context, listen: false);
    } on ProviderNotFoundException {
      _provider = MarketWatchProvider();
    }
    _provider!.addListener(_maybeOpen);
    _loadPinned();
    WidgetsBinding.instance.addPostFrameCallback((_) => _maybeOpen());
  }

  Future<void> _loadPinned() async {
    bool signedIn = false;
    try {
      signedIn = context.read<AuthProvider>().isAuthenticated;
    } on ProviderNotFoundException {
      signedIn = false;
    }
    if (!signedIn) return;
    try {
      final keys = await MarketAccountService().watchlist();
      if (mounted) setState(() => _pinned = keys);
    } catch (_) {
      // The list still shows in its normal order.
    }
  }

  void _maybeOpen() {
    final key = widget.initialKey;
    final rows = _provider?.snapshot?.watch.rows;
    if (_opened || key == null || rows == null || !mounted) return;
    final MarketWatchRow? row = rows.where((r) => r.key == key && r.hasPrice).firstOrNull;
    if (row == null) return;
    _opened = true;
    showMarketDetailSheet(context, row, service: _provider!.service, actions: marketDetailActions);
  }

  @override
  void dispose() {
    _provider?.removeListener(_maybeOpen);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(context.l10n.marketScreenTitle)),
      body: RefreshIndicator(
        onRefresh: () => _provider!.load(),
        child: ListView(
          padding: const EdgeInsets.symmetric(vertical: 16),
          children: [MarketWatchCard(provider: _provider, homeOnly: false, detailActions: marketDetailActions, pinned: _pinned)],
        ),
      ),
    );
  }
}
