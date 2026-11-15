import 'dart:convert';
import 'package:flutter/material.dart';
import '../../services/api_exception.dart';
import '../../services/marketplace_service.dart';
import '../../theme/app_theme.dart';
import '../../widgets/fira_error_state.dart';
import '../../router/app_router.dart';
import '../../l10n/app_localizations.dart';
import 'organization_verification_screen.dart';
import 'payout_account_screen.dart';
import 'widgets/seller_lots_section.dart';

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

      // 404 means the user has no business workspace yet: a real state, not an error.
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
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final isUnlimited = limit == -1 || limit == '-1';
    final limitInt = isUnlimited ? -1 : (limit is int ? limit : int.tryParse(limit.toString()) ?? 1);
    final ratio = isUnlimited ? 0.0 : (limitInt > 0 ? (used / limitInt).clamp(0.0, 1.0) : 0.0);
    final isAtCapacity = !isUnlimited && used >= limitInt;

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: isDark ? AppTheme.surfaceDark : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isAtCapacity
              ? Colors.red.withValues(alpha: 0.5)
              : (isDark ? Colors.white10 : Colors.grey.shade200),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 16, color: isAtCapacity ? Colors.red : AppTheme.primary),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  label,
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: isDark ? Colors.white70 : Colors.grey.shade700,
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              if (isAtCapacity)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: Colors.red.shade50,
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: const Text('MAX', style: TextStyle(color: Colors.red, fontSize: 9, fontWeight: FontWeight.bold)),
                ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Flexible(
                child: Text(
                  '$used $unit',
                  style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              const SizedBox(width: 4),
              Flexible(
                child: Text(
                  isUnlimited ? 'Unlimited' : '/ $limitInt $unit',
                  style: TextStyle(fontSize: 12, color: isDark ? Colors.white60 : Colors.grey),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          ClipRRect(
            borderRadius: BorderRadius.circular(4),
            child: LinearProgressIndicator(
              value: isUnlimited ? 0.2 : ratio,
              minHeight: 6,
              backgroundColor: isDark ? Colors.white12 : Colors.grey.shade100,
              valueColor: AlwaysStoppedAnimation<Color>(
                isAtCapacity ? Colors.red : (ratio > 0.8 ? Colors.amber : AppTheme.primary),
              ),
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    if (_isLoading) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator(color: AppTheme.primary)),
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
    final businessType = (org?['business_type'] ?? 'exporter').toString().toUpperCase();
    final role = (org?['my_role'] ?? 'OWNER').toString().toUpperCase();
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
        title: const Text('Exporter Portal', style: TextStyle(fontWeight: FontWeight.bold)),
        actions: [
          IconButton(
            key: const Key('open-payout-account'),
            icon: const Icon(Icons.account_balance_outlined),
            tooltip: context.l10n.payoutTitle,
            onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const PayoutAccountScreen())),
          ),
          TextButton.icon(
            onPressed: () => Navigator.pushNamed(context, AppRouter.marketplacePricing).then((_) => _fetchSellerData()),
            icon: const Icon(Icons.bolt, color: AppTheme.primary, size: 18),
            label: const Text('Plans & Quotas', style: TextStyle(color: AppTheme.primary, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _fetchSellerData,
        color: AppTheme.primary,
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Organization Context & Membership Card
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [
                      AppTheme.primary,
                      Color(0xFF8B2BB8),
                    ],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(24),
                  boxShadow: [
                    BoxShadow(
                      color: AppTheme.primary.withValues(alpha: 0.35),
                      blurRadius: 18,
                      offset: const Offset(0, 6),
                    ),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        CircleAvatar(
                          radius: 24,
                          backgroundColor: Colors.white.withValues(alpha: 0.2),
                          child: const Icon(Icons.business_rounded, color: Colors.white, size: 26),
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                orgName,
                                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white),
                              ),
                              const SizedBox(height: 4),
                              Row(
                                children: [
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                    decoration: BoxDecoration(
                                      color: Colors.white.withValues(alpha: 0.2),
                                      borderRadius: BorderRadius.circular(6),
                                    ),
                                    child: Text(
                                      businessType,
                                      style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.w600),
                                    ),
                                  ),
                                  const SizedBox(width: 6),
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                    decoration: BoxDecoration(
                                      color: Colors.white.withValues(alpha: 0.2),
                                      borderRadius: BorderRadius.circular(6),
                                    ),
                                    child: Text(
                                      'ROLE: $role',
                                      style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.w600),
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                        // Verification Badge (Trust is separate from subscription)
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
                              color: isVerified ? Colors.green.shade600 : Colors.amber.shade800,
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(isVerified ? Icons.verified : Icons.shield_outlined, color: Colors.white, size: 14),
                                const SizedBox(width: 4),
                                Text(
                                  isVerified ? 'VERIFIED' : 'GET VERIFIED',
                                  style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    const Divider(color: Colors.white24, height: 1),
                    const SizedBox(height: 14),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Current Subscription Plan', style: TextStyle(color: Colors.white70, fontSize: 11)),
                            const SizedBox(height: 2),
                            Text(
                              planName,
                              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 15),
                            ),
                          ],
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
                          decoration: BoxDecoration(
                            color: subStatus == 'grace'
                                ? Colors.amber
                                : (subStatus == 'active' ? Colors.white.withValues(alpha: 0.25) : Colors.redAccent),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            subStatus.toUpperCase(),
                            style: TextStyle(
                              color: subStatus == 'grace' ? Colors.black87 : Colors.white,
                              fontSize: 11,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),

              // Unverified Compliance Banner
              if (!isVerified) ...[
                const SizedBox(height: 14),
                Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: Colors.amber.shade50.withValues(alpha: isDark ? 0.15 : 1.0),
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: Colors.amber.shade400),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.shield_outlined, color: Colors.amber.shade800, size: 28),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Compliance Verification Required',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 13,
                                color: isDark ? Colors.white : Colors.amber.shade900,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              'Upload your ECTA export license and TIN to activate public lot publishing and bidding.',
                              style: TextStyle(
                                fontSize: 11,
                                color: isDark ? Colors.white70 : Colors.brown.shade800,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 8),
                      ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF6D1A95),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
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
                        child: const Text('Verify', style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
                      ),
                    ],
                  ),
                ),
              ],

              // Grace Period Alert (Hard Constraint: live deals continue without interruption)
              if (subStatus == 'grace') ...[
                const SizedBox(height: 14),
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.amber.shade50.withValues(alpha: isDark ? 0.15 : 1.0),
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: Colors.amber.shade400),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.warning_amber_rounded, color: Colors.amber, size: 28),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'Payment Grace Period Active',
                              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Colors.amber),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              'Grace period active until ${graceUntil ?? "14 days"}. All active deals, contracts, and documents remain 100% accessible.',
                              style: TextStyle(fontSize: 11, color: isDark ? Colors.amber.shade200 : Colors.amber.shade900),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ],

              // Active Overrides Banner (Founding Member / Promotional)
              if (overrides.isNotEmpty) ...[
                const SizedBox(height: 14),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                  decoration: BoxDecoration(
                    color: const Color(0xFF6D1A95).withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: const Color(0xFF6D1A95).withValues(alpha: 0.3)),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.stars_rounded, color: AppTheme.primary, size: 22),
                      const SizedBox(width: 10),
                      const Expanded(
                        child: Text(
                          'Promotional override active: Founding Member Benefit applied.',
                          style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppTheme.primary),
                        ),
                      ),
                    ],
                  ),
                ),
              ],

              const SizedBox(height: 24),

              // Quotas & Resource Usage Grid
              const Text('Resource Quotas & Entitlements', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: _buildMeterCard(
                      context: context,
                      label: 'Active Lots',
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
                      label: 'RFQ Quotes/Mo',
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
                      label: 'Team Seats',
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
                      label: 'Cloud Storage',
                      used: storageUsed is int ? storageUsed : int.tryParse(storageUsed.toString()) ?? 0,
                      limit: storageLimit,
                      icon: Icons.cloud_outlined,
                      unit: 'GB',
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 28),

              // Lots of the active business (slice 4): drafts, levels, hidden-until-Tier-1 banner.
              const SellerLotsSection(),
            ],
          ),
        ),
      ),
    );
  }
}
