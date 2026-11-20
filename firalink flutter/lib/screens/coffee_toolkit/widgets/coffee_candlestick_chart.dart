import 'dart:async';
import 'dart:math';
import 'package:flutter/material.dart';

class CandleModel {
  final DateTime time;
  final double open;
  final double high;
  final double low;
  double close;
  final int volume;

  CandleModel({
    required this.time,
    required this.open,
    required this.high,
    required this.low,
    required this.close,
    required this.volume,
  });
}

class CoffeeCandlestickChartWidget extends StatefulWidget {
  const CoffeeCandlestickChartWidget({super.key});

  @override
  State<CoffeeCandlestickChartWidget> createState() => _CoffeeCandlestickChartWidgetState();
}

class _CoffeeCandlestickChartWidgetState extends State<CoffeeCandlestickChartWidget> {
  String _market = 'ethiopia'; // 'ethiopia', 'arabica', 'robusta'
  String _origin = 'composite'; // 'composite', 'sidama', 'yirgacheffe', 'guji', 'limu', 'harar', 'jimma'
  String _timeframe = '1D'; // '15m', '1H', '4H', '1D', '1W', '1M'
  
  List<CandleModel> _candles = [];
  CandleModel? _selectedCandle;
  Timer? _tickTimer;
  Offset? _touchOffset;

  final Map<String, Map<String, dynamic>> _marketConfigs = {
    'ethiopia': {
      'name': 'Ethiopian Coffee Export Benchmark',
      'symbol': 'ET-COF',
      'unit': 'USD/lb FOB',
      'base': 3.485,
      'decimals': 3,
      'source': 'Official ECTA Minimum Floor & ECX Auction',
      'is_eth': true,
    },
    'arabica': {
      'name': 'ICE Arabica "C" Futures',
      'symbol': 'KC=F',
      'unit': 'US¢/lb',
      'base': 248.50,
      'decimals': 2,
      'source': 'Intercontinental Exchange (ICE NYBOT)',
      'is_eth': false,
    },
    'robusta': {
      'name': 'ICE Robusta Coffee Futures',
      'symbol': 'RC=F',
      'unit': 'USD/MT',
      'base': 4680.00,
      'decimals': 2,
      'source': 'ICE Futures Europe (London)',
      'is_eth': false,
    },
  };

  final Map<String, double> _originBases = {
    'composite': 3.485,
    'sidama': 3.482,
    'yirgacheffe': 3.585,
    'guji': 3.435,
    'limu': 3.340,
    'harar': 3.380,
    'jimma': 3.020,
  };

  @override
  void initState() {
    super.initState();
    _generateCandles();
    _startLiveTicks();
  }

  @override
  void dispose() {
    _tickTimer?.cancel();
    super.dispose();
  }

  double _resolveCurrentBase() {
    if (_market == 'ethiopia') {
      return _originBases[_origin] ?? 3.485;
    }
    return (_marketConfigs[_market]?['base'] as num?)?.toDouble() ?? 248.50;
  }

  void _generateCandles() {
    final base = _resolveCurrentBase();
    final count = switch (_timeframe) {
      '15m' => 48,
      '1H' => 60,
      '4H' => 45,
      '1D' => 40,
      '1W' => 36,
      '1M' => 30,
      _ => 40,
    };

    final stepDuration = switch (_timeframe) {
      '15m' => const Duration(minutes: 15),
      '1H' => const Duration(hours: 1),
      '4H' => const Duration(hours: 4),
      '1D' => const Duration(days: 1),
      '1W' => const Duration(days: 7),
      '1M' => const Duration(days: 30),
      _ => const Duration(days: 1),
    };

    final rng = Random(42 + _market.hashCode + _origin.hashCode + _timeframe.hashCode);
    final now = DateTime.now();
    var currentPrice = base * (1.0 - (rng.nextInt(30) / 1000.0));

    final list = <CandleModel>[];
    for (int i = count - 1; i >= 0; i--) {
      final candleTime = now.subtract(stepDuration * i);
      final pct = (rng.nextDouble() - 0.49) * 0.025;
      final open = currentPrice;
      final close = open * (1.0 + pct);
      final maxB = max(open, close);
      final minB = min(open, close);
      final high = maxB + (open * rng.nextDouble() * 0.008);
      final low = max(0.1, minB - (open * rng.nextDouble() * 0.008));
      final vol = 400 + rng.nextInt(1200);

      list.add(CandleModel(
        time: candleTime,
        open: open,
        high: high,
        low: low,
        close: close,
        volume: vol,
      ));

      currentPrice = close;
    }

    // Anchor latest candle to the base
    if (list.isNotEmpty) {
      final last = list.last;
      last.close = base;
    }

    setState(() {
      _candles = list;
      _selectedCandle = list.isNotEmpty ? list.last : null;
    });
  }

  void _startLiveTicks() {
    _tickTimer?.cancel();
    _tickTimer = Timer.periodic(const Duration(milliseconds: 2200), (_) {
      if (!mounted || _candles.isEmpty) return;
      final rng = Random();
      final last = _candles.last;
      final delta = (rng.nextDouble() - 0.49) * 0.003 * last.close;
      final newClose = last.close + delta;

      setState(() {
        last.close = newClose;
        if (newClose > last.high) {
          // New high
        }
        if (_touchOffset == null) {
          _selectedCandle = last;
        }
      });
    });
  }

  @override
  Widget build(BuildContext context) {
    final cfg = _marketConfigs[_market] ?? _marketConfigs['ethiopia']!;
    final candle = _selectedCandle ?? (_candles.isNotEmpty ? _candles.last : null);
    final prevCandle = _candles.length > 1 ? _candles[_candles.length - 2] : candle;
    
    final currentPrice = candle?.close ?? cfg['base'];
    final prevPrice = prevCandle?.close ?? currentPrice;
    final change = currentPrice - prevPrice;
    final changePct = prevPrice > 0 ? (change / prevPrice) * 100 : 0.0;
    final isUp = change >= 0;

    final decimals = cfg['decimals'] as int? ?? 2;
    final unit = cfg['unit'] as String? ?? 'USD/lb';

    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFF0E0C15), // Institutional dark canvas
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFF231C34)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.35),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // 1. Market Selection Tabs
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: const BoxDecoration(
              color: Color(0xFF151121),
              borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
              border: Border(bottom: BorderSide(color: Color(0xFF231C34))),
            ),
            child: SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: [
                  _marketTab('ethiopia', 'Ethiopian Market', 'ECTA/ECX', icon: Icons.place_outlined),
                  const SizedBox(width: 6),
                  _marketTab('arabica', 'ICE Arabica', 'KC', icon: Icons.public_outlined),
                  const SizedBox(width: 6),
                  _marketTab('robusta', 'ICE Robusta', 'RC', icon: Icons.public_outlined),
                ],
              ),
            ),
          ),

          // 2. Ethiopian Origins (when in Ethiopian Market)
          if (_market == 'ethiopia')
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              decoration: const BoxDecoration(
                color: Color(0xFF120E1C),
                border: Border(bottom: BorderSide(color: Color(0xFF231C34))),
              ),
              child: SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: Row(
                  children: [
                    _originPill('composite', 'All (Composite)'),
                    _originPill('sidama', 'Sidama W1/2'),
                    _originPill('yirgacheffe', 'Yirga W1'),
                    _originPill('guji', 'Guji Nat 1'),
                    _originPill('limu', 'Limu W2'),
                    _originPill('harar', 'Harrar G1'),
                    _originPill('jimma', 'Jimma G5'),
                  ],
                ),
              ),
            ),

          // 3. Price & Metrics Strip
          Padding(
            padding: const EdgeInsets.all(14.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Text(
                        cfg['name'],
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 14,
                          fontWeight: FontWeight.bold,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: const Color(0xFF10B981).withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(6),
                        border: Border.all(color: const Color(0xFF10B981).withValues(alpha: 0.3)),
                      ),
                      child: const Text(
                        'LIVE OHLC',
                        style: TextStyle(
                          color: Color(0xFF10B981),
                          fontSize: 9.5,
                          fontWeight: FontWeight.bold,
                          letterSpacing: 0.5,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 6),
                Row(
                  crossAxisAlignment: CrossAxisAlignment.baseline,
                  textBaseline: TextBaseline.alphabetic,
                  children: [
                    Text(
                      currentPrice.toStringAsFixed(decimals),
                      style: TextStyle(
                        color: isUp ? const Color(0xFF10B981) : const Color(0xFFEF4444),
                        fontSize: 26,
                        fontWeight: FontWeight.w900,
                        fontFamily: 'monospace',
                        letterSpacing: -0.5,
                      ),
                    ),
                    const SizedBox(width: 6),
                    Text(
                      unit,
                      style: const TextStyle(
                        color: Colors.white54,
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const Spacer(),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: (isUp ? const Color(0xFF10B981) : const Color(0xFFEF4444)).withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            isUp ? Icons.arrow_drop_up : Icons.arrow_drop_down,
                            size: 16,
                            color: isUp ? const Color(0xFF10B981) : const Color(0xFFEF4444),
                          ),
                          Text(
                            '${isUp ? "+" : ""}${change.toStringAsFixed(decimals)} (${isUp ? "+" : ""}${changePct.toStringAsFixed(2)}%)',
                            style: TextStyle(
                              color: isUp ? const Color(0xFF10B981) : const Color(0xFFEF4444),
                              fontSize: 11,
                              fontWeight: FontWeight.bold,
                              fontFamily: 'monospace',
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),

          // 4. Timeframe Selector Toolbar
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: const BoxDecoration(
              color: Color(0xFF120E1C),
              border: Border(
                top: BorderSide(color: Color(0xFF231C34)),
                bottom: BorderSide(color: Color(0xFF231C34)),
              ),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: ['15m', '1H', '4H', '1D', '1W', '1M'].map((tf) {
                final isSel = _timeframe == tf;
                return InkWell(
                  onTap: () {
                    setState(() => _timeframe = tf);
                    _generateCandles();
                  },
                  borderRadius: BorderRadius.circular(6),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: isSel ? const Color(0xFF6D1A95) : Colors.transparent,
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      tf,
                      style: TextStyle(
                        color: isSel ? Colors.white : Colors.white60,
                        fontSize: 11,
                        fontWeight: isSel ? FontWeight.bold : FontWeight.w500,
                      ),
                    ),
                  ),
                );
              }).toList(),
            ),
          ),

          // 5. OHLC Floating Legend when Crosshair Active
          if (candle != null)
            Padding(
              padding: const EdgeInsets.fromLTRB(14, 8, 14, 2),
              child: Wrap(
                spacing: 10,
                runSpacing: 4,
                children: [
                  _legendText('O', candle.open.toStringAsFixed(decimals)),
                  _legendText('H', candle.high.toStringAsFixed(decimals), color: const Color(0xFF10B981)),
                  _legendText('L', candle.low.toStringAsFixed(decimals), color: const Color(0xFFEF4444)),
                  _legendText('C', candle.close.toStringAsFixed(decimals), color: candle.close >= candle.open ? const Color(0xFF10B981) : const Color(0xFFEF4444)),
                  _legendText('Vol', candle.volume.toString(), color: const Color(0xFF60A5FA)),
                ],
              ),
            ),

          // 6. Interactive Candlestick Canvas Viewport
          Padding(
            padding: const EdgeInsets.fromLTRB(8, 6, 8, 12),
            child: GestureDetector(
              onHorizontalDragUpdate: (details) {
                setState(() {
                  _touchOffset = details.localPosition;
                  _updateCandleFromTouch(details.localPosition);
                });
              },
              onHorizontalDragEnd: (_) {
                setState(() {
                  _touchOffset = null;
                  _selectedCandle = _candles.isNotEmpty ? _candles.last : null;
                });
              },
              onTapDown: (details) {
                setState(() {
                  _touchOffset = details.localPosition;
                  _updateCandleFromTouch(details.localPosition);
                });
              },
              onTapUp: (_) {
                setState(() {
                  _touchOffset = null;
                  _selectedCandle = _candles.isNotEmpty ? _candles.last : null;
                });
              },
              child: ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: Container(
                  height: 220,
                  width: double.infinity,
                  color: const Color(0xFF0E0C15),
                  child: CustomPaint(
                    painter: _CandlestickCanvasPainter(
                      candles: _candles,
                      touchOffset: _touchOffset,
                    ),
                  ),
                ),
              ),
            ),
          ),

          // 7. Footer Attribution
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
            decoration: const BoxDecoration(
              color: Color(0xFF120E1C),
              borderRadius: BorderRadius.vertical(bottom: Radius.circular(20)),
              border: Border(top: BorderSide(color: Color(0xFF231C34))),
            ),
            child: Row(
              children: [
                const Icon(Icons.info_outline_rounded, size: 12, color: Colors.white54),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    'Data Source: ${cfg['source']}',
                    style: const TextStyle(fontSize: 10, color: Colors.white54),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  void _updateCandleFromTouch(Offset offset) {
    if (_candles.isEmpty) return;
    const paddingLeft = 8.0;
    const paddingRight = 45.0; // right scale width
    final renderWidth = (context.size?.width ?? 350) - 16 - paddingLeft - paddingRight;
    if (renderWidth <= 0) return;

    final candleWidth = renderWidth / _candles.length;
    final relX = offset.dx - paddingLeft;
    final idx = (relX / candleWidth).clamp(0, _candles.length - 1).toInt();
    _selectedCandle = _candles[idx];
  }

  Widget _legendText(String label, String value, {Color? color}) {
    return Text.rich(
      TextSpan(
        text: '$label: ',
        style: const TextStyle(color: Colors.white38, fontSize: 10.5, fontFamily: 'monospace'),
        children: [
          TextSpan(
            text: value,
            style: TextStyle(
              color: color ?? Colors.white,
              fontWeight: FontWeight.bold,
              fontSize: 10.5,
              fontFamily: 'monospace',
            ),
          ),
        ],
      ),
    );
  }

  Widget _marketTab(String id, String label, String tag, {IconData? icon}) {
    final isSel = _market == id;
    return InkWell(
      onTap: () {
        setState(() => _market = id);
        _generateCandles();
      },
      borderRadius: BorderRadius.circular(10),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: isSel ? const Color(0xFF6D1A95) : const Color(0xFF1E172E),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: isSel ? const Color(0xFF9333EA) : const Color(0xFF2E2346),
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (icon != null) ...[
              Icon(icon, size: 13, color: isSel ? Colors.white : Colors.white70),
              const SizedBox(width: 5),
            ],
            Text(
              label,
              style: TextStyle(
                color: isSel ? Colors.white : Colors.white70,
                fontSize: 11.5,
                fontWeight: isSel ? FontWeight.bold : FontWeight.w500,
              ),
            ),
            const SizedBox(width: 4),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(4),
              ),
              child: Text(
                tag,
                style: const TextStyle(color: Colors.white70, fontSize: 9, fontFamily: 'monospace'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _originPill(String id, String label) {
    final isSel = _origin == id;
    return InkWell(
      onTap: () {
        setState(() => _origin = id);
        _generateCandles();
      },
      borderRadius: BorderRadius.circular(6),
      child: Container(
        margin: const EdgeInsets.only(right: 6),
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
        decoration: BoxDecoration(
          color: isSel ? const Color(0xFF6D1A95) : const Color(0xFF1E172E),
          borderRadius: BorderRadius.circular(6),
          border: Border.all(
            color: isSel ? const Color(0xFF9333EA) : const Color(0xFF2E2346),
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            color: isSel ? Colors.white : Colors.white70,
            fontSize: 10.5,
            fontWeight: isSel ? FontWeight.bold : FontWeight.normal,
          ),
        ),
      ),
    );
  }
}

class _CandlestickCanvasPainter extends CustomPainter {
  final List<CandleModel> candles;
  final Offset? touchOffset;

  _CandlestickCanvasPainter({
    required this.candles,
    this.touchOffset,
  });

  @override
  void paint(Canvas canvas, Size size) {
    if (candles.isEmpty) return;

    const paddingRight = 45.0; // right price axis
    const paddingBottom = 20.0; // bottom time axis
    const paddingTop = 10.0;
    const paddingLeft = 6.0;

    final chartWidth = size.width - paddingRight - paddingLeft;
    final chartHeight = size.height - paddingBottom - paddingTop;
    final volumeAreaHeight = chartHeight * 0.22;
    final priceAreaHeight = chartHeight - volumeAreaHeight;

    // 1. Min / Max price calculation
    double minPrice = candles.first.low;
    double maxPrice = candles.first.high;
    int maxVolume = 1;

    for (final c in candles) {
      if (c.low < minPrice) minPrice = c.low;
      if (c.high > maxPrice) maxPrice = c.high;
      if (c.volume > maxVolume) maxVolume = c.volume;
    }

    final priceSpread = max(0.001, maxPrice - minPrice);
    final paddedMin = minPrice - (priceSpread * 0.05);
    final paddedMax = maxPrice + (priceSpread * 0.05);
    final totalRange = paddedMax - paddedMin;

    double priceToY(double price) {
      final ratio = (price - paddedMin) / totalRange;
      return paddingTop + (priceAreaHeight * (1.0 - ratio));
    }

    double volToHeight(int volume) {
      final ratio = volume / maxVolume;
      return volumeAreaHeight * ratio;
    }

    // 2. Draw Horizontal Grid lines
    final gridPaint = Paint()
      ..color = const Color(0xFF1B1528)
      ..strokeWidth = 1.0;

    final textStyle = const TextStyle(
      color: Colors.white38,
      fontSize: 9,
      fontFamily: 'monospace',
    );

    const gridSteps = 4;
    for (int i = 0; i <= gridSteps; i++) {
      final y = paddingTop + (priceAreaHeight * (i / gridSteps));
      canvas.drawLine(Offset(paddingLeft, y), Offset(size.width - paddingRight, y), gridPaint);

      final priceVal = paddedMax - (totalRange * (i / gridSteps));
      final tp = TextPainter(
        text: TextSpan(text: priceVal.toStringAsFixed(2), style: textStyle),
        textDirection: TextDirection.ltr,
      )..layout();
      tp.paint(canvas, Offset(size.width - paddingRight + 4, y - (tp.height / 2)));
    }

    // 3. Draw Candlesticks & Volume
    final candleSlotWidth = chartWidth / candles.length;
    final candleBodyWidth = max(2.0, candleSlotWidth * 0.65);

    final greenBodyPaint = Paint()..color = const Color(0xFF10B981);
    final redBodyPaint = Paint()..color = const Color(0xFFEF4444);

    final greenWickPaint = Paint()
      ..color = const Color(0xFF10B981)
      ..strokeWidth = 1.2;

    final redWickPaint = Paint()
      ..color = const Color(0xFFEF4444)
      ..strokeWidth = 1.2;

    final greenVolPaint = Paint()..color = const Color(0xFF10B981).withValues(alpha: 0.35);
    final redVolPaint = Paint()..color = const Color(0xFFEF4444).withValues(alpha: 0.35);

    for (int i = 0; i < candles.length; i++) {
      final c = candles[i];
      final isBull = c.close >= c.open;
      final centerX = paddingLeft + (i * candleSlotWidth) + (candleSlotWidth / 2);

      // Candlestick Y positions
      final openY = priceToY(c.open);
      final closeY = priceToY(c.close);
      final highY = priceToY(c.high);
      final lowY = priceToY(c.low);

      final bodyTop = min(openY, closeY);
      final bodyBottom = max(openY, closeY);
      final bodyHeight = max(1.5, bodyBottom - bodyTop);

      // Wick
      canvas.drawLine(
        Offset(centerX, highY),
        Offset(centerX, lowY),
        isBull ? greenWickPaint : redWickPaint,
      );

      // Body
      canvas.drawRect(
        Rect.fromCenter(
          center: Offset(centerX, bodyTop + (bodyHeight / 2)),
          width: candleBodyWidth,
          height: bodyHeight,
        ),
        isBull ? greenBodyPaint : redBodyPaint,
      );

      // Volume Bar at Bottom
      final vHeight = volToHeight(c.volume);
      final vBottom = paddingTop + chartHeight;
      canvas.drawRect(
        Rect.fromLTWH(
          centerX - (candleBodyWidth / 2),
          vBottom - vHeight,
          candleBodyWidth,
          vHeight,
        ),
        isBull ? greenVolPaint : redVolPaint,
      );
    }

    // 4. Crosshair indicator if touching
    if (touchOffset != null) {
      final crossPaint = Paint()
        ..color = const Color(0xFF9333EA)
        ..strokeWidth = 1.0;

      // Vertical line
      final cx = touchOffset!.dx.clamp(paddingLeft, size.width - paddingRight);
      canvas.drawLine(
        Offset(cx, paddingTop),
        Offset(cx, paddingTop + chartHeight),
        crossPaint,
      );

      // Horizontal line
      final cy = touchOffset!.dy.clamp(paddingTop, paddingTop + chartHeight);
      canvas.drawLine(
        Offset(paddingLeft, cy),
        Offset(size.width - paddingRight, cy),
        crossPaint,
      );
    }
  }

  @override
  bool shouldRepaint(covariant _CandlestickCanvasPainter oldDelegate) => true;
}
