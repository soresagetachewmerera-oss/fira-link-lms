import 'dart:ui' as ui;

import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';

/// Geometry of a market watch sparkline, the same rules as the website's SparklinePath (PHP):
/// the scale includes the previous close so its dashed line stays inside the box; fewer than two
/// finite values draw nothing.
@immutable
class SparklineGeometry {
  const SparklineGeometry(this.points, this.refY);

  final List<Offset> points;
  final double? refY;

  static SparklineGeometry? compute(List<double> values, double? ref, Size size, {double padding = 3}) {
    final nums = values.where((v) => v.isFinite).toList(growable: false);
    if (nums.length < 2) return null;
    final hasRef = ref != null && ref.isFinite;
    final scale = [...nums, if (hasRef) ref];
    final min = scale.reduce((a, b) => a < b ? a : b);
    final span = scale.reduce((a, b) => a > b ? a : b) - min;
    final usableW = (size.width - 2 * padding).clamp(1.0, double.infinity);
    final usableH = (size.height - 2 * padding).clamp(1.0, double.infinity);
    double y(double v) => span > 0 ? padding + (1 - (v - min) / span) * usableH : padding + usableH / 2;

    return SparklineGeometry([for (var i = 0; i < nums.length; i++) Offset(padding + (i / (nums.length - 1)) * usableW, y(nums[i]))], hasRef ? y(ref) : null);
  }
}

/// Line with a soft fill under it, a dashed previous-close line and a dot on the latest value.
/// Repaints only when the values or colours change.
class WatchSparklinePainter extends CustomPainter {
  WatchSparklinePainter({required this.values, required this.ref, required this.color, required this.refColor});

  final List<double> values;
  final double? ref;
  final Color color;
  final Color refColor;

  @override
  void paint(Canvas canvas, Size size) {
    final g = SparklineGeometry.compute(values, ref, size);
    if (g == null) return;
    final line = Path()..moveTo(g.points.first.dx, g.points.first.dy);
    for (final p in g.points.skip(1)) {
      line.lineTo(p.dx, p.dy);
    }
    final bottom = size.height - 3;
    final area = Path.from(line)
      ..lineTo(g.points.last.dx, bottom)
      ..lineTo(g.points.first.dx, bottom)
      ..close();
    canvas.drawPath(
      area,
      Paint()..shader = ui.Gradient.linear(Offset.zero, Offset(0, size.height), [color.withValues(alpha: .28), color.withValues(alpha: 0)]),
    );

    if (g.refY != null) {
      final dash = Paint()
        ..color = refColor
        ..strokeWidth = 1;
      for (double x = 3; x < size.width - 3; x += 5) {
        canvas.drawLine(Offset(x, g.refY!), Offset((x + 2).clamp(0, size.width - 3), g.refY!), dash);
      }
    }

    canvas.drawPath(
      line,
      Paint()
        ..color = color
        ..style = PaintingStyle.stroke
        ..strokeWidth = 1.75
        ..strokeJoin = StrokeJoin.round
        ..strokeCap = StrokeCap.round,
    );
    canvas.drawCircle(g.points.last, 2, Paint()..color = color);
  }

  @override
  bool shouldRepaint(WatchSparklinePainter old) => !listEquals(old.values, values) || old.ref != ref || old.color != color || old.refColor != refColor;
}
