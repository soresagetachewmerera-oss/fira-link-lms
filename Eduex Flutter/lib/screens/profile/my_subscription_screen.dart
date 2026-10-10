import 'package:flutter/material.dart';
import 'package:hugeicons/hugeicons.dart';
import 'package:provider/provider.dart';
import '../../providers/subscription_provider.dart';
import '../../theme/app_theme.dart';
import '../subscriptions/subscriptions_screen.dart';

class MySubscriptionScreen extends StatefulWidget {
  const MySubscriptionScreen({super.key});

  @override
  State<MySubscriptionScreen> createState() => _MySubscriptionScreenState();
}

class _MySubscriptionScreenState extends State<MySubscriptionScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<SubscriptionProvider>(context, listen: false).fetchUserSubscription();
    });
  }

  @override
  Widget build(BuildContext context) {
    final provider = Provider.of<SubscriptionProvider>(context);
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final sub = provider.activeSubscription;

    return Scaffold(
      appBar: AppBar(
        title: const Text('My Subscription'),
        actions: [
          IconButton(
            icon: const HugeIcon(icon: HugeIcons.strokeRoundedRefresh, color: AppTheme.primary, size: 20),
            onPressed: () => provider.fetchUserSubscription(),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => await provider.fetchUserSubscription(),
        child: provider.isLoadingSubscription
            ? const Center(child: CircularProgressIndicator())
            : SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (!provider.hasActiveSubscription || sub == null) ...[
                      // Empty state
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(24.0),
                        decoration: BoxDecoration(
                          color: AppTheme.getCardColor(context),
                          borderRadius: BorderRadius.circular(16),
                        ),
                        child: Column(
                          children: [
                            const HugeIcon(icon: HugeIcons.strokeRoundedCrown, size: 48, color: Colors.amber),
                            const SizedBox(height: 12),
                            const Text(
                              'No Active Subscription',
                              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                            ),
                            const SizedBox(height: 8),
                            const Text(
                              'Subscribe to a plan to unlock access to all included courses and bundles.',
                              textAlign: TextAlign.center,
                              style: TextStyle(color: Colors.grey, fontSize: 13),
                            ),
                            const SizedBox(height: 20),
                            ElevatedButton(
                              onPressed: () {
                                Navigator.push(
                                  context,
                                  MaterialPageRoute(builder: (_) => const SubscriptionsScreen()),
                                );
                              },
                              style: ElevatedButton.styleFrom(
                                backgroundColor: AppTheme.primary,
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                              ),
                              child: const Text('Browse Plans', style: TextStyle(color: Colors.white)),
                            ),
                          ],
                        ),
                      ),
                    ] else ...[
                      // Active Subscription Header Card
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(20.0),
                        decoration: BoxDecoration(
                          gradient: LinearGradient(
                            colors: [AppTheme.primary, Colors.indigo.shade700],
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                          ),
                          borderRadius: BorderRadius.circular(16),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  sub.planName,
                                  style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.bold),
                                ),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                  decoration: BoxDecoration(
                                    color: Colors.green,
                                    borderRadius: BorderRadius.circular(20),
                                  ),
                                  child: const Text('ACTIVE', style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
                                ),
                              ],
                            ),
                            const SizedBox(height: 12),
                            Text(
                              sub.isLifetime
                                  ? 'Lifetime Access Granted'
                                  : 'Remaining: ${sub.remainingDays} days',
                              style: const TextStyle(color: Colors.white70, fontSize: 14),
                            ),
                            const SizedBox(height: 16),
                            Row(
                              children: [
                                if (sub.startsAt != null)
                                  Text(
                                    'Started: ${sub.startsAt!.day}/${sub.startsAt!.month}/${sub.startsAt!.year}',
                                    style: const TextStyle(color: Colors.white60, fontSize: 12),
                                  ),
                                const Spacer(),
                                ElevatedButton.icon(
                                  onPressed: () {
                                    Navigator.push(
                                      context,
                                      MaterialPageRoute(builder: (_) => const SubscriptionsScreen()),
                                    );
                                  },
                                  icon: const HugeIcon(icon: HugeIcons.strokeRoundedCrown, size: 16, color: Colors.amber),
                                  label: const Text('Change Plan', style: TextStyle(fontSize: 12, color: Colors.white)),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Colors.white.withValues(alpha: 0.2),
                                    elevation: 0,
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 24),

                      // Included Courses List
                      const Text('Included Courses', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                      const SizedBox(height: 12),

                      if (sub.includedCourses.isEmpty)
                        const Text('No specific courses assigned to this plan.', style: TextStyle(color: Colors.grey))
                      else
                        ListView.separated(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          itemCount: sub.includedCourses.length,
                          separatorBuilder: (_, __) => const SizedBox(height: 8),
                          itemBuilder: (context, index) {
                            final item = sub.includedCourses[index];
                            return Container(
                              decoration: BoxDecoration(
                                color: AppTheme.getCardColor(context),
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: isDark ? Colors.white12 : Colors.grey.shade200),
                              ),
                              child: ListTile(
                                leading: CircleAvatar(
                                  backgroundColor: AppTheme.primary.withValues(alpha: 0.1),
                                  child: const HugeIcon(icon: HugeIcons.strokeRoundedBookOpen01, color: AppTheme.primary, size: 20),
                                ),
                                title: Text(item.title, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
                                trailing: TextButton(
                                  onPressed: provider.isEnrolling
                                      ? null
                                      : () async {
                                          final ok = await provider.enrollCourse(item.id);
                                          if (ok && context.mounted) {
                                            ScaffoldMessenger.of(context).showSnackBar(
                                              const SnackBar(content: Text('Enrolled via Subscription!')),
                                            );
                                          }
                                        },
                                  child: Text(item.isEnrolled ? 'Enrolled' : 'Access Now'),
                                ),
                              ),
                            );
                          },
                        ),
                    ],
                  ],
                ),
              ),
      ),
    );
  }
}
