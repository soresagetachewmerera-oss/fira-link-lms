import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../services/api_exception.dart';
import '../../services/marketplace_service.dart';
import '../../l10n/app_localizations.dart';
import '../../theme/app_theme.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';

class MarketplacePricingScreen extends StatefulWidget {
  const MarketplacePricingScreen({super.key});

  @override
  State<MarketplacePricingScreen> createState() => _MarketplacePricingScreenState();
}

class _MarketplacePricingScreenState extends State<MarketplacePricingScreen> {
  final MarketplaceService _marketplaceService = MarketplaceService();
  List<dynamic> _plans = [];
  Map<String, dynamic>? _orgData;
  String _currency = 'ETB'; // 'ETB' or 'USD'
  String _billingCycle = 'monthly'; // 'monthly' or 'annual'
  bool _isLoading = true;
  Object? _loadError;
  bool _isSubscribing = false;

  @override
  void initState() {
    super.initState();
    _fetchData();
  }

  Future<void> _fetchData() async {
    setState(() {
      _isLoading = true;
      _loadError = null;
    });
    try {
      final resPlans = requireSuccess(await _marketplaceService.getSubscriptionPlans());
      final resEnt = await _marketplaceService.getOrganizationEntitlements();

      final raw = jsonDecode(resPlans.body)['data'];
      final plans = raw is List ? raw : (raw is Map && raw['data'] is List ? raw['data'] as List : <dynamic>[]);

      // 401/404: signed out or no business workspace yet. Plans still show.
      Map<String, dynamic>? org;
      if (resEnt.statusCode == 200) {
        final data = jsonDecode(resEnt.body);
        org = data['data'] is Map<String, dynamic> ? data['data'] : null;
      } else if (resEnt.statusCode != 401 && resEnt.statusCode != 404) {
        throw ApiException.fromResponse(resEnt);
      }

      if (!mounted) return;
      setState(() {
        _plans = plans;
        _orgData = org;
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

  double _getPlanPrice(dynamic plan) {
    if (_currency == 'ETB') {
      if (_billingCycle == 'annual') {
        final annual = plan['annual_price_etb'];
        if (annual != null && annual > 0) return (annual as num).toDouble();
        final monthly = plan['price_etb'] ?? 0;
        return ((monthly as num).toDouble() * 10); // 2 months free
      }
      return ((plan['price_etb'] ?? 0) as num).toDouble();
    } else {
      if (_billingCycle == 'annual') {
        final annual = plan['annual_price_usd'];
        if (annual != null && annual > 0) return (annual as num).toDouble();
        final monthly = plan['price_usd'] ?? 0;
        return ((monthly as num).toDouble() * 10);
      }
      return ((plan['price_usd'] ?? 0) as num).toDouble();
    }
  }

  void _upgrade(dynamic plan) async {
    // 1. Enforce RBAC: Only Organization Owners can manage billing
    final role = (_orgData?['organization']?['my_role'] ?? 'owner').toString().toLowerCase();
    if (role != 'owner' && role != 'super_admin' && role != 'admin') {
      showDialog(
        context: context,
        builder: (ctx) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: const Row(
            children: [
              Icon(Icons.shield_outlined, color: AppTheme.primary),
              SizedBox(width: 8),
              Text('Owner Only', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            ],
          ),
          content: Text(
            'Only organization owners can modify subscription plans and billing. Your current role is ${role.toUpperCase()}. Please contact your organization owner.',
            style: const TextStyle(fontSize: 13, height: 1.4),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Understand'),
            ),
          ],
        ),
      );
      return;
    }

    setState(() => _isSubscribing = true);

    try {
      final res = await _marketplaceService.subscribe({
        'plan_id': plan['id'],
        'plan_key': plan['key'],
        'period': _billingCycle,
        'currency': _currency,
        'gateway': 'chapa',
      });

      if (!mounted) return;
      setState(() => _isSubscribing = false);

      final l10n = AppLocalizations.of(context);
      final fira = context.fira;
      Map<String, dynamic> data = const {};
      try {
        final decoded = jsonDecode(res.body);
        if (decoded is Map<String, dynamic>) data = decoded;
      } catch (_) {}

      // Only a server-confirmed activation counts; a missing checkout URL is not a success (GAP-009).
      if (res.statusCode == 200 && data['success'] == true) {
        if (data['status'] == 'activated') {
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(content: Text(l10n.planActivated), backgroundColor: fira.success),
            );
            Navigator.pop(context);
          }
        } else if (data['checkout_url'] != null) {
          final checkoutUrl = data['checkout_url'].toString();
          final uri = Uri.tryParse(checkoutUrl);
          if (uri != null) {
            final launched = await launchUrl(uri, mode: LaunchMode.externalApplication);
            if (!launched && mounted) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text('Checkout URL: $checkoutUrl'),
                  backgroundColor: AppTheme.primary,
                ),
              );
            }
          }
        } else if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(l10n.planCheckoutFailed), backgroundColor: fira.danger),
          );
        }
      } else {
        final msg = data['code'] == 'CHAPA_OFF' ? l10n.chapaPaymentsOff : l10n.planCheckoutFailed;
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(msg), backgroundColor: fira.danger),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isSubscribing = false);
        showFiraActionError(context, e);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Membership Plans & SaaS Quotas', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppTheme.primary))
          : _loadError != null
          ? FiraErrorState(error: _loadError, onRetry: _fetchData)
          : SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
              child: Column(
                children: [
                  // Header description
                  const Text(
                    'Unlock Direct Global Coffee Trade',
                    style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold, letterSpacing: -0.5),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 6),
                  Text(
                    'Transparent quotas for washing stations, cooperatives, and high-volume exporters. Open deals continue uninterrupted across all plans.',
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 12, color: isDark ? Colors.white60 : Colors.grey.shade600, height: 1.4),
                  ),
                  const SizedBox(height: 20),

                  // Controls Container (Currency & Billing Cycle)
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: isDark ? AppTheme.surfaceDark : Colors.grey.shade50,
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade200),
                    ),
                    child: Column(
                      children: [
                        // Currency Selector Toggle
                        Row(
                          children: [
                            const Text('Currency:', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                            const Spacer(),
                            Container(
                              decoration: BoxDecoration(
                                color: isDark ? Colors.black26 : Colors.white,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: isDark ? Colors.white12 : Colors.grey.shade300),
                              ),
                              child: Row(
                                children: [
                                  _buildCurrencyOption('ETB', 'ETB (Birr)'),
                                  _buildCurrencyOption('USD', 'USD (\$)'),
                                ],
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        const Divider(height: 1),
                        const SizedBox(height: 12),

                        // Billing Cycle Selector Toggle
                        Row(
                          children: [
                            const Text('Billing Period:', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                            const Spacer(),
                            Container(
                              decoration: BoxDecoration(
                                color: isDark ? Colors.black26 : Colors.white,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: isDark ? Colors.white12 : Colors.grey.shade300),
                              ),
                              child: Row(
                                children: [
                                  _buildCycleOption('monthly', 'Monthly'),
                                  _buildCycleOption('annual', 'Annual (-17%)'),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 20),

                  // Plan Cards List
                  ListView.separated(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    itemCount: _plans.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 18),
                    itemBuilder: (context, index) {
                      final plan = _plans[index];
                      final name = plan['name'] ?? 'Plan';
                      final desc = plan['description'] ?? '';
                      final isPopular = plan['is_popular'] == true || plan['key'] == 'growth';
                      final features = plan['features'] as List<dynamic>? ?? [];
                      final price = _getPlanPrice(plan);
                      final badgeText = plan['badge'] ?? (isPopular ? 'MOST POPULAR' : null);

                      return Card(
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(24),
                          side: BorderSide(
                            color: isPopular ? AppTheme.primary : (isDark ? Colors.white12 : Colors.grey.shade200),
                            width: isPopular ? 2 : 1,
                          ),
                        ),
                        color: isDark ? AppTheme.surfaceDark : Colors.white,
                        elevation: 0,
                        child: Padding(
                          padding: const EdgeInsets.all(22.0),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              // Badge (if applicable)
                              if (badgeText != null)
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                  margin: const EdgeInsets.only(bottom: 12),
                                  decoration: BoxDecoration(
                                    color: AppTheme.primary,
                                    borderRadius: BorderRadius.circular(20),
                                  ),
                                  child: Text(
                                    badgeText.toString().toUpperCase(),
                                    style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.w800),
                                  ),
                                ),

                              Text(name, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                              const SizedBox(height: 4),
                              Text(desc, style: const TextStyle(fontSize: 12, color: Colors.grey, height: 1.35)),
                              const SizedBox(height: 16),

                              // Price display
                              Row(
                                crossAxisAlignment: CrossAxisAlignment.baseline,
                                textBaseline: TextBaseline.alphabetic,
                                children: [
                                  Text(
                                    _currency == 'ETB'
                                        ? '${price == 0 ? "Free" : price.toStringAsFixed(0)} ETB'
                                        : '${price == 0 ? "Free" : "\$${price.toStringAsFixed(0)}"} USD',
                                    style: const TextStyle(fontSize: 26, fontWeight: FontWeight.w800),
                                  ),
                                  Text(
                                    price == 0
                                        ? ' / lifetime'
                                        : (_billingCycle == 'annual' ? ' / year' : ' / month'),
                                    style: const TextStyle(fontSize: 12, color: Colors.grey),
                                  ),
                                  if (_billingCycle == 'annual' && price > 0) ...[
                                    const Spacer(),
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                      decoration: BoxDecoration(
                                        color: Colors.green.shade50,
                                        borderRadius: BorderRadius.circular(8),
                                      ),
                                      child: const Text('2 Mo Free', style: TextStyle(color: Colors.green, fontSize: 10, fontWeight: FontWeight.bold)),
                                    ),
                                  ],
                                ],
                              ),

                              const SizedBox(height: 16),
                              const Divider(height: 1),
                              const SizedBox(height: 16),

                              // Features list
                              ...features.map((f) {
                                final fStr = f.toString();
                                final isComingSoon = fStr.toLowerCase().contains('esignature') || fStr.toLowerCase().contains('e-signature');

                                return Padding(
                                  padding: const EdgeInsets.only(bottom: 9.0),
                                  child: Row(
                                    children: [
                                      const Icon(Icons.check_circle, size: 16, color: Colors.green),
                                      const SizedBox(width: 10),
                                      Expanded(
                                        child: Text(fStr, style: const TextStyle(fontSize: 12)),
                                      ),
                                      if (isComingSoon)
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                          decoration: BoxDecoration(
                                            color: context.fira.brandTint,
                                            borderRadius: BorderRadius.circular(12),
                                          ),
                                          child: Text(AppLocalizations.of(context).planFeatureComingSoon, style: TextStyle(color: context.fira.pending, fontSize: 11, fontWeight: FontWeight.w600)),
                                        ),
                                    ],
                                  ),
                                );
                              }),

                              const SizedBox(height: 20),

                              // Upgrade CTA Button
                              SizedBox(
                                width: double.infinity,
                                child: ElevatedButton(
                                  onPressed: _isSubscribing ? null : () => _upgrade(plan),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: isPopular ? AppTheme.primary : (isDark ? Colors.white12 : Colors.black87),
                                    foregroundColor: Colors.white,
                                    padding: const EdgeInsets.symmetric(vertical: 14),
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                                    elevation: 0,
                                  ),
                                  child: _isSubscribing
                                      ? const SizedBox(
                                          width: 20,
                                          height: 20,
                                          child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                                        )
                                      : Text(
                                          price == 0 ? 'Activate Free Starter Tier' : 'Upgrade to $name',
                                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                                        ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),

                  const SizedBox(height: 24),

                  // Trust is never for sale notice
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: isDark ? Colors.white.withValues(alpha: 0.05) : Colors.grey.shade100,
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.verified_user_outlined, size: 20, color: AppTheme.primary),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            'Trust is Never For Sale: Platform subscription plans do not influence verification tiers, cupping scores, or trade disputes. Verification is strictly earned through rigorous documentation and quality testing.',
                            style: TextStyle(
                              fontSize: 10.5,
                              color: isDark ? Colors.white60 : Colors.grey.shade700,
                              height: 1.35,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 24),
                ],
              ),
            ),
    );
  }

  Widget _buildCurrencyOption(String value, String label) {
    final isSelected = _currency == value;
    return GestureDetector(
      onTap: () => setState(() => _currency = value),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: isSelected ? AppTheme.primary : Colors.transparent,
          borderRadius: BorderRadius.circular(10),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 11,
            fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
            color: isSelected ? Colors.white : Colors.grey,
          ),
        ),
      ),
    );
  }

  Widget _buildCycleOption(String value, String label) {
    final isSelected = _billingCycle == value;
    return GestureDetector(
      onTap: () => setState(() => _billingCycle = value),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: isSelected ? AppTheme.primary : Colors.transparent,
          borderRadius: BorderRadius.circular(10),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 11,
            fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
            color: isSelected ? Colors.white : Colors.grey,
          ),
        ),
      ),
    );
  }
}
