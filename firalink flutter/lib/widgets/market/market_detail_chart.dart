import 'dart:ui' as ui;

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../theme/fira_colors.dart';
import 'market_sparkline.dart' show SparklineGeometry;

/// Detail chart: line with a soft fill and a dashed previous close. Press and hold (or drag) to scrub:
/// a crosshair and a tooltip with the date and value, a light haptic tick per point; releasing returns
/// to the latest value. [label] formats a point for the tooltip.
class MarketDetailChart extends StatefulWidget {
  const MarketDetailChart({
    super.key,
    required this.values,
    required this.ref,
    required this.color,
    required this.label,
    required this.semanticsLabel,
    this.height = 200,
  });

  final List<double> values;
  final double? ref;
  final Color color;
  final String Function(int index) label;
  final String semanticsLabel;
  final double height;

  @override
  State<MarketDetailChart> createState() => _MarketDetailChartState();
}

class _MarketDetailChartState extends State<MarketDetailChart> {
  int? _index;

  void _scrub(Offset local, double width) {
    if (widget.values.length < 2) return;
    const padding = 8.0;
    final fraction = ((local.dx - padding) / (width - 2 * padding)).clamp(0.0, 1.0);
    final i = (fraction * (widget.values.length - 1)).round();
    if (i != _index) {
      HapticFeedback.selectionClick();
      setState(() => _index = i);
    }
  }

  void _end() => setState(() => _index = null);

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final onDeep = Theme.of(context).colorScheme.onPrimary;
    return Semantics(
      label: widget.semanticsLabel,
      excludeSemantics: true,
      child: LayoutBuilder(
        builder: (context, constraints) {
          final width = constraints.maxWidth;
          final index = _index;
          return GestureDetector(
            behavior: HitTestBehavior.opaque,
            onLongPressStart: (d) => _scrub(d.localPosition, width),
            onLongPressMoveUpdate: (d) => _scrub(d.localPosition, width),
            onLongPressEnd: (_) => _end(),
            onHorizontalDragStart: (d) => _scrub(d.localPosition, width),
            onHorizontalDragUpdate: (d) => _scrub(d.localPosition, width),
            onHorizontalDragEnd: (_) => _end(),
            onHorizontalDragCancel: _end,
            child: SizedBox(
              height: widget.height,
              child: Stack(
                clipBehavior: Clip.none,
                children: [
                  Positioned.fill(
                    child: RepaintBoundary(
                      child: CustomPaint(
                        painter: _DetailPainter(
                          values: widget.values,
                          ref: widget.ref,
                          color: widget.color,
                          refColor: fira.marketFlat,
                          index: index,
                          crossColor: onDeep.withValues(alpha: .6),
                        ),
                      ),
                    ),
                  ),
                  if (index != null)
                    Positioned(
                      top: 0,
                      left: 0,
                      right: 0,
                      child: Align(
                        alignment: Alignment(((index / (widget.values.length - 1)) * 2 - 1).clamp(-.85, .85), -1),
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(color: onDeep, borderRadius: BorderRadius.circular(8)),
                          child: Text(
                            widget.label(index),
                            style: TextStyle(color: fira.brandDeep, fontSize: 12.5, fontWeight: FontWeight.w700),
                          ),
                        ),
                      ),
                    ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}

class _DetailPainter extends CustomPainter {
  _DetailPainter({required this.values, required this.ref, required this.color, required this.refColor, required this.index, required this.crossColor});

  final List<double> values;
  final double? ref;
  final Color color;
  final Color refColor;
  final int? index;
  final Color crossColor;

  @override
  void paint(Canvas canvas, Size size) {
    final g = SparklineGeometry.compute(values, ref, size, padding: 8);
    if (g == null) return;
    final line = Path()..moveTo(g.points.first.dx, g.points.first.dy);
    for (final p in g.points.skip(1)) {
      line.lineTo(p.dx, p.dy);
    }
    final area = Path.from(line)
      ..lineTo(g.points.last.dx, size.height - 8)
      ..lineTo(g.points.first.dx, size.height - 8)
      ..close();
    canvas.drawPath(
      area,
      Paint()..shader = ui.Gradient.linear(Offset.zero, Offset(0, size.height), [color.withValues(alpha: .26), color.withValues(alpha: 0)]),
    );
    if (g.refY != null) {
      final dash = Paint()
        ..color = refColor
        ..strokeWidth = 1;
      for (double x = 8; x < size.width - 8; x += 6) {
        canvas.drawLine(Offset(x, g.refY!), Offset((x + 3).clamp(0, size.width - 8), g.refY!), dash);
      }
    }
    canvas.drawPath(
      line,
      Paint()
        ..color = color
        ..style = PaintingStyle.stroke
        ..strokeWidth = 2
        ..strokeJoin = StrokeJoin.round,
    );
    final selected = index == null ? g.points.last : g.points[index!.clamp(0, g.points.length - 1)];
    if (index != null) {
      canvas.drawLine(
        Offset(selected.dx, 0),
        Offset(selected.dx, size.height),
        Paint()
          ..color = crossColor
          ..strokeWidth = 1,
      );
    }
    canvas.drawCircle(selected, index == null ? 3 : 5, Paint()..color = color);
  }

  @override
  bool shouldRepaint(_DetailPainter old) => old.index != index || !listEquals(old.values, values) || old.ref != ref || old.color != color;
}
