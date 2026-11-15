import 'package:flutter/material.dart';
import '../../l10n/app_localizations.dart';
import '../../widgets/bottom_nav_bar.dart';
import '../marketplace/organization_verification_screen.dart';
import '../shell/main_shell_screen.dart';

class IntentWelcomeScreen extends StatelessWidget {
  final String intent; // 'export', 'buy', 'learn', 'events'

  const IntentWelcomeScreen({super.key, required this.intent});

  @override
  Widget build(BuildContext context) {
    const primaryColor = Color(0xFF6D1A95);

    final Map<String, dynamic> config = switch (intent) {
      'export' => {
          'title': 'Welcome to Fira Trade',
          'subtitle': 'Your export workspace is configured. Next: get verified by ECTA guidelines or publish your first coffee lot.',
          'actionTitle': 'Go to Exporter Dashboard',
          'tab': BottomNavTab.b2b,
          'icon': Icons.local_shipping_outlined,
          'cardTitle': 'Tiered Exporter Verification',
          'cardDesc': 'Attach your MoTI business license & ECTA competence certificate to earn the Verified Exporter Badge.',
        },
      'buy' => {
          'title': 'Welcome to Direct Sourcing',
          'subtitle': 'Your buyer profile is active. Next: browse verified origin lots, request sealed samples, or publish an RFQ tender.',
          'actionTitle': 'Explore Coffee Marketplace',
          'tab': BottomNavTab.b2b,
          'icon': Icons.shopping_bag_outlined,
          'cardTitle': 'Request First Coffee Sample',
          'cardDesc': 'Request 250g type samples with cryptographic seal verification and track DHL/courier dispatch.',
        },
      'events' => {
          'title': 'Welcome to Origin Tours',
          'subtitle': 'Discover Ethiopia\'s legendary coffee terroirs, washing stations, and harvest festivals.',
          'actionTitle': 'Browse Upcoming Tours',
          'tab': BottomNavTab.tours,
          'icon': Icons.explore_outlined,
          'cardTitle': 'Upcoming Harvest Tours',
          'cardDesc': 'Explore Yirgacheffe, Sidama, Guji & Jimma with certified guides and cupping sessions.',
        },
      _ => {
          'title': 'Welcome to Fira Academy',
          'subtitle': 'Learn the nuances of Ethiopian coffee, export regulations, cupping protocols, and international trade.',
          'actionTitle': 'Start Learning',
          'tab': BottomNavTab.courses,
          'icon': Icons.school_outlined,
          'cardTitle': 'Featured Masterclass',
          'cardDesc': 'Ethiopian Coffee Export Readiness & Quality Grading Standards.',
        },
    };

    return Scaffold(
      backgroundColor: Colors.white,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 24.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Spacer(),
              Center(
                child: Container(
                  width: 90,
                  height: 90,
                  decoration: BoxDecoration(
                    color: primaryColor.withValues(alpha: 0.1),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    config['icon'] as IconData,
                    color: primaryColor,
                    size: 46,
                  ),
                ),
              ),
              const SizedBox(height: 28),
              Text(
                config['title'],
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF1E293B),
                ),
              ),
              const SizedBox(height: 10),
              Text(
                config['subtitle'],
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 14, color: Colors.black54, height: 1.4),
              ),
              const SizedBox(height: 36),

              // "Next Step" Card
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: primaryColor.withValues(alpha: 0.2)),
                  color: primaryColor.withValues(alpha: 0.04),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: primaryColor,
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: const Text(
                            'RECOMMENDED NEXT STEP',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 10,
                              fontWeight: FontWeight.bold,
                              letterSpacing: 0.5,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Text(
                      config['cardTitle'],
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF1E293B),
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      config['cardDesc'],
                      style: TextStyle(fontSize: 12, color: Colors.grey.shade600, height: 1.3),
                    ),
                  ],
                ),
              ),

              if (intent == 'export' || intent == 'buy') ...[
                const SizedBox(height: 8),
                OutlinedButton.icon(
                  key: const Key('welcome-start-verification'),
                  icon: const Icon(Icons.verified_user_outlined),
                  label: Text(context.l10n.verifyStart),
                  onPressed: () {
                    final nav = Navigator.of(context);
                    nav.pushAndRemoveUntil(
                      MaterialPageRoute(builder: (_) => MainShellScreen(initialTab: config['tab'])),
                      (route) => false,
                    );
                    nav.push(MaterialPageRoute(builder: (_) => const OrganizationVerificationScreen()));
                  },
                ),
              ],

              const Spacer(),

              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: primaryColor,
                  foregroundColor: Colors.white,
                  minimumSize: const Size.fromHeight(54),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  elevation: 0,
                ),
                onPressed: () {
                  Navigator.pushAndRemoveUntil(
                    context,
                    MaterialPageRoute(
                      builder: (_) => MainShellScreen(initialTab: config['tab']),
                    ),
                    (route) => false,
                  );
                },
                child: Text(
                  config['actionTitle'],
                  style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                ),
              ),
              const SizedBox(height: 12),
            ],
          ),
        ),
      ),
    );
  }
}

