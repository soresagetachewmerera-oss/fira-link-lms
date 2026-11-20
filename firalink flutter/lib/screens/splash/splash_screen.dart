import 'package:flutter/material.dart';
import '../../theme/fira_colors.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen>
    with SingleTickerProviderStateMixin {
  late final AnimationController _entranceController;
  late final Animation<double> _fadeAnimation;
  late final Animation<Offset> _slideAnimation;

  @override
  void initState() {
    super.initState();

    _entranceController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 900),
    );

    _fadeAnimation = CurvedAnimation(
      parent: _entranceController,
      curve: Curves.easeOutCubic,
    );

    _slideAnimation = Tween<Offset>(
      begin: const Offset(0, 0.08),
      end: Offset.zero,
    ).animate(
      CurvedAnimation(
        parent: _entranceController,
        curve: Curves.easeOutCubic,
      ),
    );

    _entranceController.forward();
  }

  @override
  void dispose() {
    _entranceController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final fira = context.fira;

    return Scaffold(
      backgroundColor: isDark ? const Color(0xFF0F0B1E) : const Color(0xFFFAF7FC),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 24),
          child: Column(
            children: [
              const Spacer(flex: 3),

              // Centered Official Fira Link Emblem & Wordmark
              FadeTransition(
                opacity: _fadeAnimation,
                child: SlideTransition(
                  position: _slideAnimation,
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      // Center Emblem Container
                      Container(
                        width: 96,
                        height: 96,
                        decoration: BoxDecoration(
                          color: const Color(0xFF1E0533),
                          borderRadius: BorderRadius.circular(24),
                          border: Border.all(
                            color: fira.brandPrimary.withValues(alpha: 0.40),
                            width: 1.5,
                          ),
                          boxShadow: [
                            BoxShadow(
                              color: fira.brandPrimary.withValues(alpha: 0.22),
                              blurRadius: 24,
                              offset: const Offset(0, 8),
                            ),
                          ],
                        ),
                        padding: const EdgeInsets.all(16),
                        child: Image.asset(
                          'assets/img/fira_emblem_white.png',
                          fit: BoxFit.contain,
                        ),
                      ),

                      const SizedBox(height: 20),

                      // Brand Wordmark
                      const Text(
                        'FIRA LINK',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          fontSize: 28,
                          fontWeight: FontWeight.w800,
                          letterSpacing: 3.5,
                          color: Colors.white,
                          fontFamily: 'Montserrat',
                        ),
                      ),

                      const SizedBox(height: 8),

                      // Tagline
                      Text(
                        'The Gateway of African Trade & Excellence',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w400,
                          color: Colors.white.withValues(alpha: 0.70),
                          letterSpacing: 0.4,
                          fontFamily: 'Montserrat',
                        ),
                      ),

                      const SizedBox(height: 32),

                      // Elegant Indeterminate Progress Bar
                      SizedBox(
                        width: 140,
                        child: ClipRRect(
                          borderRadius: BorderRadius.circular(8),
                          child: LinearProgressIndicator(
                            minHeight: 3.5,
                            backgroundColor: Colors.white.withValues(alpha: 0.10),
                            valueColor: AlwaysStoppedAnimation<Color>(
                              fira.brandPrimary,
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),

              const Spacer(flex: 3),

              // Bottom 4-Pillar Ecosystem Footer
              FadeTransition(
                opacity: _fadeAnimation,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.04),
                    borderRadius: BorderRadius.circular(22),
                    border: Border.all(
                      color: Colors.white.withValues(alpha: 0.08),
                    ),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceAround,
                    children: [
                      _PillarItem(
                        icon: Icons.public_rounded,
                        label: 'B2B Trade',
                        color: fira.brandPrimary,
                      ),
                      _PillarItem(
                        icon: Icons.auto_awesome_rounded,
                        label: 'Fira AI',
                        color: fira.brandPrimary,
                      ),
                      _PillarItem(
                        icon: Icons.explore_rounded,
                        label: 'Safaris',
                        color: fira.brandPrimary,
                      ),
                      _PillarItem(
                        icon: Icons.workspace_premium_rounded,
                        label: 'Summits',
                        color: fira.brandPrimary,
                      ),
                    ],
                  ),
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

class _PillarItem extends StatelessWidget {
  const _PillarItem({
    required this.icon,
    required this.label,
    required this.color,
  });

  final IconData icon;
  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 38,
          height: 38,
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.20),
            shape: BoxShape.circle,
            border: Border.all(
              color: color.withValues(alpha: 0.40),
              width: 1.2,
            ),
          ),
          child: Icon(
            icon,
            color: const Color(0xFFE9D5FF),
            size: 19,
          ),
        ),
        const SizedBox(height: 6),
        Text(
          label,
          style: const TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.w500,
            color: Colors.white,
            letterSpacing: 0.3,
            fontFamily: 'Montserrat',
          ),
        ),
      ],
    );
  }
}
