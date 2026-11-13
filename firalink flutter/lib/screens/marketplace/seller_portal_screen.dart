import 'dart:convert';
import 'package:flutter/material.dart';
import '../../services/marketplace_service.dart';
import '../../theme/app_theme.dart';
import '../../router/app_router.dart';
import 'widgets/quota_limit_sheet.dart';
import 'organization_verification_screen.dart';

class SellerPortalScreen extends StatefulWidget {
  const SellerPortalScreen({super.key});

  @override
  State<SellerPortalScreen> createState() => _SellerPortalScreenState();
}

class _SellerPortalScreenState extends State<SellerPortalScreen> {
  final MarketplaceService _marketplaceService = MarketplaceService();
  dynamic _profileData;
  Map<String, dynamic>? _entitlementsData;
  List<dynamic> _myListings = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchSellerData();
  }

  Future<void> _fetchSellerData() async {
    setState(() => _isLoading = true);
    try {
      final resProf = await _marketplaceService.getSellerProfile();
      final resList = await _marketplaceService.getSellerListings();
      final resEnt = await _marketplaceService.getOrganizationEntitlements();

      if (resProf.statusCode == 200 && mounted) {
        final data = jsonDecode(resProf.body);
        _profileData = data['data'];
      }

      if (resEnt.statusCode == 200 && mounted) {
        final data = jsonDecode(resEnt.body);
        _entitlementsData = data['data'] is Map<String, dynamic> ? data['data'] : null;
      }

      if (resList.statusCode == 200 && mounted) {
        final data = jsonDecode(resList.body);
        final raw = data['data'];
        if (raw is List) {
          _myListings = raw;
        } else if (raw is Map && raw['data'] is List) {
          _myListings = raw['data'];
        } else {
          _myListings = [];
        }
      }

      if (mounted) setState(() => _isLoading = false);
    } catch (_) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _handleAddLot() {
    // 1. Verification Gate (Trust is never for sale)
    final org = _entitlementsData?['organization'];
    final profile = _profileData is Map ? _profileData : null;
    final isVerified = org?['is_verified'] == true || profile?['is_verified'] == true;

    if (!isVerified) {
      showDialog(
        context: context,
        builder: (ctx) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: const Row(
            children: [
              Icon(Icons.shield_outlined, color: Color(0xFF6D1A95)),
              SizedBox(width: 8),
              Text('Verification Required', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            ],
          ),
          content: const Text(
            'Trust is Never For Sale: Only verified Ethiopian exporters and producers may publish live export lots. Please submit your ECTA credentials to proceed.',
            style: TextStyle(fontSize: 13, height: 1.4),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Cancel', style: TextStyle(color: Colors.grey)),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF6D1A95),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              ),
              onPressed: () {
                Navigator.pop(ctx);
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => const OrganizationVerificationScreen(initialRole: 'exporter'),
                  ),
                ).then((_) => _fetchSellerData());
              },
              child: const Text('Verify Now', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
            ),
          ],
        ),
      );
      return;
    }

    // 2. Check if lot quota is reached
    final ent = _entitlementsData?['entitlements'];
    final usage = _entitlementsData?['usage'];
    final activeLotsLimit = ent?['active_lots'] ?? _profileData?['listing_quota']?['limit'] ?? 3;
    final activeLotsUsed = usage?['active_lots'] ?? _profileData?['listing_quota']?['active_count'] ?? _myListings.length;

    final limitInt = activeLotsLimit is int ? activeLotsLimit : int.tryParse(activeLotsLimit.toString()) ?? 3;
    final usedInt = activeLotsUsed is int ? activeLotsUsed : int.tryParse(activeLotsUsed.toString()) ?? 0;

    if (limitInt != -1 && usedInt >= limitInt) {
      QuotaLimitSheet.show(context, {
        'feature': 'active_lots',
        'limit': limitInt,
        'used': usedInt,
        'message': 'You have reached your limit of $limitInt active coffee lots on your current plan.',
      });
      return;
    }

    Navigator.pushNamed(context, AppRouter.marketplaceCreateListing).then((_) => _fetchSellerData());
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
    final activeLotsUsed = usage?['active_lots'] ?? _profileData?['listing_quota']?['active_count'] ?? _myListings.length;
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
                                builder: (_) => const OrganizationVerificationScreen(initialRole: 'exporter'),
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
                              builder: (_) => const OrganizationVerificationScreen(initialRole: 'exporter'),
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

              // Active Lots Section Header
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('My Active Coffee Lots', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  ElevatedButton.icon(
                    onPressed: _handleAddLot,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppTheme.primary,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                      elevation: 0,
                    ),
                    icon: const Icon(Icons.add, size: 16),
                    label: const Text('Add Lot', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                  ),
                ],
              ),

              const SizedBox(height: 14),

              if (_myListings.isEmpty)
                Container(
                  padding: const EdgeInsets.all(32),
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: isDark ? AppTheme.surfaceDark : Colors.grey.shade50,
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade200),
                  ),
                  child: Column(
                    children: [
                      const Icon(Icons.coffee_outlined, size: 40, color: Colors.grey),
                      const SizedBox(height: 10),
                      const Text('You have no published lots yet.', style: TextStyle(fontWeight: FontWeight.bold)),
                      const SizedBox(height: 4),
                      const Text(
                        'Add your washing station micro-lots to receive international inquiries and sample requests.',
                        textAlign: TextAlign.center,
                        style: TextStyle(fontSize: 12, color: Colors.grey),
                      ),
                    ],
                  ),
                )
              else
                ListView.separated(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: _myListings.length,
                  separatorBuilder: (_, _) => const SizedBox(height: 12),
                  itemBuilder: (context, index) {
                    final lot = _myListings[index];
                    final cuppingScore = lot['cupping_score'];
                    final price = lot['indicative_price_usd_per_lb'];

                    return Card(
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                      color: isDark ? AppTheme.surfaceDark : Colors.white,
                      elevation: 0,
                      child: ListTile(
                        leading: CircleAvatar(
                          backgroundColor: AppTheme.primary.withValues(alpha: 0.1),
                          child: const Icon(Icons.coffee, color: AppTheme.primary),
                        ),
                        title: Text(
                          lot['title'] ?? 'Coffee Lot #${lot['id']}',
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                        ),
                        subtitle: Text(
                          '${lot['origin_region'] ?? "Ethiopia"} • \$${price ?? "N/A"}/lb • ${lot['available_quantity_bags'] ?? lot['total_quantity_bags'] ?? "N/A"} Bags',
                          style: const TextStyle(fontSize: 11, color: Colors.grey),
                        ),
                        trailing: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            if (cuppingScore != null)
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                decoration: BoxDecoration(
                                  color: AppTheme.primary.withValues(alpha: 0.1),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: Text(
                                  '$cuppingScore Q',
                                  style: const TextStyle(
                                    color: AppTheme.primary,
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                            const SizedBox(width: 4),
                            const Icon(Icons.chevron_right, size: 18),
                          ],
                        ),
                        onTap: () {
                          Navigator.pushNamed(context, AppRouter.coffeeListingDetail, arguments: lot['slug'] ?? lot['id']);
                        },
                      ),
                    );
                  },
                ),
            ],
          ),
        ),
      ),
    );
  }
}
