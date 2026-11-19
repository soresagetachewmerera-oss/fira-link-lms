import 'dart:async';
import 'dart:math' as math;

import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../models/market_overview.dart';
import '../../theme/fira_colors.dart';

/// Shared pieces for the Home market card and the Coffee market screen.
/// Built from context.fira tokens only (no new colours). Status is always word + icon + colour.

String formatMarketValue(String value) {
  final parsed = double.tryParse(value);
  return parsed == null ? value : parsed.toStringAsFixed(2);
}

String marketUnitLabel(AppLocalizations l10n, String currency, String unit) {
  if (currency == 'USX' && unit == 'lb') return l10n.marketUnitUsCentsLb;
  if (currency == 'USD' && unit == 'lb') return l10n.marketUnitUsdLb;
  if (currency == 'ETB' && unit == 'feresula_17kg') return l10n.marketUnitEtbFeresula;
  return '$currency/$unit';
}

String _two(int v) => v.toString().padLeft(2, '0');

/// Daily and weekly prices carry a source date (UTC day); others a local time.
String formatMarketTime(DateTime time, {bool dateOnly = false}) {
  if (dateOnly) {
    final utc = time.toUtc();
    return '${utc.year}-${_two(utc.month)}-${_two(utc.day)}';
  }
  final local = time.toLocal();
  return '${local.year}-${_two(local.month)}-${_two(local.day)} ${_two(local.hour)}:${_two(local.minute)}';
}

class MarketTrendVisual {
  const MarketTrendVisual(this.label, this.icon, this.color);

  final String label;
  final IconData icon;
  final Color color;

  static MarketTrendVisual of(BuildContext context, MarketTrendLabel trend, {Color? neutral}) {
    final fira = context.fira;
    final l10n = context.l10n;
    switch (trend) {
      case MarketTrendLabel.bullish:
        return MarketTrendVisual(l10n.marketTrendBullish, Icons.trending_up_rounded, fira.success);
      case MarketTrendLabel.bearish:
        return MarketTrendVisual(l10n.marketTrendBearish, Icons.trending_down_rounded, fira.danger);
      case MarketTrendLabel.sideways:
        return MarketTrendVisual(l10n.marketTrendSideways, Icons.trending_flat_rounded, neutral ?? fira.mutedText);
      case MarketTrendLabel.notEnoughData:
        return MarketTrendVisual(l10n.marketTrendNotEnoughData, Icons.hourglass_empty_rounded, neutral ?? fira.mutedText);
    }
  }
}

class MarketFreshnessVisual {
  const MarketFreshnessVisual(this.label, this.icon, this.color);

  final String label;
  final IconData icon;
  final Color color;

  static MarketFreshnessVisual of(BuildContext context, MarketBenchmark row, {Color? neutral}) {
    final fira = context.fira;
    final l10n = context.l10n;
    final muted = neutral ?? fira.mutedText;
    final serverLabel = row.freshnessLabel;
    switch (row.freshness) {
      case MarketFreshness.live:
        return MarketFreshnessVisual(serverLabel.isNotEmpty ? serverLabel : l10n.marketFreshLive, Icons.circle, fira.success);
      case MarketFreshness.delayed:
        return MarketFreshnessVisual(serverLabel.isNotEmpty ? serverLabel : l10n.marketFreshDelayed, Icons.schedule_rounded, muted);
      case MarketFreshness.dailyClose:
        return MarketFreshnessVisual(serverLabel.isNotEmpty ? serverLabel : l10n.marketFreshDailyClose, Icons.event_rounded, muted);
      case MarketFreshness.stale:
        return MarketFreshnessVisual(serverLabel.isNotEmpty ? serverLabel : l10n.marketFreshStale, Icons.history_rounded, fira.danger);
      case MarketFreshness.unavailable:
        return MarketFreshnessVisual(l10n.marketFreshUnavailable, Icons.block_rounded, fira.danger);
    }
  }
}

/// Word + icon + colour, never colour alone.
class MarketStatusLine extends StatelessWidget {
  const MarketStatusLine({super.key, required this.icon, required this.label, required this.color, this.textColor, this.fontSize = 12});

  final IconData icon;
  final String label;
  final Color color;
  final Color? textColor;
  final double fontSize;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: fontSize + 3, color: color),
        const SizedBox(width: 4),
        Flexible(
          child: Text(
            label,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(fontSize: fontSize, fontWeight: FontWeight.w600, color: textColor ?? color, height: 1.25),
          ),
        ),
      ],
    );
  }
}

class MarketSparklinePainter extends CustomPainter {
  MarketSparklinePainter(this.points, this.color, {this.strokeWidth = 2});

  final List<MarketPoint> points;
  final Color color;
  final double strokeWidth;

  @override
  void paint(Canvas canvas, Size size) {
    if (points.length < 2) return;
    final values = points.map((p) => p.value).toList();
    final minV = values.reduce(math.min);
    final maxV = values.reduce(math.max);
    final span = (maxV - minV).abs() < 1e-9 ? 1.0 : maxV - minV;
    final start = points.first.time.millisecondsSinceEpoch.toDouble();
    final end = points.last.time.millisecondsSinceEpoch.toDouble();
    final width = (end - start).abs() < 1 ? 1.0 : end - start;
    final path = Path();
    for (var i = 0; i < points.length; i++) {
      final x = (points[i].time.millisecondsSinceEpoch - start) / width * size.width;
      final y = size.height - ((points[i].value - minV) / span) * size.height;
      i == 0 ? path.moveTo(x, y) : path.lineTo(x, y);
    }
    canvas.drawPath(
      path,
      Paint()
        ..color = color
        ..style = PaintingStyle.stroke
        ..strokeWidth = strokeWidth
        ..strokeCap = StrokeCap.round
        ..strokeJoin = StrokeJoin.round,
    );
  }

  @override
  bool shouldRepaint(MarketSparklinePainter old) => old.points != points || old.color != color;
}

/// Refreshes about every [interval] while [isVisible], pauses when the app is in the
/// background, and waits longer after each failure (up to 10x the interval).
class MarketRefresher with WidgetsBindingObserver {
  MarketRefresher({required this.onTick, required this.isVisible, this.interval = const Duration(seconds: 60)});

  final Future<bool> Function() onTick;
  final bool Function() isVisible;
  final Duration interval;

  Timer? _timer;
  int _failures = 0;
  bool _foreground = true;
  bool _disposed = false;

  int get failures => _failures;

  Duration get nextDelay => interval * math.min(10, 1 << math.min(_failures, 4));

  void start() {
    WidgetsBinding.instance.addObserver(this);
    _schedule();
  }

  void reportResult(bool ok) => _failures = ok ? 0 : _failures + 1;

  void _schedule() {
    _timer?.cancel();
    if (_disposed || !_foreground) return;
    _timer = Timer(nextDelay, () async {
      if (_disposed) return;
      if (isVisible()) {
        reportResult(await onTick());
      }
      _schedule();
    });
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    _foreground = state == AppLifecycleState.resumed;
    if (_foreground) {
      _schedule();
    } else {
      _timer?.cancel();
    }
  }

  void dispose() {
    _disposed = true;
    _timer?.cancel();
    WidgetsBinding.instance.removeObserver(this);
  }
}

/// The row a card should feature: the hero when it has a value, else the first row that has one.
MarketBenchmark? featuredBenchmark(MarketOverview overview) {
  final hero = overview.hero;
  if (hero != null && hero.hasValue) return hero;
  for (final row in overview.benchmarks) {
    if (row.hasValue) return row;
  }
  return null;
}
