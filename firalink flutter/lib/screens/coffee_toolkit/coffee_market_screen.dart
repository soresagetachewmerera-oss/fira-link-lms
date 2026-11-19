import 'dart:math' as math;

import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../models/market_overview.dart';
import '../../services/market_data_service.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/home_skeletons.dart';
import '../../widgets/market/market_ui.dart';
import '../ai_chat/ai_chat_screen.dart';

/// Coffee market: sourced prices only (GET /api/market/overview and /series).
/// Every row shows its source, source time and freshness. Old data is labelled Stale, missing data
/// Unavailable; nothing is estimated or generated in the app. Public: works for guests.
class CoffeeMarketScreen extends StatefulWidget {
  const CoffeeMarketScreen({super.key, this.service});

  final MarketDataService? service;

  @override
  State<CoffeeMarketScreen> createState() => _CoffeeMarketScreenState();
}

class _CoffeeMarketScreenState extends State<CoffeeMarketScreen> {
  late final MarketDataService _service = widget.service ?? MarketDataService();
  MarketRefresher? _refresher;

  MarketSnapshot? _snapshot;
  bool _loading = true;
  bool _failed = false;

  String? _symbol;
  String _range = '1M';
  MarketSeriesData? _series;
  bool _seriesLoading = false;
  bool _seriesFailed = false;

  @override
  void initState() {
    super.initState();
    _load();
    _refresher = MarketRefresher(onTick: _load, isVisible: _isVisible)..start();
  }

  bool _isVisible() => mounted && (ModalRoute.of(context)?.isCurrent ?? true);

  @override
  void dispose() {
    _refresher?.dispose();
    super.dispose();
  }

  Future<bool> _load() async {
    try {
      final snapshot = await _service.fetchOverview();
      if (!mounted) return true;
      final featured = featuredBenchmark(snapshot.overview);
      final symbolStillShown = snapshot.overview.benchmarks.any((b) => b.symbol == _symbol && b.hasValue);
      setState(() {
        _snapshot = snapshot;
        _loading = false;
        _failed = false;
        if (!symbolStillShown) _symbol = featured?.symbol;
      });
      if (_symbol != null && !snapshot.fromCache) await _loadSeries();
      return !snapshot.fromCache;
    } catch (_) {
      if (!mounted) return false;
      setState(() {
        _loading = false;
        _failed = _snapshot == null;
      });
      return false;
    }
  }

  Future<void> _loadSeries() async {
    final symbol = _symbol;
    if (symbol == null) return;
    setState(() {
      _seriesLoading = true;
      _seriesFailed = false;
    });
    try {
      final data = await _service.fetchSeries(symbol, _range);
      if (!mounted || symbol != _symbol) return;
      setState(() {
        _series = data;
        _seriesLoading = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _series = null;
        _seriesLoading = false;
        _seriesFailed = true;
      });
    }
  }

  Future<void> _refresh() async => _refresher?.reportResult(await _load());

  void _select(MarketBenchmark row) {
    if (!row.hasValue || row.symbol == _symbol) return;
    setState(() {
      _symbol = row.symbol;
      _series = null;
    });
    _loadSeries();
  }

  void _setRange(String range) {
    if (range == _range) return;
    setState(() => _range = range);
    _loadSeries();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    return Scaffold(
      backgroundColor: fira.surface,
      appBar: AppBar(title: Text(l10n.marketScreenTitle)),
      body: RefreshIndicator(
        onRefresh: _refresh,
        child: _buildBody(context),
      ),
    );
  }

  Widget _buildBody(BuildContext context) {
    final l10n = context.l10n;
    const pad = EdgeInsets.fromLTRB(20, 16, 20, 32);
    if (_loading && _snapshot == null) {
      return ListView(
        padding: pad,
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          Semantics(label: l10n.marketLoading, child: const SizedBox.shrink()),
          for (final h in [140.0, 220.0, 72.0, 72.0, 72.0]) ...[
            BaseShimmer(child: Container(height: h, decoration: BoxDecoration(color: context.fira.brandTint, borderRadius: BorderRadius.circular(22)))),
            const SizedBox(height: 12),
          ],
        ],
      );
    }
    if (_failed || _snapshot == null) {
      return ListView(
        padding: pad,
        physics: const AlwaysScrollableScrollPhysics(),
        children: [_UnavailableState(onRetry: _refresh)],
      );
    }

    final snapshot = _snapshot!;
    final overview = snapshot.overview;
    final selected = overview.benchmarks.where((b) => b.symbol == _symbol).firstOrNull;
    return ListView(
      padding: pad,
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        if (snapshot.fromCache) ...[
          _Notice(icon: Icons.cloud_off_rounded, text: l10n.marketOfflineCached(formatMarketTime(snapshot.receivedAt))),
          const SizedBox(height: 12),
        ],
        if (selected != null && selected.hasValue) _HeroSection(row: selected) else const _UnavailableState(),
        const SizedBox(height: 16),
        if (selected != null && selected.hasValue && !snapshot.fromCache) ...[
          _ChartCard(
            range: _range,
            onRange: _setRange,
            series: _series,
            loading: _seriesLoading,
            failed: _seriesFailed,
            isDaily: selected.isDaily,
            onRetry: _loadSeries,
          ),
          const SizedBox(height: 20),
        ],
        _SectionTitle(l10n.marketBenchmarks),
        const SizedBox(height: 8),
        for (final row in overview.benchmarks) ...[
          _BenchmarkRow(row: row, selected: row.symbol == _symbol, onTap: () => _select(row)),
          const SizedBox(height: 8),
        ],
        const SizedBox(height: 12),
        _SummaryCard(text: overview.summaryText, stale: snapshot.fromCache),
        const SizedBox(height: 16),
        SizedBox(
          height: 52,
          child: FilledButton.icon(
            onPressed: () => Navigator.of(context).push(
              MaterialPageRoute(builder: (_) => AiChatScreen(initialPrompt: l10n.marketAskFiraPrompt)),
            ),
            icon: const Icon(Icons.auto_awesome_outlined),
            label: Text(l10n.marketAskFira),
          ),
        ),
        const SizedBox(height: 16),
        Text(
          l10n.marketAttribution(overview.attributions.join(', ')),
          style: TextStyle(color: context.fira.mutedText, fontSize: 12, height: 1.4),
        ),
        if (overview.disclaimer.isNotEmpty) ...[
          const SizedBox(height: 4),
          Text(overview.disclaimer, style: TextStyle(color: context.fira.mutedText, fontSize: 12, height: 1.4)),
        ],
      ],
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle(this.text);
  final String text;

  @override
  Widget build(BuildContext context) => Semantics(
        header: true,
        child: Text(text, style: TextStyle(color: context.fira.onSurface, fontSize: 17, fontWeight: FontWeight.w800)),
      );
}

class _Notice extends StatelessWidget {
  const _Notice({required this.icon, required this.text});
  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(color: fira.brandTint, borderRadius: BorderRadius.circular(16)),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: fira.danger, size: 20),
          const SizedBox(width: 10),
          Expanded(child: Text(text, style: TextStyle(color: fira.onSurface, fontSize: 13.5, height: 1.4))),
        ],
      ),
    );
  }
}

class _UnavailableState extends StatelessWidget {
  const _UnavailableState({this.onRetry});
  final Future<void> Function()? onRetry;

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final l10n = context.l10n;
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(color: fira.card, borderRadius: BorderRadius.circular(22)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          MarketStatusLine(icon: Icons.block_rounded, label: l10n.marketUnavailable, color: fira.danger, fontSize: 15),
          const SizedBox(height: 8),
          Text(l10n.marketUnavailableHint, style: TextStyle(color: fira.mutedText, fontSize: 14, height: 1.4)),
          if (onRetry != null) ...[
            const SizedBox(height: 12),
            SizedBox(
              height: 48,
              child: OutlinedButton.icon(onPressed: onRetry, icon: const Icon(Icons.refresh_rounded), label: Text(l10n.marketTryAgain)),
            ),
          ],
        ],
      ),
    );
  }
}

class _HeroSection extends StatelessWidget {
  const _HeroSection({required this.row});
  final MarketBenchmark row;

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final l10n = context.l10n;
    final onDeep = Theme.of(context).colorScheme.onPrimary;
    final muted = onDeep.withValues(alpha: .8);
    final trend = MarketTrendVisual.of(context, row.trend, neutral: onDeep);
    final fresh = MarketFreshnessVisual.of(context, row, neutral: muted);
    final unit = marketUnitLabel(l10n, row.currency, row.unit);
    final stale = row.freshness == MarketFreshness.stale;
    return Semantics(
      container: true,
      label: '${row.name}. ${formatMarketValue(row.value!)} $unit. ${trend.label}. ${fresh.label}. ${l10n.marketSourceLabel(row.source.attribution)}',
      excludeSemantics: true,
      child: Container(
        padding: const EdgeInsets.all(18),
        decoration: BoxDecoration(color: fira.brandDeep, borderRadius: BorderRadius.circular(22)),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(row.name, style: TextStyle(color: onDeep, fontSize: 14, fontWeight: FontWeight.w700)),
            const SizedBox(height: 8),
            Wrap(
              crossAxisAlignment: WrapCrossAlignment.end,
              spacing: 8,
              children: [
                Text(formatMarketValue(row.value!), style: TextStyle(color: onDeep, fontSize: 32, fontWeight: FontWeight.w800)),
                Padding(padding: const EdgeInsets.only(bottom: 6), child: Text(unit, style: TextStyle(color: muted, fontSize: 13, fontWeight: FontWeight.w600))),
              ],
            ),
            const SizedBox(height: 8),
            MarketStatusLine(icon: trend.icon, label: trend.label, color: stale ? muted : onDeep, fontSize: 13),
            const SizedBox(height: 4),
            MarketStatusLine(icon: fresh.icon, label: fresh.label, color: stale ? onDeep : muted, textColor: stale ? onDeep : muted, fontSize: 12.5),
            if (row.sourceTime != null) ...[
              const SizedBox(height: 4),
              Text(l10n.marketSourceTime(formatMarketTime(row.sourceTime!, dateOnly: row.isDaily)), style: TextStyle(color: muted, fontSize: 12)),
            ],
            const SizedBox(height: 2),
            Text(l10n.marketSourceLabel(row.source.attribution), style: TextStyle(color: muted, fontSize: 12)),
          ],
        ),
      ),
    );
  }
}

class _ChartCard extends StatelessWidget {
  const _ChartCard({
    required this.range,
    required this.onRange,
    required this.series,
    required this.loading,
    required this.failed,
    required this.isDaily,
    required this.onRetry,
  });

  final String range;
  final ValueChanged<String> onRange;
  final MarketSeriesData? series;
  final bool loading;
  final bool failed;
  final bool isDaily;
  final Future<void> Function() onRetry;

  String _rangeLabel(AppLocalizations l10n, String r) => switch (r) {
        '1D' => l10n.marketRange1D,
        '1W' => l10n.marketRange1W,
        '1M' => l10n.marketRange1M,
        '3M' => l10n.marketRange3M,
        _ => l10n.marketRange1Y,
      };

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final l10n = context.l10n;
    final points = series?.points ?? const <MarketPoint>[];
    Widget chart;
    if (loading && series == null) {
      chart = const Center(child: CircularProgressIndicator());
    } else if (failed) {
      chart = Center(
        child: TextButton.icon(onPressed: onRetry, icon: const Icon(Icons.refresh_rounded), label: Text(l10n.marketTryAgain)),
      );
    } else if (points.length < 2) {
      chart = Center(child: Text(l10n.marketChartEmpty, textAlign: TextAlign.center, style: TextStyle(color: fira.mutedText, fontSize: 13.5)));
    } else {
      final values = points.map((p) => p.value);
      final hi = values.reduce(math.max);
      final lo = values.reduce(math.min);
      chart = Column(
        children: [
          Expanded(
            child: Row(
              children: [
                Expanded(
                  child: Semantics(
                    label: l10n.marketChartSemantics(
                      formatMarketValue(lo.toString()),
                      formatMarketValue(hi.toString()),
                      formatMarketTime(points.first.time, dateOnly: isDaily),
                      formatMarketTime(points.last.time, dateOnly: isDaily),
                    ),
                    child: CustomPaint(painter: MarketSparklinePainter(points, fira.brandPrimary, strokeWidth: 2.5), size: Size.infinite),
                  ),
                ),
                const SizedBox(width: 8),
                Column(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(formatMarketValue(hi.toString()), style: TextStyle(color: fira.mutedText, fontSize: 11)),
                    Text(formatMarketValue(lo.toString()), style: TextStyle(color: fira.mutedText, fontSize: 11)),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 6),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(formatMarketTime(points.first.time, dateOnly: isDaily), style: TextStyle(color: fira.mutedText, fontSize: 11)),
              Text(formatMarketTime(points.last.time, dateOnly: isDaily), style: TextStyle(color: fira.mutedText, fontSize: 11)),
            ],
          ),
        ],
      );
    }

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(color: fira.card, borderRadius: BorderRadius.circular(22)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              for (final r in MarketDataService.ranges)
                ChoiceChip(
                  label: Text(_rangeLabel(l10n, r)),
                  selected: r == range,
                  onSelected: (_) => onRange(r),
                  materialTapTargetSize: MaterialTapTargetSize.padded,
                  selectedColor: fira.chipSelectedBg,
                  backgroundColor: fira.chipUnselectedBg,
                ),
            ],
          ),
          const SizedBox(height: 12),
          SizedBox(height: 180, child: chart),
        ],
      ),
    );
  }
}

class _BenchmarkRow extends StatelessWidget {
  const _BenchmarkRow({required this.row, required this.selected, required this.onTap});
  final MarketBenchmark row;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final l10n = context.l10n;
    final fresh = MarketFreshnessVisual.of(context, row);
    final unit = marketUnitLabel(l10n, row.currency, row.unit);
    final valueText = row.hasValue ? '${formatMarketValue(row.value!)} $unit' : l10n.marketFreshUnavailable;
    return Semantics(
      button: row.hasValue,
      selected: selected,
      label: '${row.name}. $valueText. ${fresh.label}. ${l10n.marketSourceLabel(row.source.attribution)}',
      excludeSemantics: true,
      child: Material(
        color: selected ? fira.brandTint : fira.card,
        borderRadius: BorderRadius.circular(16),
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: row.hasValue ? onTap : null,
          child: ConstrainedBox(
            constraints: const BoxConstraints(minHeight: 64),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(row.name, style: TextStyle(color: fira.onSurface, fontSize: 14, fontWeight: FontWeight.w700)),
                        const SizedBox(height: 4),
                        MarketStatusLine(icon: fresh.icon, label: fresh.label, color: fresh.color, fontSize: 12),
                        const SizedBox(height: 2),
                        Text(
                          row.sourceTime != null && row.hasValue
                              ? '${row.source.attribution} · ${formatMarketTime(row.sourceTime!, dateOnly: row.isDaily)}'
                              : row.source.attribution,
                          style: TextStyle(color: fira.mutedText, fontSize: 12),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 12),
                  Flexible(
                    child: Text(
                      valueText,
                      textAlign: TextAlign.end,
                      style: TextStyle(
                        color: row.hasValue ? fira.onSurface : fira.mutedText,
                        fontSize: row.hasValue ? 15 : 13,
                        fontWeight: row.hasValue ? FontWeight.w800 : FontWeight.w600,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _SummaryCard extends StatelessWidget {
  const _SummaryCard({required this.text, required this.stale});
  final String text;
  final bool stale;

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final l10n = context.l10n;
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(color: fira.card, borderRadius: BorderRadius.circular(22)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.auto_awesome_outlined, color: fira.brandPrimary, size: 20),
              const SizedBox(width: 8),
              Expanded(child: _SectionTitle(l10n.marketSummary)),
            ],
          ),
          const SizedBox(height: 8),
          Text(text.isNotEmpty ? text : l10n.marketUnavailableHint, style: TextStyle(color: fira.onSurface, fontSize: 14, height: 1.45)),
          const SizedBox(height: 8),
          Text(stale ? l10n.marketSummaryStale : l10n.marketSummaryNote, style: TextStyle(color: fira.mutedText, fontSize: 12, height: 1.4)),
        ],
      ),
    );
  }
}
