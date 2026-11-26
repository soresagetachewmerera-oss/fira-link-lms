import 'package:flutter/material.dart';

import '../l10n/app_localizations.dart';
import '../models/trade_model.dart';
import '../theme/fira_colors.dart';

/// Localized labels for request and quote values that come from the server.
class TradeLabels {
  const TradeLabels._();

  static const incoterms = ['FOB', 'CIF', 'CFR', 'EXW'];
  static const units = ['bags', 'kg', 'mt'];

  static String unit(AppLocalizations l10n, String unit) => switch (unit) {
        'kg' => l10n.unitKg,
        'mt' => l10n.unitMt,
        _ => l10n.unitBags,
      };

  static String volume(AppLocalizations l10n, double value, String volumeUnit) {
    final n = value == value.roundToDouble() ? value.toStringAsFixed(0) : value.toStringAsFixed(2);
    return '$n ${unit(l10n, volumeUnit)}';
  }

  static String process(AppLocalizations l10n, String? p) => switch (p) {
        'natural' => l10n.processNatural,
        'honey' => l10n.processHoney,
        'washed' => l10n.processWashed,
        _ => p ?? '',
      };

  static String grade(AppLocalizations l10n, String? g) {
    if (g == null || g.isEmpty) return '';
    if (g == 'undergrade') return l10n.gradeUnder;
    if (g == 'g4_plus') return l10n.gradeG4Plus;
    return l10n.translate('gradeNumber', {'n': g.replaceAll('grade_', '')});
  }

  static String rfqStatus(AppLocalizations l10n, String status) => switch (status) {
        'open' => l10n.requestStatusOpen,
        'fulfilled' => l10n.requestStatusFulfilled,
        'expired' => l10n.requestStatusExpired,
        _ => l10n.requestStatusClosed,
      };

  static String quoteStatus(AppLocalizations l10n, String status) => switch (status) {
        'accepted' => l10n.quoteStatusAccepted,
        'not_selected' => l10n.quoteStatusNotSelected,
        'rejected' => l10n.quoteStatusRejected,
        'expired' => l10n.quoteStatusExpired,
        _ => l10n.quoteStatusSubmitted,
      };

  static String? blocker(AppLocalizations l10n, String? code) => switch (code) {
        'REQUEST_CLOSED' => l10n.blockerRequestClosed,
        'QUOTE_NOT_OPEN' => l10n.blockerQuoteNotOpen,
        'LOT_REQUIRED' => l10n.blockerLotRequired,
        'SELLER_NOT_VERIFIED' => l10n.blockerSellerNotVerified,
        'BUYER_NOT_VERIFIED' => l10n.blockerBuyerNotVerified,
        'STOCK_UNAVAILABLE' => l10n.blockerStockUnavailable,
        _ => null,
      };

  static String money(double v) {
    final s = v.toStringAsFixed(0);
    final b = StringBuffer();
    for (var i = 0; i < s.length; i++) {
      if (i > 0 && (s.length - i) % 3 == 0) b.write(',');
      b.write(s[i]);
    }
    return '\$$b';
  }
}

/// "Verified · Tier n" or "Not verified at the moment", from the server's business summary.
class BusinessTierLabel extends StatelessWidget {
  const BusinessTierLabel({super.key, required this.business});

  final BusinessSummary? business;

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final verified = business?.verified ?? false;
    return Text(
      verified ? context.l10n.translate('sellerVerifiedTier', {'tier': business!.tier}) : context.l10n.compareNotVerified,
      style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: verified ? fira.success : fira.mutedText),
    );
  }
}
