import 'package:flutter/material.dart';
import 'package:hugeicons/hugeicons.dart';
import 'package:provider/provider.dart';
import '../../models/subscription_plan_model.dart';
import '../../providers/subscription_provider.dart';
import '../../providers/auth_provider.dart';
import '../../router/app_router.dart';
import '../../theme/app_theme.dart';
import 'subscription_checkout_screen.dart';

class SubscriptionsScreen extends StatefulWidget {
  const SubscriptionsScreen({super.key});

  @override
  State<SubscriptionsScreen> createState() => _SubscriptionsScreenState();
}

class _SubscriptionsScreenState extends State<SubscriptionsScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final provider = Provider.of<SubscriptionProvider>(context, listen: false);
      provider.fetchPlans();
      provider.fetchUserSubscription();
    });
  }

  @override
  Widget build(BuildContext context) {
    final provider = Provider.of<SubscriptionProvider>(context);
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Subscription Plans'),
        elevation: 0,
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          await provider.fetchPlans();
          await provider.fetchUserSubscription();
        },
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(20.0),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [
                      AppTheme.primary,
                      AppTheme.primary.withValues(alpha: 0.8),
                    ],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(16.0),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: const [
                    Row(
                      children: [
                        HugeIcon(icon: HugeIcons.strokeRoundedCrown, color: Colors.amber, size: 28),
                        SizedBox(width: 8),
                        Text(
                          'Unlock Unlimited Learning',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                    SizedBox(height: 8),
                    Text(
                      'Choose a plan that fits your goals and get access to top-rated courses and bundles.',
                      style: TextStyle(
                        color: Colors.white70,
                        fontSize: 13,
                        height: 1.4,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),

              // Active Subscription Card if active
              if (provider.hasActiveSubscription) ...[
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(16.0),
                  decoration: BoxDecoration(
                    color: isDark ? Colors.green.shade900.withValues(alpha: 0.3) : Colors.green.shade50,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.green.shade400),
                  ),
                  child: Row(
                    children: [
                      const HugeIcon(icon: HugeIcons.strokeRoundedCheckmarkCircle02, color: Colors.green, size: 28),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Active: ${provider.activeSubscription!.planName}',
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              provider.activeSubscription!.isLifetime
                                  ? 'Lifetime Access Active'
                                  : 'Expires in ${provider.activeSubscription!.remainingDays} days',
                              style: TextStyle(fontSize: 12, color: isDark ? Colors.white70 : Colors.black87),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 24),
              ],

              // Plans list
              const Text(
                'Available Plans',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 16),

              if (provider.isLoadingPlans)
                const Center(child: Padding(padding: EdgeInsets.all(32), child: CircularProgressIndicator()))
              else if (provider.plans.isEmpty)
                const Center(
                  child: Padding(
                    padding: EdgeInsets.all(32),
                    child: Text('No active subscription plans available right now.'),
                  ),
                )
              else
                ListView.separated(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: provider.plans.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 16),
                  itemBuilder: (context, index) {
                    final plan = provider.plans[index];
                    return _buildPlanCard(context, plan, provider.activeSubscription, isDark);
                  },
                ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildPlanCard(BuildContext context, SubscriptionPlanModel plan, dynamic userSub, bool isDark) {
    final isCurrentPlan = userSub != null && userSub.planId == plan.id;

    return Container(
      decoration: BoxDecoration(
        color: AppTheme.getCardColor(context),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: plan.isFeatured
              ? AppTheme.primary
              : (isDark ? Colors.white12 : Colors.grey.shade300),
          width: plan.isFeatured ? 2 : 1,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          if (plan.isFeatured)
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(vertical: 4),
              decoration: const BoxDecoration(
                color: AppTheme.primary,
                borderRadius: BorderRadius.only(
                  topLeft: Radius.circular(14),
                  topRight: Radius.circular(14),
                ),
              ),
              child: const Text(
                'MOST POPULAR',
                textAlign: TextAlign.center,
                style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold, letterSpacing: 1.1),
              ),
            ),

          Padding(
            padding: const EdgeInsets.all(20.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      plan.name,
                      style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: AppTheme.primary.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Text(
                        plan.billingPeriod.toUpperCase(),
                        style: const TextStyle(color: AppTheme.primary, fontSize: 11, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),

                // Price
                Row(
                  crossAxisAlignment: CrossAxisAlignment.baseline,
                  textBaseline: TextBaseline.alphabetic,
                  children: [
                    Text(
                      plan.formattedPrice,
                      style: const TextStyle(fontSize: 28, fontWeight: FontWeight.w800, color: AppTheme.primary),
                    ),
                    Text(
                      ' / ${plan.billingPeriod}',
                      style: TextStyle(fontSize: 14, color: isDark ? Colors.white60 : Colors.grey.shade600),
                    ),
                  ],
                ),

                if (plan.description != null && plan.description!.isNotEmpty) ...[
                  const SizedBox(height: 10),
                  Text(
                    plan.description!,
                    style: TextStyle(fontSize: 13, color: isDark ? Colors.white70 : Colors.grey.shade700),
                  ),
                ],
                const SizedBox(height: 16),
                const Divider(),
                const SizedBox(height: 12),

                // Included counts
                Row(
                  children: [
                    const HugeIcon(icon: HugeIcons.strokeRoundedBookOpen01, size: 18, color: AppTheme.primary),
                    const SizedBox(width: 8),
                    Text('${plan.includedCoursesCount} Included Courses', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                  ],
                ),
                if (plan.includedBundlesCount > 0) ...[
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      const HugeIcon(icon: HugeIcons.strokeRoundedPackage, size: 18, color: Colors.orange),
                      const SizedBox(width: 8),
                      Text('${plan.includedBundlesCount} Included Bundles', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                    ],
                  ),
                ],

                // Feature checklist
                if (plan.features.isNotEmpty) ...[
                  const SizedBox(height: 12),
                  ...plan.features.map(
                    (feature) => Padding(
                      padding: const EdgeInsets.only(bottom: 8.0),
                      child: Row(
                        children: [
                          const HugeIcon(icon: HugeIcons.strokeRoundedCheckmarkBadge01, size: 16, color: Colors.green),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(feature, style: const TextStyle(fontSize: 13)),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],

                const SizedBox(height: 20),

                // Button
                SizedBox(
                  width: double.infinity,
                  height: 48,
                  child: ElevatedButton(
                    onPressed: isCurrentPlan
                        ? null
                        : () {
                            final auth = Provider.of<AuthProvider>(context, listen: false);
                            if (!auth.isAuthenticated) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('Please log in to subscribe to a plan.')),
                              );
                              Navigator.pushNamed(context, AppRouter.auth);
                              return;
                            }
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (_) => SubscriptionCheckoutScreen(plan: plan),
                              ),
                            );
                          },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppTheme.primary,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    child: Text(
                      isCurrentPlan ? 'Current Active Plan' : 'Subscribe Now',
                      style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
