import 'package:intl/intl.dart';

/// Shared currency formatter adhering to the Fira Link standard: "ETB 33,000.00"
class FiraCurrencyFormatter {
  const FiraCurrencyFormatter._();

  static final NumberFormat _formatter = NumberFormat('#,##0.00', 'en_US');

  /// Formats a numeric price into standard "ETB 33,000.00" format with comma grouping
  static String format(dynamic amount, {String currency = 'ETB'}) {
    if (amount == null) return '$currency 0.00';
    final double value = amount is num ? amount.toDouble() : (double.tryParse(amount.toString()) ?? 0.0);
    return '$currency ${_formatter.format(value)}';
  }
}

