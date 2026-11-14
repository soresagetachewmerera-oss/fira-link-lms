import 'package:flutter/material.dart';
import '../../../theme/app_theme.dart';
import '../../../router/app_router.dart';

class VerificationPromptDialog {
  /// Prompt visitor to sign in or register before attempting a B2B action
  static Future<void> showAuthRequired(
    BuildContext context, {
    String actionDescription = 'execute this trade action',
  }) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return showModalBottomSheet(
      context: context,
      backgroundColor: isDark ? AppTheme.surfaceDark : Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) {
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 20.0),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: Colors.grey.withValues(alpha: 0.3),
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
                const SizedBox(height: 20),
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: AppTheme.primary.withValues(alpha: 0.1),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    Icons.lock_person_outlined,
                    color: AppTheme.primary,
                    size: 36,
                  ),
                ),
                const SizedBox(height: 16),
                const Text(
                  'Sign In Required',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  'To $actionDescription, please sign in or register your business entity with Fira Link.',
                  textAlign: TextAlign.center,
                  style: const TextStyle(fontSize: 14, color: Colors.grey, height: 1.4),
                ),
                const SizedBox(height: 24),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: () {
                      Navigator.pop(ctx);
                      Navigator.pushNamed(context, AppRouter.auth);
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppTheme.primary,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                    ),
                    child: const Text(
                      'Sign In / Register',
                      style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                    ),
                  ),
                ),
                const SizedBox(height: 10),
                SizedBox(
                  width: double.infinity,
                  child: TextButton(
                    onPressed: () => Navigator.pop(ctx),
                    child: const Text('Continue Browsing as Visitor', style: TextStyle(color: Colors.grey)),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  /// Show role verification modal for unverified, pending, rejected, or expired accounts
  static Future<void> showVerificationRequired(
    BuildContext context, {
    required String role,
    String? message,
    String? status,
    String? reason,
  }) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final currentStatus = status ?? 'unsubmitted';

    final roleLabel = switch (role.toLowerCase()) {
      'exporter' => 'Licensed Exporter',
      'buyer' => 'Verified Buyer',
      'supplier' => 'Washing Station / Supplier',
      'broker' => 'Coffee Broker',
      _ => '${role[0].toUpperCase()}${role.substring(1)}',
    };

    final (badgeIcon, badgeColor, title, defaultDesc) = switch (currentStatus) {
      'pending_review' || 'under_scrutiny' => (
        Icons.hourglass_top_rounded,
        Colors.orange,
        '$roleLabel Review in Progress',
        'Your compliance dossier has been received and is currently undergoing regulatory verification with ECTA and MoTI. You will be notified upon approval.'
      ),
      'rejected' => (
        Icons.cancel_outlined,
        Colors.red,
        '$roleLabel Verification Rejected',
        reason != null && reason.isNotEmpty
            ? 'Reason: $reason. Please update and resubmit your compliance credentials.'
            : 'Your verification dossier did not meet compliance criteria. Please resubmit valid official documentation.'
      ),
      'expired' => (
        Icons.timer_off_outlined,
        Colors.redAccent,
        '$roleLabel License Expired',
        'Your seasonal coffee export license or commercial registry on record has expired. Please upload your current renewal certificate.'
      ),
      'suspended' => (
        Icons.block_rounded,
        Colors.red,
        'Trading Access Suspended',
        'Trading permissions for this account have been temporarily restricted by compliance administration.'
      ),
      _ => (
        Icons.verified_user_outlined,
        AppTheme.primary,
        '$roleLabel Verification Required',
        message ?? 'Trust is Never For Sale: To protect authentic direct coffee trade, only verified $roleLabel entities may execute this action.'
      ),
    };

    final canSubmit = currentStatus == 'unsubmitted' || currentStatus == 'rejected' || currentStatus == 'expired';

    return showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: isDark ? AppTheme.surfaceDark : Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) {
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 20.0),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: Colors.grey.withValues(alpha: 0.3),
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
                const SizedBox(height: 20),
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: badgeColor.withValues(alpha: 0.12),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    badgeIcon,
                    color: badgeColor,
                    size: 38,
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  title,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontSize: 19,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 10),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: AppTheme.primary.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    'TRUST IS NEVER FOR SALE • ECTA COMPLIANT',
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 0.6,
                      color: AppTheme.primary,
                    ),
                  ),
                ),
                const SizedBox(height: 14),
                Text(
                  defaultDesc,
                  textAlign: TextAlign.center,
                  style: const TextStyle(fontSize: 13, color: Colors.grey, height: 1.45),
                ),
                const SizedBox(height: 24),
                if (currentStatus != 'suspended')
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: () {
                        Navigator.pop(ctx);
                        Navigator.pushNamed(context, AppRouter.marketplaceVerification);
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppTheme.primary,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(14),
                        ),
                      ),
                      icon: Icon(canSubmit ? Icons.shield_outlined : Icons.description_outlined, size: 18),
                      label: Text(
                        canSubmit ? 'Complete $roleLabel Verification' : 'View Compliance Dossier',
                        style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ),
                const SizedBox(height: 10),
                SizedBox(
                  width: double.infinity,
                  child: TextButton(
                    onPressed: () => Navigator.pop(ctx),
                    child: const Text('Dismiss', style: TextStyle(color: Colors.grey)),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}
