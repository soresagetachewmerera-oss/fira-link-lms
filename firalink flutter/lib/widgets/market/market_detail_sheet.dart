import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../l10n/app_localizations.dart';
import '../../models/market_series_detail.dart';
import '../../models/market_watch.dart';
import '../../services/market_watch_service.dart';
import '../../theme/fira_colors.dart';
import 'market_detail_chart.dart';
import 'market_ui.dart' show formatMarketTime;

/// Opens the detail sheet for a market watch row (guests too). [actions] are added by the signed-in
/// features (watchlist, alert, Fira AI, CSV) and receive the loaded detail.
Future<void> showMarketDetailSheet(
  BuildContext context,
  MarketWatchRow row, {
  MarketWatchService? service,
  List<Widget> Function(BuildContext context, MarketSeriesDetail detail)? actions,
}) {
  return showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    useSafeArea: true,
    backgroundColor: context.fira.brandDeep.withValues(alpha: 0),
    builder: (_) => DraggableScrollableSheet(
      initialChildSize: .88,
      minChildSize: .5,
      maxChildSize: .95,
      expand: false,
      builder: (context, controller) => MarketDetailSheet(row: row, service: service ?? MarketWatchService(), scrollController: controller, actions: actions),
    ),
  );
}

class MarketDetailSheet extends StatefulWidget {
  const MarketDetailSheet({super.key, required this.row, required this.service, this.scrollController, this.actions});

  final MarketWatchRow row;
  final MarketWatchService service;
  final ScrollController? scrollController;
  final List<Widget> Function(BuildContext context, MarketSeriesDetail detail)? actions;

  @override
  State<MarketDetailSheet> createState() => _MarketDetailSheetState();
}

class _MarketDetailSheetState extends State<MarketDetailSheet> {
  MarketSeriesDetail? _detail;
  bool _loading = true;
  bool _failed = false;
  String _range = '1M';
  String? _unit;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _failed = false;
    });
    try {
      final detail = await widget.service.fetchSeries(widget.row.key, range: _range, unit: _unit);
      if (!mounted) return;
      setState(() {
        _detail = detail;
        _range = detail.range;
        _unit = detail.unitKey;
        _loading = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _failed = true; // keep what is already on screen
      });
    }
  }

  void _pick({String? range, String? unit}) {
    if (_loading) return;
    _range = range ?? _range;
    _unit = unit ?? _unit;
    _load();
  }

  String _n(double v, int d) => NumberFormat.decimalPatternDigits(locale: 'en', decimalDigits: d).format(v);

  String _date(DateTime t) => _range == '1D' ? formatMarketTime(t) : formatMarketTime(t, dateOnly: true);

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final l10n = context.l10n;
    final onDeep = Theme.of(context).colorScheme.onPrimary;
    final muted = onDeep.withValues(alpha: .74);
    final brand = Theme.of(context).textTheme.labelLarge;
    final d = _detail;
    final row = widget.row;
    final dir = d?.direction ?? row.direction;
    final color = switch (dir) {
      MarketDirection.up => fira.marketUp,
      MarketDirection.down => fira.marketDown,
      _ => fira.marketFlat,
    };

    Widget chip(String label, bool selected, VoidCallback onTap) => Semantics(
      button: true,
      selected: selected,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(999),
        child: ConstrainedBox(
          constraints: const BoxConstraints(minWidth: 44, minHeight: 44),
          child: Container(
            alignment: Alignment.center,
            padding: const EdgeInsets.symmetric(horizontal: 12),
            margin: const EdgeInsets.symmetric(vertical: 4),
            decoration: BoxDecoration(
              color: selected ? onDeep : onDeep.withValues(alpha: 0),
              border: Border.all(color: selected ? onDeep : onDeep.withValues(alpha: .2)),
              borderRadius: BorderRadius.circular(999),
            ),
            child: Text(
              label,
              style: TextStyle(color: selected ? fira.brandDeep : onDeep, fontSize: 13, fontWeight: FontWeight.w600),
            ),
          ),
        ),
      ),
    );

    String rangeLabel(String r) => switch (r) {
      '1D' => l10n.marketRange1D,
      '1W' => l10n.marketRange1W,
      '1M' => l10n.marketRange1M,
      '3M' => l10n.marketRange3M,
      '1Y' => l10n.marketRange1Y,
      _ => l10n.marketRange5Y,
    };

    final dec = d?.decimals ?? row.decimals;
    final price = d?.price ?? row.price;
    final change = d?.change ?? row.change;
    final pct = d?.changePct ?? row.changePct;
    final sign = change == null ? '' : (change > 0 ? '+' : (change < 0 ? '−' : ''));
    final points = d?.points ?? const [];

    return DefaultTextStyle.merge(
      style: TextStyle(fontFamily: brand?.fontFamily, fontFamilyFallback: brand?.fontFamilyFallback, color: onDeep),
      child: DecoratedBox(
        decoration: BoxDecoration(
          borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
          gradient: LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [fira.brandDeep, fira.brandDarker]),
        ),
        child: ListView(
          controller: widget.scrollController,
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 32),
          children: [
            Center(
              child: Container(
                width: 40,
                height: 4,
                margin: const EdgeInsets.only(bottom: 8),
                decoration: BoxDecoration(color: onDeep.withValues(alpha: .3), borderRadius: BorderRadius.circular(2)),
              ),
            ),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Semantics(
                        header: true,
                        child: Text(
                          d?.name ?? row.name,
                          style: TextStyle(color: onDeep, fontSize: 19, fontWeight: FontWeight.w700, height: 1.3),
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text('${d?.statusLabel ?? row.statusLabel} · ${row.sourceShort}', style: TextStyle(color: muted, fontSize: 13, height: 1.4)),
                    ],
                  ),
                ),
                IconButton(
                  tooltip: l10n.marketDetailClose,
                  onPressed: () => Navigator.of(context).maybePop(),
                  icon: Icon(Icons.close_rounded, color: onDeep),
                  constraints: const BoxConstraints(minWidth: 44, minHeight: 44),
                ),
              ],
            ),
            const SizedBox(height: 12),
            if (price != null)
              Semantics(
                label: row.a11yLabel,
                excludeSemantics: true,
                child: Wrap(
                  crossAxisAlignment: WrapCrossAlignment.end,
                  spacing: 10,
                  runSpacing: 4,
                  children: [
                    Text(
                      _n(price, dec),
                      style: TextStyle(
                        color: onDeep,
                        fontSize: 32,
                        fontWeight: FontWeight.w800,
                        height: 1.1,
                        fontFeatures: const [FontFeature.tabularFigures()],
                      ),
                    ),
                    Text(
                      d?.unitLabel ?? row.unitLabel,
                      style: TextStyle(color: muted, fontSize: 14, fontWeight: FontWeight.w600),
                    ),
                    if (change != null)
                      Text(
                        '$sign${_n(change.abs(), dec)} ($sign${_n((pct ?? 0).abs(), 2)}%)',
                        style: TextStyle(color: color, fontSize: 15, fontWeight: FontWeight.w700),
                      ),
                  ],
                ),
              ),
            const SizedBox(height: 16),
            if (d != null) ...[
              Semantics(
                label: l10n.marketDetailRange,
                child: Wrap(spacing: 6, children: [for (final r in d.ranges) chip(rangeLabel(r), r == _range, () => _pick(range: r))]),
              ),
              if (d.units.length > 1)
                Semantics(
                  label: l10n.marketDetailUnit,
                  child: Wrap(spacing: 6, children: [for (final u in d.units) chip(u.label, u.key == _unit, () => _pick(unit: u.key))]),
                ),
              const SizedBox(height: 12),
            ],
            if (_loading && d == null)
              const SizedBox(height: 200, child: Center(child: CircularProgressIndicator()))
            else if (points.length >= 2)
              Opacity(
                opacity: _loading ? .6 : 1,
                child: MarketDetailChart(
                  values: [for (final p in points) p.v],
                  ref: d?.prevClose,
                  color: color,
                  label: (i) => '${_date(points[i].t)} · ${_n(points[i].v, dec)}',
                  semanticsLabel: l10n.marketDetailChart(
                    d!.shortName,
                    rangeLabel(_range),
                    _date(points.first.t),
                    _date(points.last.t),
                    _n(points.map((p) => p.v).reduce((a, b) => a < b ? a : b), dec),
                    _n(points.map((p) => p.v).reduce((a, b) => a > b ? a : b), dec),
                  ),
                ),
              )
            else
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 24),
                child: Text(l10n.marketChartEmpty, style: TextStyle(color: muted)),
              ),
            if (points.length >= 2) ...[const SizedBox(height: 6), Text(l10n.marketDetailScrubHint, style: TextStyle(color: muted, fontSize: 12))],
            if (_failed) ...[
              const SizedBox(height: 8),
              Row(
                children: [
                  Icon(Icons.error_outline_rounded, size: 18, color: muted),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(l10n.marketDetailFailed, style: TextStyle(color: onDeep, fontSize: 13)),
                  ),
                  TextButton(
                    onPressed: _load,
                    style: TextButton.styleFrom(foregroundColor: onDeep, minimumSize: const Size(44, 44)),
                    child: Text(l10n.marketTryAgain),
                  ),
                ],
              ),
            ],
            if (d?.fx != null) ...[
              const SizedBox(height: 10),
              Text(
                l10n.marketDetailFx(_n(d!.fx!.rate, 4), d.fx!.source, d.fx!.asOf == null ? '' : formatMarketTime(d.fx!.asOf!, dateOnly: true)),
                style: TextStyle(color: muted, fontSize: 12.5, height: 1.4),
              ),
            ],
            if (d != null) ...[
              const SizedBox(height: 16),
              Semantics(
                header: true,
                child: Text(
                  l10n.marketDetailStats,
                  style: TextStyle(color: onDeep, fontSize: 15, fontWeight: FontWeight.w700),
                ),
              ),
              const SizedBox(height: 8),
              // Two columns that grow with their text (long Amharic labels, 200% text) instead of a fixed ratio.
              LayoutBuilder(
                builder: (context, constraints) => Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    for (final (label, value) in [
                      (l10n.marketDetailPrevClose, d.prevClose),
                      (l10n.marketDetailHigh52w, d.high52w),
                      (l10n.marketDetailLow52w, d.low52w),
                      (l10n.marketDetailAvg20d, d.avg20d),
                    ])
                      SizedBox(
                        width: (constraints.maxWidth - 8) / 2,
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          decoration: BoxDecoration(color: onDeep.withValues(alpha: .07), borderRadius: BorderRadius.circular(12)),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Text(
                                label,
                                style: TextStyle(color: muted, fontSize: 12, fontWeight: FontWeight.w600),
                              ),
                              Text(
                                value == null ? '—' : _n(value, dec),
                                style: TextStyle(color: onDeep, fontSize: 16, fontWeight: FontWeight.w700),
                              ),
                            ],
                          ),
                        ),
                      ),
                  ],
                ),
              ),
              if (widget.actions != null) ...[const SizedBox(height: 16), ...widget.actions!(context, d)],
              if (points.isNotEmpty)
                Theme(
                  data: Theme.of(context).copyWith(dividerColor: onDeep.withValues(alpha: 0)),
                  child: ExpansionTile(
                    tilePadding: EdgeInsets.zero,
                    iconColor: onDeep,
                    collapsedIconColor: onDeep,
                    title: Text(
                      l10n.marketDetailValues,
                      style: TextStyle(color: onDeep, fontSize: 14, fontWeight: FontWeight.w600),
                    ),
                    children: [
                      for (final p in points.reversed.take(90))
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: 4),
                          child: Row(
                            children: [
                              Expanded(
                                child: Text(_date(p.t), style: TextStyle(color: muted, fontSize: 13)),
                              ),
                              Text(
                                _n(p.v, dec),
                                style: TextStyle(color: onDeep, fontSize: 13, fontFeatures: const [FontFeature.tabularFigures()]),
                              ),
                            ],
                          ),
                        ),
                    ],
                  ),
                ),
              const SizedBox(height: 8),
              Text(d.disclaimer, style: TextStyle(color: muted, fontSize: 12, height: 1.45)),
            ],
          ],
        ),
      ),
    );
  }
}
