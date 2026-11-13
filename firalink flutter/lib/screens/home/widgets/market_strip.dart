import 'package:flutter/material.dart';
import '../models/market_data.dart';
import 'home_design_constants.dart';

class MarketStrip extends StatelessWidget {
  const MarketStrip({super.key, required this.items});

  final List<MarketChipItem> items;

  @override
  Widget build(BuildContext context) {
    if (items.isEmpty) {
      return const SizedBox.shrink();
    }

    final Color cardBg = HomeDesignConstants.surface(context);
    final Color textColor = HomeDesignConstants.text(context);
    final Color mutedColor = HomeDesignConstants.mutedText(context);
    final Color tintColor = HomeDesignConstants.brandTint(context);

    return SizedBox(
      height: 38,
      child: ListView.separated(
        padding: const EdgeInsets.symmetric(
          horizontal: HomeDesignConstants.horizontalPadding,
        ),
        scrollDirection: Axis.horizontal,
        physics: const BouncingScrollPhysics(),
        itemCount: items.length,
        separatorBuilder: (_, _) => const SizedBox(width: 8),
        itemBuilder: (context, index) {
          final item = items[index];

          Color trendColor;
          IconData? trendIcon;
          switch (item.trend) {
            case MarketTrend.positive:
              trendColor = const Color(0xFF16A34A); // Semantic green only
              trendIcon = Icons.arrow_upward_rounded;
              break;
            case MarketTrend.negative:
              trendColor = const Color(0xFFDC2626); // Semantic red only
              trendIcon = Icons.arrow_downward_rounded;
              break;
            case MarketTrend.neutral:
              trendColor = mutedColor;
              trendIcon = null;
              break;
          }

          final details = [
            if (item.source?.isNotEmpty == true) 'Source: ${item.source}',
            if (item.asOf != null)
              'As of: ${item.asOf!.year}-${item.asOf!.month.toString().padLeft(2, '0')}-${item.asOf!.day.toString().padLeft(2, '0')}',
          ].join(' · ');

          return Tooltip(
            message: details,
            child: Semantics(
              label: details.isEmpty
                  ? '${item.label}: ${item.value}'
                  : '${item.label}: ${item.value}. $details',
              child: Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 6,
                ),
                decoration: BoxDecoration(
                  color: cardBg,
                  borderRadius: BorderRadius.circular(
                    HomeDesignConstants.radiusPill,
                  ),
                  border: Border.all(color: tintColor, width: 1.0),
                  boxShadow: HomeDesignConstants.softShadow(
                    context,
                    blur: 8,
                    offset: const Offset(0, 2),
                  ),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: <Widget>[
                    Text(
                      item.label,
                      style: TextStyle(
                        color: mutedColor,
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(width: 6),
                    Text(
                      item.value,
                      style: TextStyle(
                        color: textColor,
                        fontSize: 11.5,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    if (item.changeText != null) ...[
                      const SizedBox(width: 4),
                      if (trendIcon != null)
                        Icon(trendIcon, size: 11, color: trendColor),
                      Text(
                        item.changeText!,
                        style: TextStyle(
                          color: trendColor,
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}
