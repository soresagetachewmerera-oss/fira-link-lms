import 'package:flutter/material.dart';

import '../../../l10n/app_localizations.dart';
import '../../../models/market_overview.dart';
import '../../../services/market_data_service.dart';
import '../../../theme/fira_colors.dart';
import '../../../widgets/home_skeletons.dart';
import '../../../widgets/market/market_ui.dart';
import '../../coffee_toolkit/coffee_market_screen.dart';

/// Home "Coffee market" card (replaces the Calculate profit tile). Shows the featured benchmark
/// with trend word + arrow, a small line, price and unit, freshness and source. States: loading,
/// Bullish/Bearish/Sideways/Not enough data, Stale, Unavailable with Try again. Works for guests.
/// Background is fira.brandDeep: the drawn #140526 is not a token, so it is not used.
class MarketTrendCard extends StatefulWidget {
  const MarketTrendCard({super.key, this.service});

  final MarketDataService? service;

  @override
  State<MarketTrendCard> createState() => _MarketTrendCardState();
}

class _MarketTrendCardState extends State<MarketTrendCard> {
  late final MarketDataService _service = widget.service ?? MarketDataService();
  MarketRefresher? _refresher;
  MarketSnapshot? _snapshot;
  List<MarketPoint> _points = const [];
  bool _loading = true;
  bool _failed = false;

  @override
  void initState() {
    super.initState();
    _load();
    _refresher = MarketRefresher(onTick: _load, isVisible: _isVisible)..start();
  }

  bool _isVisible() {
    if (!mounted) return false;
    final route = ModalRoute.of(context);
    return TickerMode.valuesOf(context).enabled && (route?.isCurrent ?? true);
  }

  Future<bool> _load() async {
    try {
      final snapshot = await _service.fetchOverview();
      List<MarketPoint> points = const [];
      final featured = featuredBenchmark(snapshot.overview);
      if (featured != null && !snapshot.fromCache) {
        try {
          points = (await _service.fetchSeries(featured.symbol, '1M')).points;
        } catch (_) {
          points = const []; // no line, the price itself is still sourced
        }
      }
      if (!mounted) return true;
      setState(() {
        _snapshot = snapshot;
        _points = points;
        _loading = false;
        _failed = false;
      });
      return !snapshot.fromCache;
    } catch (_) {
      if (!mounted) return false;
      setState(() {
        _loading = false;
        _failed = true;
      });
      return false;
    }
  }

  Future<void> _retry() async {
    setState(() => _loading = true);
    _refresher?.reportResult(await _load());
  }

  @override
  void dispose() {
    _refresher?.dispose();
    super.dispose();
  }

  void _open() => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const CoffeeMarketScreen()));

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final l10n = context.l10n;
    final onDeep = Theme.of(context).colorScheme.onPrimary;
    final muted = onDeep.withValues(alpha: .78);
    final featured = _snapshot == null ? null : featuredBenchmark(_snapshot!.overview);

    Widget body;
    String semantics;
    if (_loading && _snapshot == null) {
      semantics = l10n.marketLoading;
      body = BaseShimmer(
        child: Container(decoration: BoxDecoration(color: fira.brandTint, borderRadius: BorderRadius.circular(16))),
      );
    } else if (_failed || featured == null) {
      semantics = '${l10n.marketCardTitle}. ${l10n.marketUnavailable}. ${l10n.marketUnavailableHint}';
      body = Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _title(l10n, onDeep),
          const Spacer(),
          MarketStatusLine(icon: Icons.block_rounded, label: l10n.marketUnavailable, color: onDeep, fontSize: 13),
          const SizedBox(height: 4),
          Text(l10n.marketUnavailableHint, maxLines: 3, overflow: TextOverflow.ellipsis, style: TextStyle(color: muted, fontSize: 11.5, height: 1.3)),
          const SizedBox(height: 6),
          SizedBox(
            height: 44,
            child: TextButton.icon(
              onPressed: _retry,
              style: TextButton.styleFrom(foregroundColor: onDeep, padding: EdgeInsets.zero, minimumSize: const Size(44, 44)),
              icon: const Icon(Icons.refresh_rounded, size: 18),
              label: Text(l10n.marketTryAgain),
            ),
          ),
        ],
      );
    } else {
      final trend = MarketTrendVisual.of(context, featured.trend, neutral: onDeep);
      final fresh = MarketFreshnessVisual.of(context, featured, neutral: muted);
      final unit = marketUnitLabel(l10n, featured.currency, featured.unit);
      final price = formatMarketValue(featured.value!);
      final stale = featured.freshness == MarketFreshness.stale;
      semantics = '${l10n.marketCardTitle}. ${featured.name}. ${trend.label}. $price $unit. ${fresh.label}. ${l10n.marketSourceLabel(featured.source.attribution)}';
      body = Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _title(l10n, onDeep),
          const SizedBox(height: 6),
          MarketStatusLine(icon: trend.icon, label: trend.label, color: stale ? muted : onDeep, fontSize: 12.5),
          const Spacer(),
          if (_points.length >= 2 && !stale)
            SizedBox(
              height: 26,
              width: double.infinity,
              child: CustomPaint(painter: MarketSparklinePainter(_points, onDeep.withValues(alpha: .9))),
            ),
          const SizedBox(height: 6),
          FittedBox(
            fit: BoxFit.scaleDown,
            alignment: Alignment.centerLeft,
            child: Text.rich(
              TextSpan(children: [
                TextSpan(text: price, style: TextStyle(color: onDeep, fontSize: 22, fontWeight: FontWeight.w800)),
                TextSpan(text: '  $unit', style: TextStyle(color: muted, fontSize: 11.5, fontWeight: FontWeight.w600)),
              ]),
            ),
          ),
          const SizedBox(height: 4),
          MarketStatusLine(
            icon: fresh.icon,
            label: stale && _snapshot!.fromCache
                ? '${l10n.marketFreshStale} · ${formatMarketTime(_snapshot!.receivedAt)}'
                : fresh.label,
            color: stale ? onDeep : muted,
            textColor: stale ? onDeep : muted,
            fontSize: 11,
          ),
          const SizedBox(height: 2),
          Text(featured.source.attribution, maxLines: 1, overflow: TextOverflow.ellipsis, style: TextStyle(color: muted, fontSize: 11)),
        ],
      );
    }

    return Semantics(
      button: true,
      label: semantics,
      excludeSemantics: true,
      child: Material(
        color: fira.brandDeep,
        borderRadius: BorderRadius.circular(16),
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: _open,
          child: Padding(padding: const EdgeInsets.all(14), child: body),
        ),
      ),
    );
  }

  Widget _title(AppLocalizations l10n, Color color) => Row(
        children: [
          Icon(Icons.show_chart_rounded, size: 18, color: color),
          const SizedBox(width: 6),
          Expanded(
            child: Text(l10n.marketCardTitle, maxLines: 1, overflow: TextOverflow.ellipsis, style: TextStyle(color: color, fontSize: 13.5, fontWeight: FontWeight.w700)),
          ),
        ],
      );
}
