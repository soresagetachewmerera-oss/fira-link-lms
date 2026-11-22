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
      duration: const Duration(milliseconds: 800),
    );

    _fadeAnimation = CurvedAnimation(
      parent: _entranceController,
      curve: Curves.easeOutCubic,
    );

    _slideAnimation = Tween<Offset>(
      begin: const Offset(0, 0.06),
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
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
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
                      // Official Center Emblem Jewel
                      Container(
                        width: 92,
                        height: 92,
                        decoration: BoxDecoration(
                          color: const Color(0xFF1E0533),
                          borderRadius: BorderRadius.circular(24),
                          border: Border.all(
                            color: fira.brandPrimary.withValues(alpha: 0.35),
                            width: 1.5,
                          ),
                          boxShadow: [
                            BoxShadow(
                              color: fira.brandPrimary.withValues(alpha: isDark ? 0.22 : 0.14),
                              blurRadius: 24,
                              offset: const Offset(0, 8),
                            ),
                          ],
                        ),
                        padding: const EdgeInsets.all(18),
                        child: Image.asset(
                          'assets/img/fira_emblem_white.png',
                          fit: BoxFit.contain,
                        ),
                      ),

                      const SizedBox(height: 22),

                      // Brand Wordmark
                      Text(
                        'FIRA LINK',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          fontSize: 26,
                          fontWeight: FontWeight.w800,
                          letterSpacing: 4.0,
                          color: isDark ? Colors.white : fira.onSurface,
                          fontFamily: 'Montserrat',
                        ),
                      ),

                      const SizedBox(height: 8),

                      // Brand Tagline
                      Text(
                        'The Gateway of African Trade & Excellence',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w500,
                          color: isDark ? Colors.white.withValues(alpha: 0.70) : fira.mutedText,
                          letterSpacing: 0.2,
                          fontFamily: 'Montserrat',
                        ),
                      ),

                      const SizedBox(height: 32),

                      // Quiet Indeterminate Progress Bar
                      SizedBox(
                        width: 120,
                        child: ClipRRect(
                          borderRadius: BorderRadius.circular(8),
                          child: LinearProgressIndicator(
                            minHeight: 3.5,
                            backgroundColor: isDark
                                ? Colors.white.withValues(alpha: 0.10)
                                : fira.brandTint,
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

              // Bottom 4-Pillar Ecosystem Capsule (Quiet Chrome, Proper Contrast)
              FadeTransition(
                opacity: _fadeAnimation,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                  decoration: BoxDecoration(
                    color: isDark ? Colors.white.withValues(alpha: 0.04) : fira.surface,
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(
                      color: isDark
                          ? Colors.white.withValues(alpha: 0.08)
                          : fira.brandPrimary.withValues(alpha: 0.12),
                    ),
                    boxShadow: isDark
                        ? null
                        : [
                            BoxShadow(
                              color: Colors.black.withValues(alpha: 0.04),
                              blurRadius: 16,
                              offset: const Offset(0, 4),
                            ),
                          ],
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceAround,
                    children: [
                      _PillarItem(
                        icon: Icons.storefront_outlined,
                        label: 'B2B Trade',
                        color: fira.brandPrimary,
                        isDark: isDark,
                        onSurfaceColor: fira.onSurface,
                      ),
                      _PillarItem(
                        icon: Icons.psychology_outlined,
                        label: 'Fira AI',
                        color: fira.brandPrimary,
                        isDark: isDark,
                        onSurfaceColor: fira.onSurface,
                      ),
                      _PillarItem(
                        icon: Icons.terrain_outlined,
                        label: 'Safaris',
                        color: fira.brandPrimary,
                        isDark: isDark,
                        onSurfaceColor: fira.onSurface,
                      ),
                      _PillarItem(
                        icon: Icons.event_seat_outlined,
                        label: 'Summits',
                        color: fira.brandPrimary,
                        isDark: isDark,
                        onSurfaceColor: fira.onSurface,
                      ),
                    ],
                  ),
                ),
              ),

              const SizedBox(height: 14),

              // Subtle Origin Note
              Text(
                'Addis Ababa, Ethiopia · Direct Origin Export',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w400,
                  color: isDark
                      ? Colors.white.withValues(alpha: 0.35)
                      : fira.mutedText.withValues(alpha: 0.65),
                  fontFamily: 'Montserrat',
                  letterSpacing: 0.2,
                ),
              ),

              const SizedBox(height: 8),
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
    required this.isDark,
    required this.onSurfaceColor,
  });

  final IconData icon;
  final String label;
  final Color color;
  final bool isDark;
  final Color onSurfaceColor;

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 36,
          height: 36,
          decoration: BoxDecoration(
            color: color.withValues(alpha: isDark ? 0.18 : 0.08),
            shape: BoxShape.circle,
            border: Border.all(
              color: color.withValues(alpha: isDark ? 0.32 : 0.20),
              width: 1.0,
            ),
          ),
          child: Icon(
            icon,
            color: isDark ? const Color(0xFFE9D5FF) : color,
            size: 18,
          ),
        ),
        const SizedBox(height: 6),
        Text(
          label,
          style: TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.w600,
            color: isDark ? Colors.white.withValues(alpha: 0.85) : onSurfaceColor,
            letterSpacing: 0.1,
            fontFamily: 'Montserrat',
          ),
        ),
      ],
    );
  }
}
