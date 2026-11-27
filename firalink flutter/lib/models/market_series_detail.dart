import 'market_watch.dart' show MarketDirection, MarketWatchStatus;

/// One instrument's detail from `GET /api/market/instruments/{key}/series` (schema 1): stored points
/// for a range, statistics and an optional unit with the NBE rate it used. No number is computed here.
class MarketSeriesDetail {
  const MarketSeriesDetail({
    required this.key,
    required this.name,
    required this.shortName,
    required this.range,
    required this.ranges,
    required this.unitKey,
    required this.unitLabel,
    required this.units,
    required this.fx,
    required this.decimals,
    required this.status,
    required this.statusLabel,
    required this.asOf,
    required this.sourceAttribution,
    required this.price,
    required this.change,
    required this.changePct,
    required this.direction,
    required this.points,
    required this.prevClose,
    required this.high52w,
    required this.low52w,
    required this.avg20d,
    required this.disclaimer,
  });

  final String key;
  final String name;
  final String shortName;
  final String range;
  final List<String> ranges;
  final String? unitKey;
  final String unitLabel;
  final List<({String key, String label})> units;
  final ({double rate, DateTime? asOf, String source})? fx;
  final int decimals;
  final MarketWatchStatus status;
  final String statusLabel;
  final DateTime? asOf;
  final String sourceAttribution;
  final double? price;
  final double? change;
  final double? changePct;
  final MarketDirection direction;
  final List<({DateTime t, double v})> points;
  final double? prevClose;
  final double? high52w;
  final double? low52w;
  final double? avg20d;
  final String disclaimer;

  static double? _num(Object? v) => v is num ? v.toDouble() : (v is String ? double.tryParse(v) : null);

  factory MarketSeriesDetail.fromJson(Map<String, dynamic> json) {
    final d = ((json['data'] ?? json) as Map).cast<String, dynamic>();
    if ((d['schema'] as num?)?.toInt() != 1) {
      throw FormatException('Unsupported market series schema: ${d['schema']}');
    }
    final unit = (d['unit'] as Map?)?.cast<String, dynamic>() ?? const {};
    final stats = (d['stats'] as Map?)?.cast<String, dynamic>() ?? const {};
    final fx = (d['fx'] as Map?)?.cast<String, dynamic>();
    final status = switch (d['status']?.toString()) {
      'live' => MarketWatchStatus.live,
      'delayed' => MarketWatchStatus.delayed,
      'daily_close' => MarketWatchStatus.dailyClose,
      'schedule' => MarketWatchStatus.schedule,
      'stale' => MarketWatchStatus.stale,
      _ => MarketWatchStatus.unavailable,
    };
    final price = status == MarketWatchStatus.unavailable ? null : _num(d['price']);
    return MarketSeriesDetail(
      key: d['key']?.toString() ?? '',
      name: d['name']?.toString() ?? '',
      shortName: d['short_name']?.toString() ?? '',
      range: d['range']?.toString() ?? '1M',
      ranges: ((d['ranges'] as List?) ?? const []).map((e) => e.toString()).toList(growable: false),
      unitKey: unit['key']?.toString(),
      unitLabel: unit['label']?.toString() ?? '',
      units: [
        for (final u in (d['units'] as List?) ?? const [])
          if (u is Map) (key: u['key'].toString(), label: u['label'].toString()),
      ],
      fx: fx == null || _num(fx['rate']) == null
          ? null
          : (rate: _num(fx['rate'])!, asOf: DateTime.tryParse(fx['as_of']?.toString() ?? ''), source: fx['source']?.toString() ?? ''),
      decimals: (d['decimals'] as num?)?.toInt().clamp(0, 6) ?? 2,
      status: status,
      statusLabel: d['status_label']?.toString() ?? '',
      asOf: DateTime.tryParse(d['as_of']?.toString() ?? ''),
      sourceAttribution: ((d['source'] as Map?)?['attribution'])?.toString() ?? '',
      price: price,
      change: price == null ? null : _num(d['change']),
      changePct: price == null ? null : _num(d['change_pct']),
      direction: switch (d['direction']?.toString()) {
        'up' => MarketDirection.up,
        'down' => MarketDirection.down,
        'flat' => MarketDirection.flat,
        _ => MarketDirection.none,
      },
      points: price == null
          ? const []
          : [
              for (final p in (d['points'] as List?) ?? const [])
                if (p is Map && _num(p['v']) != null && DateTime.tryParse(p['t']?.toString() ?? '') != null)
                  (t: DateTime.parse(p['t'].toString()), v: _num(p['v'])!),
            ],
      prevClose: _num(stats['prev_close']),
      high52w: _num(stats['high_52w']),
      low52w: _num(stats['low_52w']),
      avg20d: _num(stats['avg_20d']),
      disclaimer: d['disclaimer']?.toString() ?? '',
    );
  }
}
