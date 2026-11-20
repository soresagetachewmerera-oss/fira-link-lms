import 'dart:convert';
import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../router/app_router.dart';
import '../../services/api_exception.dart';
import '../../services/marketplace_service.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import 'organization_verification_screen.dart';
import 'payout_account_screen.dart';
import 'widgets/seller_lots_section.dart';

/// Exporter trade desk (business workspace): organization profile, membership tier,
/// compliance verification status, resource quotas, and published coffee lots.
class SellerPortalScreen extends StatefulWidget {
  const SellerPortalScreen({super.key});

  @override
  State<SellerPortalScreen> createState() => _SellerPortalScreenState();
}

class _SellerPortalScreenState extends State<SellerPortalScreen> {
  final MarketplaceService _marketplaceService = MarketplaceService();
  dynamic _profileData;
  Map<String, dynamic>? _entitlementsData;
  bool _isLoading = true;
  Object? _loadError;

  @override
  void initState() {
    super.initState();
    _fetchSellerData();
  }

  Future<void> _fetchSellerData() async {
    setState(() {
      _isLoading = true;
      _loadError = null;
    });
    try {
      final resProf = requireSuccess(await _marketplaceService.getSellerProfile());
      final resEnt = await _marketplaceService.getOrganizationEntitlements();

      final profile = jsonDecode(resProf.body)['data'];

      // 404 means the user has no business workspace yet: a valid state.
      Map<String, dynamic>? entitlements;
      if (resEnt.statusCode == 200) {
        final data = jsonDecode(resEnt.body);
        entitlements = data['data'] is Map<String, dynamic> ? data['data'] : null;
      } else if (resEnt.statusCode != 404) {
        throw ApiException.fromResponse(resEnt);
      }

      if (!mounted) return;
      setState(() {
        _profileData = profile;
        _entitlementsData = entitlements;
        _isLoading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loadError = e;
        _isLoading = false;
      });
    }
  }

  Widget _buildMeterCard({
    required BuildContext context,
    required String label,
    required int used,
    required dynamic limit,
    required IconData icon,
    String unit = '',
  }) {
    final fira = context.fira;
    final isUnlimited = limit == -1 || limit == '-1';
    final limitInt = isUnlimited ? -1 : (limit is int ? limit : int.tryParse(limit.toString()) ?? 1);
    final ratio = isUnlimited ? 0.0 : (limitInt > 0 ? (used / limitInt).clamp(0.0, 1.0) : 0.0);
    final isAtCapacity = !isUnlimited && used >= limitInt;

    return Container(
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
              Icon(
                icon,
                size: 16,
                color: isAtCapacity ? fira.danger : fira.brandPrimary,
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  label,
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: fira.onSurface,
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              if (isAtCapacity)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: fira.danger.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    'Cap reached',
                    style: TextStyle(
                      color: fira.danger,
                      fontSize: 10,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            crossAxisAlignment: CrossAxisAlignment.baseline,
            textBaseline: TextBaseline.alphabetic,
            children: [
              Flexible(
                child: Text(
                  '$used $unit',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                    color: fira.onSurface,
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              const SizedBox(width: 4),
              Flexible(
                child: Text(
                  isUnlimited ? 'Unlimited' : '/ $limitInt $unit',
                  style: TextStyle(
                    fontSize: 12,
                    color: fira.mutedText,
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          ClipRRect(
            borderRadius: BorderRadius.circular(3),
            child: LinearProgressIndicator(
              value: isUnlimited ? 0.15 : ratio,
              minHeight: 4,
              backgroundColor: fira.brandTint,
              valueColor: AlwaysStoppedAnimation<Color>(
                isAtCapacity
                    ? fira.danger
                    : (ratio > 0.8 ? fira.pending : fira.brandPrimary),
              ),
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;

    if (_isLoading) {
      return Scaffold(
        body: Center(child: CircularProgressIndicator(color: fira.brandPrimary)),
      );
    }

    if (_loadError != null) {
      return Scaffold(
        appBar: AppBar(),
        body: FiraErrorState(error: _loadError, onRetry: _fetchSellerData),
      );
    }

    final profile = _profileData?['profile'];
    final org = _entitlementsData?['organization'] ?? _profileData?['organization'];
    final sub = _entitlementsData?['subscription'] ?? _profileData?['active_subscription'];
    final ent = _entitlementsData?['entitlements'];
    final usage = _entitlementsData?['usage'];
    final overrides = _entitlementsData?['active_overrides'] as List<dynamic>? ?? [];

    final orgName = org?['name'] ?? profile?['company_name'] ?? 'Ethiopian Coffee Exporter';
    final businessType = (org?['business_type'] ?? 'exporter').toString();
    final role = (org?['my_role'] ?? 'owner').toString();
    final isVerified = org?['is_verified'] == true || profile?['is_verified'] == true;
    final planName = sub?['plan_name'] ?? sub?['plan']?['name'] ?? 'Exporter Starter';
    final subStatus = (sub?['status'] ?? 'active').toString().toLowerCase();
    final graceUntil = sub?['grace_until'];

    // Usage counts
    final activeLotsLimit = ent?['active_lots'] ?? _profileData?['listing_quota']?['limit'] ?? 3;
    final activeLotsUsed = usage?['active_lots'] ?? _profileData?['listing_quota']?['active_count'] ?? 0;
    final rfqLimit = ent?['rfq_responses_per_month'] ?? 5;
    final rfqUsed = usage?['rfq_responses_per_month'] ?? 0;
    final seatsLimit = ent?['seats'] ?? 2;
    final seatsUsed = usage?['seats'] ?? 1;
    final storageLimit = ent?['storage_gb'] ?? 1;
    final storageUsed = usage?['storage_gb'] ?? 0;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Trade desk', style: TextStyle(fontWeight: FontWeight.w600)),
        actions: [
          IconButton(
            key: const Key('open-payout-account'),
            icon: const Icon(Icons.account_balance_outlined),
            tooltip: context.l10n.payoutTitle,
            onPressed: () => Navigator.of(context).push(
              MaterialPageRoute(builder: (_) => const PayoutAccountScreen()),
            ),
          ),
          TextButton.icon(
            onPressed: () => Navigator.pushNamed(context, AppRouter.marketplacePricing)
                .then((_) => _fetchSellerData()),
            icon: Icon(Icons.tune_rounded, color: fira.brandPrimary, size: 16),
            label: Text(
              'Plans & quotas',
              style: TextStyle(
                color: fira.brandPrimary,
                fontWeight: FontWeight.w600,
                fontSize: 13,
              ),
            ),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _fetchSellerData,
        color: fira.brandPrimary,
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 32),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Organization Context & Membership Card (Quiet Deep Surface)
              Container(
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
                          radius: 22,
                          backgroundColor: Colors.white.withValues(alpha: 0.12),
                          child: const Icon(Icons.business_outlined, color: Colors.white, size: 22),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                orgName,
                                style: const TextStyle(
                                  fontSize: 17,
                                  fontWeight: FontWeight.w700,
                                  color: Colors.white,
                                ),
                              ),
                              const SizedBox(height: 6),
                              Row(
                                children: [
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                    decoration: BoxDecoration(
                                      color: Colors.white.withValues(alpha: 0.12),
                                      borderRadius: BorderRadius.circular(6),
                                    ),
                                    child: Text(
                                      businessType.capitalizeFirst(),
                                      style: const TextStyle(
                                        color: Colors.white,
                                        fontSize: 11,
                                        fontWeight: FontWeight.w500,
                                      ),
                                    ),
                                  ),
                                  const SizedBox(width: 6),
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                    decoration: BoxDecoration(
                                      color: Colors.white.withValues(alpha: 0.12),
                                      borderRadius: BorderRadius.circular(6),
                                    ),
                                    child: Text(
                                      'Role: ${role.capitalizeFirst()}',
                                      style: const TextStyle(
                                        color: Colors.white,
                                        fontSize: 11,
                                        fontWeight: FontWeight.w500,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                        // Verification Status
                        InkWell(
                          onTap: () {
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (_) => const OrganizationVerificationScreen(),
                              ),
                            ).then((_) => _fetchSellerData());
                          },
                          borderRadius: BorderRadius.circular(12),
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                            decoration: BoxDecoration(
                              color: isVerified
                                  ? fira.success.withValues(alpha: 0.25)
                                  : fira.pending.withValues(alpha: 0.3),
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(
                                color: isVerified
                                    ? fira.success.withValues(alpha: 0.5)
                                    : fira.pending.withValues(alpha: 0.6),
                              ),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(
                                  isVerified ? Icons.verified : Icons.shield_outlined,
                                  color: Colors.white,
                                  size: 13,
                                ),
                                const SizedBox(width: 4),
                                Text(
                                  isVerified ? 'Verified' : 'Get verified',
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 11,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    Divider(color: Colors.white.withValues(alpha: 0.12), height: 1),
                    const SizedBox(height: 12),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Subscription plan',
                              style: TextStyle(
                                color: Colors.white.withValues(alpha: 0.7),
                                fontSize: 11,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              planName,
                              style: const TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.w600,
                                fontSize: 14,
                              ),
                            ),
                          ],
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: subStatus == 'grace'
                                ? fira.pending.withValues(alpha: 0.3)
                                : (subStatus == 'active'
                                    ? Colors.white.withValues(alpha: 0.15)
                                    : fira.danger.withValues(alpha: 0.3)),
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(
                              color: subStatus == 'grace'
                                  ? fira.pending.withValues(alpha: 0.6)
                                  : Colors.white.withValues(alpha: 0.2),
                            ),
                          ),
                          child: Text(
                            subStatus == 'grace' ? 'Grace period' : subStatus.capitalizeFirst(),
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),

              // Unverified Compliance Notice
              if (!isVerified) ...[
                const SizedBox(height: 16),
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: fira.brandTint,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(
                      color: fira.brandPrimary.withValues(alpha: 0.2),
                    ),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.shield_outlined, color: fira.brandPrimary, size: 24),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Compliance verification required',
                              style: TextStyle(
                                fontWeight: FontWeight.w600,
                                fontSize: 13,
                                color: fira.onSurface,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              'Upload your export license and TIN to activate public lot publishing and bidding.',
                              style: TextStyle(
                                fontSize: 11,
                                color: fira.mutedText,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 8),
                      ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: fira.brandPrimary,
                          foregroundColor: Colors.white,
                          elevation: 0,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                          minimumSize: Size.zero,
                          tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                        ),
                        onPressed: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) => const OrganizationVerificationScreen(),
                            ),
                          ).then((_) => _fetchSellerData());
                        },
                        child: const Text(
                          'Verify',
                          style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600),
                        ),
                      ),
                    ],
                  ),
                ),
              ],

              // Grace Period Alert
              if (subStatus == 'grace') ...[
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
                      Icon(Icons.info_outline_rounded, color: fira.pending, size: 24),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Subscription grace period',
                              style: TextStyle(
                                fontWeight: FontWeight.w600,
                                fontSize: 13,
                                color: fira.onSurface,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              'Grace period active until ${graceUntil ?? "14 days"}. All active deals, contracts, and documents remain 100% accessible.',
                              style: TextStyle(
                                fontSize: 11,
                                color: fira.mutedText,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ],

              // Active Overrides Banner
              if (overrides.isNotEmpty) ...[
                const SizedBox(height: 16),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                  decoration: BoxDecoration(
                    color: fira.brandTint,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(
                      color: fira.brandPrimary.withValues(alpha: 0.25),
                    ),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.stars_outlined, color: fira.brandPrimary, size: 20),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          'Promotional override active: Founding member benefit applied.',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w500,
                            color: fira.brandPrimary,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],

              const SizedBox(height: 24),

              // Quotas & Resource Usage Grid
              Text(
                'Resource quotas & usage',
                style: TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w600,
                  color: fira.onSurface,
                ),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: _buildMeterCard(
                      context: context,
                      label: 'Active lots',
                      used: activeLotsUsed is int ? activeLotsUsed : int.tryParse(activeLotsUsed.toString()) ?? 0,
                      limit: activeLotsLimit,
                      icon: Icons.inventory_2_outlined,
                      unit: 'lots',
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _buildMeterCard(
                      context: context,
                      label: 'Monthly RFQs',
                      used: rfqUsed is int ? rfqUsed : int.tryParse(rfqUsed.toString()) ?? 0,
                      limit: rfqLimit,
                      icon: Icons.request_quote_outlined,
                      unit: 'quotes',
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: _buildMeterCard(
                      context: context,
                      label: 'Team seats',
                      used: seatsUsed is int ? seatsUsed : int.tryParse(seatsUsed.toString()) ?? 0,
                      limit: seatsLimit,
                      icon: Icons.people_outline,
                      unit: 'seats',
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _buildMeterCard(
                      context: context,
                      label: 'Document storage',
                      used: storageUsed is int ? storageUsed : int.tryParse(storageUsed.toString()) ?? 0,
                      limit: storageLimit,
                      icon: Icons.cloud_outlined,
                      unit: 'GB',
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 28),

              // Lots of the active business: drafts, levels, hidden-until-Tier-1 banner.
              const SellerLotsSection(),
            ],
          ),
        ),
      ),
    );
  }
}

extension on String {
  String capitalizeFirst() {
    if (isEmpty) return this;
    return this[0].toUpperCase() + substring(1).toLowerCase();
  }
}
