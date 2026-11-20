import 'dart:convert';
import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../router/app_router.dart';
import '../../services/api_exception.dart';
import '../../services/marketplace_service.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';

/// Self-serve SaaS Subscription & Entitlement Quota Management Screen.
///
/// Displays current organization membership tier, billing cycle status, grace period alert,
/// live quota meters (lots, RFQs, seats, deal rooms, storage), upgrade options,
/// and self-serve cancellation for organization owners.
class SubscriptionManagementScreen extends StatefulWidget {
  const SubscriptionManagementScreen({super.key, this.service});

  final MarketplaceService? service;

  @override
  State<SubscriptionManagementScreen> createState() => _SubscriptionManagementScreenState();
}

class _SubscriptionManagementScreenState extends State<SubscriptionManagementScreen> {
  late final MarketplaceService _marketplaceService;
  Map<String, dynamic>? _snapshotData;
  bool _isLoading = true;
  bool _isCancelling = false;
  Object? _loadError;

  @override
  void initState() {
    super.initState();
    _marketplaceService = widget.service ?? MarketplaceService();
    _fetchEntitlements();
  }

  Future<void> _fetchEntitlements() async {
    setState(() {
      _isLoading = true;
      _loadError = null;
    });

    try {
      final res = await _marketplaceService.getOrganizationEntitlements();
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        if (mounted) {
          setState(() {
            _snapshotData = body['data'] is Map<String, dynamic> ? body['data'] : null;
            _isLoading = false;
          });
        }
      } else if (res.statusCode == 404) {
        if (mounted) {
          setState(() {
            _snapshotData = null;
            _isLoading = false;
          });
        }
      } else {
        throw ApiException.fromResponse(res);
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _loadError = e;
          _isLoading = false;
        });
      }
    }
  }

  String _formatDate(String? isoString) {
    if (isoString == null || isoString.isEmpty) return '';
    try {
      final dt = DateTime.parse(isoString).toLocal();
      return '${dt.year}-${dt.month.toString().padLeft(2, '0')}-${dt.day.toString().padLeft(2, '0')}';
    } catch (_) {
      return isoString;
    }
  }

  Future<void> _handleCancelRenewal() async {
    final l10n = context.l10n;
    final fira = context.fira;

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Text(
          l10n.cancelRenewal,
          style: TextStyle(fontWeight: FontWeight.w700, color: fira.onSurface),
        ),
        content: Text(
          l10n.cancelRenewalConfirm,
          style: TextStyle(color: fira.onSurface.withValues(alpha: 0.8), fontSize: 13, height: 1.4),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: Text(l10n.notNow, style: TextStyle(color: fira.mutedText)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: fira.danger,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              elevation: 0,
            ),
            onPressed: () => Navigator.of(ctx).pop(true),
            child: Text(l10n.cancelRenewal),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    setState(() => _isCancelling = true);
    try {
      final res = await _marketplaceService.cancelSubscription();
      if (res.statusCode == 200) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(l10n.renewalCancelled),
              backgroundColor: fira.brandDeep,
            ),
          );
          _fetchEntitlements();
        }
      } else {
        throw ApiException.fromResponse(res);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(e.toString()),
            backgroundColor: fira.danger,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isCancelling = false);
      }
    }
  }

  Widget _buildMeter({
    required BuildContext context,
    required Key key,
    required String label,
    required dynamic limitVal,
    required dynamic usedVal,
    required IconData icon,
    String unit = '',
  }) {
    final fira = context.fira;
    final isUnlimited = limitVal == -1 || limitVal == '-1';
    final limit = isUnlimited ? -1 : (limitVal is num ? limitVal.toInt() : int.tryParse(limitVal.toString()) ?? 1);
    final used = usedVal is num ? usedVal.toInt() : int.tryParse(usedVal.toString()) ?? 0;
    final ratio = isUnlimited ? 0.0 : (limit > 0 ? (used / limit).clamp(0.0, 1.0) : 0.0);
    final isAtCapacity = !isUnlimited && used >= limit;

    final usageText = isUnlimited
        ? '$used / ${context.l10n.unlimited} $unit'.trim()
        : '$used / $limit $unit'.trim();

    return Container(
      key: key,
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: fira.card,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isAtCapacity
              ? fira.danger.withValues(alpha: 0.4)
              : fira.brandTint.withValues(alpha: 0.8),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: fira.brandTint,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(icon, size: 18, color: fira.brandPrimary),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  label,
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: fira.onSurface,
                  ),
                ),
              ),
              Text(
                usageText,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: isAtCapacity ? fira.danger : fira.brandPrimary,
                ),
              ),
            ],
          ),
          if (!isUnlimited) ...[
            const SizedBox(height: 12),
            ClipRRect(
              borderRadius: BorderRadius.circular(4),
              child: LinearProgressIndicator(
                value: ratio,
                minHeight: 6,
                backgroundColor: fira.chipUnselectedBg,
                valueColor: AlwaysStoppedAnimation<Color>(
                  isAtCapacity ? fira.danger : fira.brandPrimary,
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;

    if (_isLoading) {
      return Scaffold(
        appBar: AppBar(title: Text(l10n.planAndBilling)),
        body: Center(
          child: CircularProgressIndicator(color: fira.brandPrimary),
        ),
      );
    }

    if (_loadError != null) {
      return Scaffold(
        appBar: AppBar(title: Text(l10n.planAndBilling)),
        body: Center(
          child: FiraErrorState(
            error: _loadError!,
            onRetry: _fetchEntitlements,
          ),
        ),
      );
    }

    if (_snapshotData == null) {
      return Scaffold(
        appBar: AppBar(title: Text(l10n.planAndBilling)),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24.0),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.business_outlined, size: 48, color: fira.mutedText),
                const SizedBox(height: 16),
                Text(
                  'No business workspace found',
                  style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16, color: fira.onSurface),
                ),
                const SizedBox(height: 8),
                Text(
                  'Create an organization workspace to manage plans and entitlements.',
                  textAlign: TextAlign.center,
                  style: TextStyle(color: fira.mutedText, fontSize: 13),
                ),
              ],
            ),
          ),
        ),
      );
    }

    final org = _snapshotData?['organization'] as Map<String, dynamic>?;
    final plan = _snapshotData?['plan'] as Map<String, dynamic>?;
    final sub = _snapshotData?['subscription'] as Map<String, dynamic>?;
    final usage = _snapshotData?['usage'] as Map<String, dynamic>? ?? {};
    final isOwner = _snapshotData?['is_owner'] == true;

    final orgName = org?['name']?.toString() ?? 'Organization';
    final planName = plan?['name']?.toString() ?? sub?['plan_name']?.toString() ?? 'Starter (Free)';
    final planKey = plan?['key']?.toString() ?? sub?['plan_key']?.toString() ?? 'starter';
    final isStarter = planKey == 'starter';
    final subStatus = (sub?['status']?.toString() ?? 'active').toLowerCase();
    final isGrace = subStatus == 'grace';
    final billingPeriod = sub?['billing_period']?.toString() ?? 'monthly';
    final periodEnd = _formatDate(sub?['current_period_end']?.toString());
    final graceUntil = _formatDate(sub?['grace_until']?.toString());
    final cancelAtEnd = sub?['cancel_at_period_end'] == true;

    // Feature quotas
    final lotsUsage = usage['active_lots'] as Map<String, dynamic>?;
    final rfqUsage = usage['rfq_responses_per_month'] as Map<String, dynamic>?;
    final seatsUsage = usage['seats'] as Map<String, dynamic>?;
    final dealsUsage = usage['active_deal_rooms'] as Map<String, dynamic>?;
    final storageUsage = usage['storage_gb'] as Map<String, dynamic>?;

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.planAndBilling, style: const TextStyle(fontWeight: FontWeight.w600)),
      ),
      body: RefreshIndicator(
        onRefresh: _fetchEntitlements,
        color: fira.brandPrimary,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 32),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Current Plan Banner Card
              Container(
                key: const Key('subscription-plan-card'),
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: fira.brandDeep,
                  borderRadius: BorderRadius.circular(22),
                  border: Border.all(
                    color: fira.brandPrimary.withValues(alpha: 0.25),
                  ),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        CircleAvatar(
                          radius: 20,
                          backgroundColor: Colors.white.withValues(alpha: 0.12),
                          child: const Icon(Icons.tune_rounded, color: Colors.white, size: 20),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                orgName,
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Colors.white.withValues(alpha: 0.7),
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                planName,
                                key: const Key('plan-name'),
                                style: const TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.w700,
                                  color: Colors.white,
                                ),
                              ),
                            ],
                          ),
                        ),
                        Container(
                          key: const Key('plan-status'),
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: isGrace
                                ? fira.pending.withValues(alpha: 0.3)
                                : (subStatus == 'active'
                                    ? Colors.white.withValues(alpha: 0.15)
                                    : fira.danger.withValues(alpha: 0.3)),
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(
                              color: isGrace
                                  ? fira.pending.withValues(alpha: 0.6)
                                  : Colors.white.withValues(alpha: 0.2),
                            ),
                          ),
                          child: Text(
                            isGrace ? 'Grace' : subStatus.toUpperCase(),
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    Divider(color: Colors.white.withValues(alpha: 0.12), height: 1),
                    const SizedBox(height: 14),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              l10n.billingPeriod,
                              style: TextStyle(
                                color: Colors.white.withValues(alpha: 0.6),
                                fontSize: 11,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              billingPeriod.toUpperCase(),
                              style: const TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.w600,
                                fontSize: 13,
                              ),
                            ),
                          ],
                        ),
                        if (periodEnd.isNotEmpty) ...[
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              Text(
                                cancelAtEnd ? l10n.cancelsOn : l10n.renewsOn,
                                style: TextStyle(
                                  color: Colors.white.withValues(alpha: 0.6),
                                  fontSize: 11,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                periodEnd,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.w600,
                                  fontSize: 13,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
              ),

              // Grace Period Alert Banner
              if (isGrace) ...[
                const SizedBox(height: 16),
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: fira.brandTint,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(
                      color: fira.pending.withValues(alpha: 0.4),
                    ),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.warning_amber_rounded, color: fira.pending, size: 24),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              l10n.gracePeriodWarning,
                              style: TextStyle(
                                fontWeight: FontWeight.w600,
                                fontSize: 12,
                                color: fira.onSurface,
                              ),
                            ),
                            if (graceUntil.isNotEmpty) ...[
                              const SizedBox(height: 4),
                              Text(
                                'Grace active until: $graceUntil',
                                style: TextStyle(
                                  fontSize: 11,
                                  color: fira.mutedText,
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ],

              // Starter Notice
              if (isStarter) ...[
                const SizedBox(height: 16),
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: fira.card,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: fira.brandTint),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.info_outline, color: fira.brandPrimary, size: 20),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          l10n.starterPlanNotice,
                          style: TextStyle(fontSize: 12, color: fira.mutedText, height: 1.3),
                        ),
                      ),
                    ],
                  ),
                ),
              ],

              const SizedBox(height: 24),

              // Usage & Quotas Title
              Text(
                l10n.usageAndLimits,
                style: TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                  color: fira.onSurface,
                ),
              ),
              const SizedBox(height: 12),

              // 1. Active Lots
              _buildMeter(
                context: context,
                key: const Key('quota-meter-active-lots'),
                label: l10n.activeLots,
                limitVal: lotsUsage?['limit'] ?? 3,
                usedVal: lotsUsage?['used'] ?? 0,
                icon: Icons.inventory_2_outlined,
              ),

              // 2. Monthly RFQ Quotes
              _buildMeter(
                context: context,
                key: const Key('quota-meter-rfq-quotes'),
                label: l10n.rfqQuotes,
                limitVal: rfqUsage?['limit'] ?? 5,
                usedVal: rfqUsage?['used'] ?? 0,
                icon: Icons.request_quote_outlined,
              ),

              // 3. Team Seats
              _buildMeter(
                context: context,
                key: const Key('quota-meter-team-seats'),
                label: l10n.teamSeats,
                limitVal: seatsUsage?['limit'] ?? 2,
                usedVal: seatsUsage?['used'] ?? 1,
                icon: Icons.people_outline,
              ),

              // 4. Active Deal Rooms
              _buildMeter(
                context: context,
                key: const Key('quota-meter-active-deals'),
                label: l10n.activeDeals,
                limitVal: dealsUsage?['limit'] ?? 2,
                usedVal: dealsUsage?['used'] ?? 0,
                icon: Icons.handshake_outlined,
              ),

              // 5. Document Storage
              _buildMeter(
                context: context,
                key: const Key('quota-meter-storage-quota'),
                label: l10n.storageQuota,
                limitVal: storageUsage?['limit'] ?? 1,
                usedVal: storageUsage?['used'] ?? 0,
                icon: Icons.cloud_queue_outlined,
                unit: 'GB',
              ),

              const SizedBox(height: 20),

              // Upgrade / Change Plan Action
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  key: const Key('upgrade-plan-button'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: fira.brandPrimary,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                    elevation: 0,
                  ),
                  onPressed: () {
                    Navigator.pushNamed(context, AppRouter.marketplacePricing)
                        .then((_) => _fetchEntitlements());
                  },
                  icon: const Icon(Icons.arrow_upward_rounded, size: 18),
                  label: const Text(
                    'Upgrade / Change Plan',
                    style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
                  ),
                ),
              ),

              // Cancel Renewal Action (Owners only, non-starter, not already canceled)
              if (isOwner && !isStarter && !cancelAtEnd) ...[
                const SizedBox(height: 12),
                SizedBox(
                  width: double.infinity,
                  child: TextButton.icon(
                    key: const Key('cancel-renewal-button'),
                    style: TextButton.styleFrom(
                      foregroundColor: fira.danger,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                    onPressed: _isCancelling ? null : _handleCancelRenewal,
                    icon: _isCancelling
                        ? SizedBox(
                            width: 14,
                            height: 14,
                            child: CircularProgressIndicator(strokeWidth: 2, color: fira.danger),
                          )
                        : const Icon(Icons.cancel_outlined, size: 16),
                    label: Text(
                      l10n.cancelRenewal,
                      style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                    ),
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
