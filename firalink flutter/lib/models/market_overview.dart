/// Coffee market data from GET /api/market/overview and /api/market/series.
/// Every number comes with its source, source time and freshness from the server.
/// The app never computes, estimates or fills in a price.
library;

enum MarketFreshness { live, delayed, dailyClose, stale, unavailable }

enum MarketTrendLabel { bullish, bearish, sideways, notEnoughData }

MarketFreshness _freshness(String? value) {
  switch (value) {
    case 'live':
      return MarketFreshness.live;
    case 'delayed':
      return MarketFreshness.delayed;
    case 'daily_close':
      return MarketFreshness.dailyClose;
    case 'stale':
      return MarketFreshness.stale;
    default:
      return MarketFreshness.unavailable;
  }
}

MarketTrendLabel _trend(String? value) {
  switch (value) {
    case 'bullish':
      return MarketTrendLabel.bullish;
    case 'bearish':
      return MarketTrendLabel.bearish;
    case 'sideways':
      return MarketTrendLabel.sideways;
    default:
      return MarketTrendLabel.notEnoughData;
  }
}

DateTime? _time(dynamic value) =>
    value is String && value.isNotEmpty ? DateTime.tryParse(value) : null;

class MarketSourceInfo {
  const MarketSourceInfo({required this.name, required this.url, required this.attribution});

  final String name;
  final String url;
  final String attribution;

  factory MarketSourceInfo.fromJson(Map<String, dynamic> json) => MarketSourceInfo(
        name: json['name']?.toString() ?? '',
        url: json['url']?.toString() ?? '',
        attribution: (json['attribution'] ?? json['name'])?.toString() ?? '',
      );

  Map<String, dynamic> toJson() => {'name': name, 'url': url, 'attribution': attribution};
}

class MarketBenchmark {
  const MarketBenchmark({
    required this.symbol,
    required this.name,
    required this.priceType,
    required this.value,
    required this.currency,
    required this.unit,
    required this.isHero,
    required this.source,
    required this.sourceTime,
    required this.freshness,
    required this.freshnessLabel,
    required this.delayMinutes,
    required this.trend,
  });

  final String symbol;
  final String name;
  final String priceType;

  /// Exact value as sent by the server (string decimal), or null when there is no current price.
  final String? value;
  final String currency;
  final String unit;
  final bool isHero;
  final MarketSourceInfo source;
  final DateTime? sourceTime;
  final MarketFreshness freshness;
  final String freshnessLabel;
  final int? delayMinutes;
  final MarketTrendLabel trend;

  /// A number may be shown only when the server sent one and it is not Unavailable.
  bool get hasValue => value != null && freshness != MarketFreshness.unavailable;

  bool get isDaily => priceType == 'daily_close' || priceType == 'weekly_regulatory_floor';

  factory MarketBenchmark.fromJson(Map<String, dynamic> json) {
    final freshness = (json['freshness'] as Map?)?.cast<String, dynamic>() ?? const {};
    final trend = (json['trend'] as Map?)?.cast<String, dynamic>() ?? const {};
    final rawValue = json['value'];
    return MarketBenchmark(
      symbol: json['symbol']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      priceType: json['price_type']?.toString() ?? '',
      value: rawValue?.toString(),
      currency: json['currency']?.toString() ?? '',
      unit: json['unit']?.toString() ?? '',
      isHero: json['is_hero'] == true,
      source: MarketSourceInfo.fromJson(((json['source'] as Map?) ?? const {}).cast<String, dynamic>()),
      sourceTime: _time(json['source_time']),
      freshness: _freshness(freshness['status']?.toString()),
      freshnessLabel: freshness['label']?.toString() ?? '',
      delayMinutes: (freshness['delay_minutes'] as num?)?.toInt(),
      trend: _trend(trend['label']?.toString()),
    );
  }

  /// The same row, marked Stale because it comes from the app's cache (shown with its real time).
  MarketBenchmark asCachedStale() => MarketBenchmark(
        symbol: symbol,
        name: name,
        priceType: priceType,
        value: value,
        currency: currency,
        unit: unit,
        isHero: isHero,
        source: source,
        sourceTime: sourceTime,
        freshness: freshness == MarketFreshness.unavailable ? MarketFreshness.unavailable : MarketFreshness.stale,
        freshnessLabel: '',
        delayMinutes: delayMinutes,
        trend: trend,
      );
}

class MarketOverview {
  const MarketOverview({
    required this.hero,
    required this.benchmarks,
    required this.summaryText,
    required this.summaryRows,
    required this.disclaimer,
    required this.generatedAt,
  });

  final MarketBenchmark? hero;
  final List<MarketBenchmark> benchmarks;
  final String summaryText;
  final List<String> summaryRows;
  final String disclaimer;
  final DateTime? generatedAt;

  factory MarketOverview.fromJson(Map<String, dynamic> json) {
    final data = ((json['data'] ?? json) as Map).cast<String, dynamic>();
    final hero = data['hero'];
    final summary = (data['summary'] as Map?)?.cast<String, dynamic>() ?? const {};
    return MarketOverview(
      hero: hero is Map ? MarketBenchmark.fromJson(hero.cast<String, dynamic>()) : null,
      benchmarks: (data['benchmarks'] as List? ?? const [])
          .whereType<Map>()
          .map((row) => MarketBenchmark.fromJson(row.cast<String, dynamic>()))
          .toList(),
      summaryText: summary['text']?.toString() ?? '',
      summaryRows: (summary['source_rows'] as List? ?? const []).map((e) => e.toString()).toList(),
      disclaimer: data['disclaimer']?.toString() ?? '',
      generatedAt: _time(data['generated_at']),
    );
  }

  MarketOverview asCachedStale() => MarketOverview(
        hero: hero?.asCachedStale(),
        benchmarks: benchmarks.map((b) => b.asCachedStale()).toList(),
        summaryText: summaryText,
        summaryRows: summaryRows,
        disclaimer: disclaimer,
        generatedAt: generatedAt,
      );

  /// Distinct attribution names of every source shown on the page.
  List<String> get attributions =>
      {for (final b in benchmarks) if (b.source.attribution.isNotEmpty) b.source.attribution}.toList();
}

class MarketPoint {
  const MarketPoint(this.value, this.time);

  final double value;
  final DateTime time;
}

class MarketSeriesData {
  const MarketSeriesData({required this.symbol, required this.range, required this.points});

  final String symbol;
  final String range;
  final List<MarketPoint> points;

  factory MarketSeriesData.fromJson(Map<String, dynamic> json) {
    final data = ((json['data'] ?? json) as Map).cast<String, dynamic>();
    final series = (data['series'] as Map?)?.cast<String, dynamic>() ?? const {};
    final points = <MarketPoint>[];
    for (final raw in (data['points'] as List? ?? const [])) {
      if (raw is! Map) continue;
      final value = double.tryParse(raw['value']?.toString() ?? '');
      final time = _time(raw['source_time']);
      if (value != null && time != null) points.add(MarketPoint(value, time));
    }
    return MarketSeriesData(
      symbol: series['symbol']?.toString() ?? '',
      range: data['range']?.toString() ?? '',
      points: points,
    );
  }
}

/// What the screen shows: the overview, and whether it came from the app's cache after a failed request.
class MarketSnapshot {
  const MarketSnapshot({required this.overview, required this.fromCache, required this.receivedAt});

  final MarketOverview overview;
  final bool fromCache;

  /// When the app received this response from the server (the cache keeps the real time).
  final DateTime receivedAt;
}
