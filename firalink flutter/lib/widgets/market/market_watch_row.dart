import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../l10n/app_localizations.dart';
import '../../models/market_watch.dart';
import '../../theme/fira_colors.dart';
import 'market_sparkline.dart';
import 'market_ui.dart' show formatMarketTime;

/// One market watch row on the deep card: direction glyph and short name, status and source, sparkline,
/// price, unit and change. Screen readers hear one sentence (the server's a11y label), plus "offline"
/// when the row comes from the device cache. Numbers always use Latin digits.
class MarketWatchRowTile extends StatelessWidget {
  const MarketWatchRowTile({super.key, required this.row, this.offlineSince, this.onTap, this.selected = false});

  final MarketWatchRow row;

  /// Set when this row is shown from the device cache (offline): the time it was received.
  final DateTime? offlineSince;
  final VoidCallback? onTap;
  final bool selected;

  static final _tabular = [const FontFeature.tabularFigures()];

  String _number(double value, int decimals) => NumberFormat.decimalPatternDigits(locale: 'en', decimalDigits: decimals).format(value.abs());

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final l10n = context.l10n;
    final onDeep = Theme.of(context).colorScheme.onPrimary;
    final muted = onDeep.withValues(alpha: .74);
    final offline = offlineSince != null;
    // Offline, a "Live" or "Delayed" label would no longer be true: show when that price was from instead.
    final timeSensitive = row.status == MarketWatchStatus.live || row.status == MarketWatchStatus.delayed;
    final dim = row.status == MarketWatchStatus.stale || (offline && timeSensitive);
    final statusText = offline && timeSensitive && row.asOf != null ? l10n.marketWatchLastAt(formatMarketTime(row.asOf!)) : row.statusLabel;

    final directionColor = dim
        ? muted
        : switch (row.direction) {
            MarketDirection.up => fira.marketUp,
            MarketDirection.down => fira.marketDown,
            _ => fira.marketFlat,
          };
    final glyph = !row.hasPrice
        ? Icons.circle
        : switch (row.direction) {
            MarketDirection.up => Icons.arrow_drop_up_rounded,
            MarketDirection.down => Icons.arrow_drop_down_rounded,
            MarketDirection.flat => Icons.remove_rounded,
            MarketDirection.none => Icons.circle,
          };
    final glyphSize = glyph == Icons.circle ? 7.0 : 26.0;

    final change = row.change;
    final pct = row.changePct;
    final sign = change == null ? '' : (change > 0 ? '+' : (change < 0 ? '−' : ''));
    final changeText = change == null ? null : '$sign${_number(change, row.decimals)} ($sign${_number(pct ?? 0, 2)}%)';

    final semantics = offline ? '${row.a11yLabel} ${l10n.marketWatchOfflineSpoken(formatMarketTime(offlineSince!))}' : row.a11yLabel;
    final textScale = MediaQuery.textScalerOf(context).scale(1);
    final brand = Theme.of(context).textTheme.labelLarge;

    return Semantics(
      container: true,
      button: onTap != null,
      selected: onTap != null ? selected : null,
      label: semantics,
      excludeSemantics: true,
      child: Material(
        color: onDeep.withValues(alpha: selected ? .08 : 0),
        child: InkWell(
          onTap: onTap,
          // Material above resets the text style; keep the brand font (see MarketWatchCard._frame).
          child: DefaultTextStyle.merge(
            style: TextStyle(fontFamily: brand?.fontFamily, fontFamilyFallback: brand?.fontFamilyFallback),
            child: ConstrainedBox(
              constraints: const BoxConstraints(minHeight: 70),
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                child: LayoutBuilder(
                  builder: (context, constraints) {
                    // Drop the sparkline when space is tight or text is enlarged; the numbers stay.
                    final showSpark = row.sparkline.length >= 2 && textScale < 1.6 && constraints.maxWidth >= 300;
                    final wide = constraints.maxWidth >= 420;
                    return Row(
                      children: [
                        Expanded(
                          flex: 13,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  SizedBox(
                                    width: 18,
                                    child: Icon(glyph, size: glyphSize, color: directionColor),
                                  ),
                                  const SizedBox(width: 6),
                                  Flexible(
                                    child: Text(
                                      row.shortName,
                                      style: TextStyle(color: dim ? muted : onDeep, fontSize: 16, fontWeight: FontWeight.w700, height: 1.3),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 2),
                              Text('$statusText · ${row.sourceShort}', style: TextStyle(color: muted, fontSize: 12.5, height: 1.4)),
                            ],
                          ),
                        ),
                        if (showSpark) ...[
                          const SizedBox(width: 10),
                          SizedBox(
                            width: wide ? 96 : 68,
                            height: wide ? 34 : 30,
                            child: CustomPaint(
                              painter: WatchSparklinePainter(values: row.sparkline, ref: row.sparkRef, color: directionColor, refColor: fira.marketFlat),
                            ),
                          ),
                        ],
                        const SizedBox(width: 12),
                        // A bounded share for the numbers: long values wrap here instead of squeezing the name.
                        Flexible(
                          flex: 9,
                          fit: FlexFit.tight,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: row.hasPrice
                                ? [
                                    Text(
                                      _number(row.price!, row.decimals),
                                      style: TextStyle(
                                        color: dim ? muted : onDeep,
                                        fontSize: 17,
                                        fontWeight: FontWeight.w700,
                                        height: 1.3,
                                        fontFeatures: _tabular,
                                      ),
                                    ),
                                    Text(
                                      row.unitLabel,
                                      textAlign: TextAlign.right,
                                      style: TextStyle(color: muted, fontSize: 12, height: 1.4),
                                    ),
                                    if (changeText != null)
                                      Text(
                                        changeText,
                                        textAlign: TextAlign.right,
                                        style: TextStyle(
                                          color: directionColor,
                                          fontSize: 12.5,
                                          fontWeight: FontWeight.w600,
                                          height: 1.4,
                                          fontFeatures: _tabular,
                                        ),
                                      ),
                                  ]
                                : [
                                    Text(
                                      l10n.marketWatchRowUnavailable,
                                      textAlign: TextAlign.right,
                                      style: TextStyle(color: muted, fontSize: 14, fontWeight: FontWeight.w600),
                                    ),
                                  ],
                          ),
                        ),
                      ],
                    );
                  },
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
