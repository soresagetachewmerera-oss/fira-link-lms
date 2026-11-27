/// Market watch rows from `GET /api/market/watch` (schema 1, built by the server's MarketWatchService).
/// Every number here was stored on the server with its source and time. A row without a price has no
/// number at all; the app never fills one in.
library;

enum MarketWatchStatus { live, delayed, dailyClose, schedule, stale, unavailable }

enum MarketDirection { up, down, flat, none }

MarketWatchStatus _status(String? value) => switch (value) {
      'live' => MarketWatchStatus.live,
      'delayed' => MarketWatchStatus.delayed,
      'daily_close' => MarketWatchStatus.dailyClose,
      'schedule' => MarketWatchStatus.schedule,
      'stale' => MarketWatchStatus.stale,
      _ => MarketWatchStatus.unavailable,
    };

MarketDirection _direction(String? value) => switch (value) {
      'up' => MarketDirection.up,
      'down' => MarketDirection.down,
      'flat' => MarketDirection.flat,
      _ => MarketDirection.none,
    };

double? _num(Object? value) {
  if (value is num) return value.toDouble();
  if (value is String) return double.tryParse(value);
  return null;
}

class MarketWatchRow {
  const MarketWatchRow({
    required this.key,
    required this.name,
    required this.shortName,
    required this.unitLabel,
    required this.decimals,
    required this.price,
    required this.prevClose,
    required this.change,
    required this.changePct,
    required this.direction,
    required this.status,
    required this.statusLabel,
    required this.asOf,
    required this.sourceName,
    required this.sourceAttribution,
    required this.sparkline,
    required this.sparkRef,
    required this.onHome,
    required this.a11yLabel,
  });

  final String key;
  final String name;
  final String shortName;
  final String unitLabel;
  final int decimals;
  final double? price;
  final double? prevClose;
  final double? change;
  final double? changePct;
  final MarketDirection direction;
  final MarketWatchStatus status;
  final String statusLabel;
  final DateTime? asOf;
  final String sourceName;
  final String sourceAttribution;
  final List<double> sparkline;
  final double? sparkRef;
  final bool onHome;
  final String a11yLabel;

  bool get hasPrice => price != null;

  /// "International Coffee Organization (ICO)" -> "ICO"; otherwise the source name.
  String get sourceShort {
    final match = RegExp(r'\(([^)]+)\)\s*$').firstMatch(sourceAttribution);
    return match?.group(1) ?? sourceName;
  }

  factory MarketWatchRow.fromJson(Map<String, dynamic> json) {
    final source = (json['source'] as Map?)?.cast<String, dynamic>() ?? const {};
    final price = _num(json['price']);
    final status = _status(json['status']?.toString());
    final unavailable = price == null || status == MarketWatchStatus.unavailable;
    return MarketWatchRow(
      key: json['key']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      shortName: json['short_name']?.toString() ?? json['name']?.toString() ?? '',
      unitLabel: json['unit_label']?.toString() ?? '',
      decimals: (json['decimals'] as num?)?.toInt().clamp(0, 6) ?? 2,
      // An unavailable row carries no number, even if a client somewhere sent one.
      price: unavailable ? null : price,
      prevClose: unavailable ? null : _num(json['prev_close']),
      change: unavailable ? null : _num(json['change']),
      changePct: unavailable ? null : _num(json['change_pct']),
      direction: unavailable ? MarketDirection.none : _direction(json['direction']?.toString()),
      status: unavailable ? MarketWatchStatus.unavailable : status,
      statusLabel: json['status_label']?.toString() ?? '',
      asOf: unavailable ? null : DateTime.tryParse(json['as_of']?.toString() ?? ''),
      sourceName: source['name']?.toString() ?? '',
      sourceAttribution: source['attribution']?.toString() ?? '',
      sparkline: unavailable
          ? const []
          : ((json['sparkline'] as List?) ?? const []).map(_num).whereType<double>().toList(growable: false),
      sparkRef: unavailable ? null : _num(json['spark_ref']),
      onHome: json['on_home'] == true,
      a11yLabel: json['a11y_label']?.toString() ?? '',
    );
  }
}

class MarketWatch {
  const MarketWatch({
    required this.generatedAt,
    required this.status,
    required this.statusLabel,
    required this.sources,
    required this.disclaimer,
    required this.homeVisible,
    required this.homeKeys,
    required this.rows,
  });

  static const supportedSchema = 1;

  final DateTime? generatedAt;
  final MarketWatchStatus status;
  final String statusLabel;
  final List<String> sources;
  final String disclaimer;
  final bool homeVisible;
  final List<String> homeKeys;
  final List<MarketWatchRow> rows;

  /// The Home rows in the server's order.
  List<MarketWatchRow> get homeRows => [
        for (final key in homeKeys) ...rows.where((r) => r.key == key),
      ];

  /// Short source names for the footer: "ICO, ECTA, NBE".
  List<String> get sourcesShort => [
        for (final s in sources) RegExp(r'\(([^)]+)\)\s*$').firstMatch(s)?.group(1) ?? s,
      ];

  factory MarketWatch.fromJson(Map<String, dynamic> json) {
    final data = ((json['data'] ?? json) as Map).cast<String, dynamic>();
    final schema = (data['schema'] as num?)?.toInt();
    if (schema != supportedSchema) {
      // A newer contract may mean something else by the same fields: show nothing rather than guess.
      throw FormatException('Unsupported market watch schema: $schema');
    }
    final status = (data['status'] as Map?)?.cast<String, dynamic>() ?? const {};
    final home = (data['home'] as Map?)?.cast<String, dynamic>() ?? const {};
    return MarketWatch(
      generatedAt: DateTime.tryParse(data['generated_at']?.toString() ?? ''),
      status: _status(status['status']?.toString()),
      statusLabel: status['label']?.toString() ?? '',
      sources: ((data['sources'] as List?) ?? const []).map((e) => e.toString()).toList(growable: false),
      disclaimer: data['disclaimer']?.toString() ?? '',
      homeVisible: home['visible'] == true,
      homeKeys: ((home['keys'] as List?) ?? const []).map((e) => e.toString()).toList(growable: false),
      rows: ((data['rows'] as List?) ?? const [])
          .whereType<Map>()
          .map((r) => MarketWatchRow.fromJson(r.cast<String, dynamic>()))
          .toList(growable: false),
    );
  }
}

/// What the app shows: the response, whether it came from the device cache, and when it was received.
class MarketWatchSnapshot {
  const MarketWatchSnapshot({required this.watch, required this.fromCache, required this.receivedAt});

  final MarketWatch watch;
  final bool fromCache;
  final DateTime receivedAt;
}
