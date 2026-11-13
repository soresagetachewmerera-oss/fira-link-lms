enum MarketTrend { positive, negative, neutral }

class MarketChipItem {
  final String label;
  final String value;
  final String? changeText;
  final MarketTrend trend;
  final String? source;
  final DateTime? asOf;

  const MarketChipItem({
    required this.label,
    required this.value,
    this.changeText,
    this.trend = MarketTrend.neutral,
    this.source,
    this.asOf,
  });

  factory MarketChipItem.fromJson(Map<String, dynamic> json) {
    final trendValue = json['trend']?.toString().toLowerCase();
    return MarketChipItem(
      label: json['label']?.toString() ?? '',
      value: json['value']?.toString() ?? '',
      changeText: json['change_text']?.toString(),
      trend: switch (trendValue) {
        'up' || 'positive' => MarketTrend.positive,
        'down' || 'negative' => MarketTrend.negative,
        _ => MarketTrend.neutral,
      },
      source: json['source']?.toString(),
      asOf: DateTime.tryParse(json['as_of']?.toString() ?? ''),
    );
  }
}
