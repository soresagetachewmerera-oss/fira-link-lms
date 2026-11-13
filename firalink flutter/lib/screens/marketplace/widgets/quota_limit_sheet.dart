import 'package:flutter/material.dart';
import '../../../router/app_router.dart';
import '../../../theme/app_theme.dart';

class QuotaLimitSheet extends StatelessWidget {
  final Map<String, dynamic> limitInfo;

  const QuotaLimitSheet({
    super.key,
    required this.limitInfo,
  });

  static Future<void> show(BuildContext context, Map<String, dynamic> limitInfo) {
    return showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => QuotaLimitSheet(limitInfo: limitInfo),
    );
  }

  String _formatFeatureName(String featureKey) {
    switch (featureKey) {
      case 'active_lots':
        return 'Active Coffee Lots';
      case 'rfq_responses_per_month':
        return 'Monthly RFQ Quotes';
      case 'active_deal_rooms':
        return 'Active Deal Rooms';
      case 'seats':
        return 'Team Seats';
      case 'storage_gb':
        return 'Document Storage (GB)';
      default:
        return featureKey.replaceAll('_', ' ').toUpperCase();
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final featureKey = limitInfo['feature']?.toString() ?? 'quota';
    final featureName = _formatFeatureName(featureKey);
    final limit = limitInfo['limit'] ?? 0;
    final used = limitInfo['used'] ?? limit;
    final serverMessage = limitInfo['message']?.toString();

    return Container(
      padding: const EdgeInsets.fromLTRB(24, 20, 24, 32),
      decoration: BoxDecoration(
        color: isDark ? AppTheme.surfaceDark : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.15),
            blurRadius: 20,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Drag handle
          Container(
            width: 40,
            height: 4,
            margin: const EdgeInsets.only(bottom: 20),
            decoration: BoxDecoration(
              color: isDark ? Colors.white24 : Colors.grey.shade300,
              borderRadius: BorderRadius.circular(2),
            ),
          ),

          // Alert Icon Badge
          Container(
            width: 64,
            height: 64,
            decoration: BoxDecoration(
              color: AppTheme.primary.withValues(alpha: 0.12),
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.trending_up_rounded,
              color: AppTheme.primary,
              size: 32,
            ),
          ),
          const SizedBox(height: 16),

          // Title
          const Text(
            'Plan Quota Reached',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              letterSpacing: -0.3,
            ),
          ),
          const SizedBox(height: 8),

          // Feature & Usage Indicator
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            margin: const EdgeInsets.symmetric(vertical: 8),
            decoration: BoxDecoration(
              color: isDark ? const Color(0xFF2A1B36) : const Color(0xFFFAF5FF),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(
                color: AppTheme.primary.withValues(alpha: 0.2),
              ),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        featureName,
                        style: const TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        serverMessage ?? 'Limit of $limit reached ($used currently active)',
                        style: TextStyle(
                          fontSize: 11,
                          color: isDark ? Colors.white60 : Colors.grey.shade600,
                        ),
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: AppTheme.primary,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    '$used / $limit',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 8),

          // Reassurance badge (Trust is never for sale / open deals protected)
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.amber.shade50.withValues(alpha: isDark ? 0.1 : 1.0),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: Colors.amber.shade300.withValues(alpha: 0.5),
              ),
            ),
            child: Row(
              children: [
                Icon(
                  Icons.shield_outlined,
                  size: 20,
                  color: isDark ? Colors.amber.shade300 : Colors.amber.shade800,
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    'All your active deals, open deal rooms, and trade records continue without interruption.',
                    style: TextStyle(
                      fontSize: 11,
                      color: isDark ? Colors.amber.shade200 : Colors.amber.shade900,
                      height: 1.35,
                    ),
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 24),

          // Action Buttons
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: () {
                Navigator.pop(context);
                Navigator.pushNamed(context, AppRouter.marketplacePricing);
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.primary,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(14),
                ),
                elevation: 0,
              ),
              child: const Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.bolt_rounded, size: 20),
                  SizedBox(width: 8),
                  Text(
                    'Upgrade Plan for More Capacity',
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 14,
                    ),
                  ),
                ],
              ),
            ),
          ),

          const SizedBox(height: 10),

          // Secondary button
          SizedBox(
            width: double.infinity,
            child: TextButton(
              onPressed: () => Navigator.pop(context),
              style: TextButton.styleFrom(
                padding: const EdgeInsets.symmetric(vertical: 12),
                foregroundColor: isDark ? Colors.white70 : Colors.grey.shade700,
              ),
              child: const Text('Dismiss'),
            ),
          ),
        ],
      ),
    );
  }
}

