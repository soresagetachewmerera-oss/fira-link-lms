import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../router/app_router.dart';
import '../../theme/fira_colors.dart';

class OnboardingScreen extends StatefulWidget {
  const OnboardingScreen({super.key});

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  final PageController _pageController = PageController();
  int _currentIndex = 0;

  final List<_OnboardingContent> _pages = const [
    _OnboardingContent(
      badge: 'B2B COMMERCE & EXPORT',
      badgeColor: Color(0xFFF59E0B),
      titleLeading: 'Connect Ethiopian',
      titleAccent: ' export commodities',
      titleTrailing: '\nto global markets',
      description:
          'Directly trade specialty coffee, sesame, pulses, and livestock with verified international buyers across 30+ global destinations.',
      imagePath: 'assets/img/onboarding/onboarding_b2b_trade.png',
      glowColor: Color(0xFFF59E0B),
    ),
    _OnboardingContent(
      badge: 'FIRA AI LEGAL STUDIO',
      badgeColor: Color(0xFFC084FC),
      titleLeading: 'Generate instant',
      titleAccent: ' AI export documents',
      titleTrailing: '\n& customs packages',
      description:
          'Produce bank-ready Proforma Invoices, Bills of Lading, and Certificates of Origin in seconds with Fira AI\'s automated legal intelligence.',
      imagePath: 'assets/img/onboarding/onboarding_ai_docs.png',
      glowColor: Color(0xFF6D1A95),
    ),
    _OnboardingContent(
      badge: 'COFFEE SAFARIS & AGRO-TOURS',
      badgeColor: Color(0xFF10B981),
      titleLeading: 'Book curated',
      titleAccent: ' coffee origin safaris',
      titleTrailing: '\n& agro-expeditions',
      description:
          'Experience guided farm-to-cup journeys across Guji, Yirgacheffe, and Sidama. Reserve harvest immersions and cupping masterclasses.',
      imagePath: 'assets/img/onboarding/onboarding_safari_tours.png',
      glowColor: Color(0xFF10B981),
    ),
    _OnboardingContent(
      badge: 'GLOBAL SUMMITS & ACADEMY',
      badgeColor: Color(0xFF60A5FA),
      titleLeading: 'Access high-level',
      titleAccent: ' business summits',
      titleTrailing: '\n& executive training',
      description:
          'Reserve VIP passes for international bilateral trade forums, expos, and earn accredited trade masterclass certifications.',
      imagePath: 'assets/img/onboarding/onboarding_summits.png',
      glowColor: Color(0xFF3B82F6),
    ),
  ];

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  void _handleNext() {
    HapticFeedback.lightImpact();
    if (_currentIndex < _pages.length - 1) {
      _pageController.nextPage(
        duration: const Duration(milliseconds: 400),
        curve: Curves.easeOutCubic,
      );
    } else {
      _completeOnboarding();
    }
  }

  Future<void> _completeOnboarding() async {
    HapticFeedback.mediumImpact();
    final SharedPreferences prefs = await SharedPreferences.getInstance();
    await prefs.setBool('has_seen_onboarding', true);

    if (!mounted) return;

    Navigator.of(context).pushReplacementNamed(AppRouter.home);
  }

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final textTheme = Theme.of(context).textTheme;
    final bool isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      backgroundColor: fira.surface,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(22, 10, 22, 24),
          child: Column(
            children: [
              // Top Bar: Padded Brand Emblem & Skip Button
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      Container(
                        width: 42,
                        height: 42,
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: fira.card,
                          shape: BoxShape.circle,
                          border: Border.all(
                            color: fira.brandTint.withValues(alpha: 0.8),
                          ),
                          boxShadow: [
                            BoxShadow(
                              color: fira.shadowColor,
                              blurRadius: 8,
                              offset: const Offset(0, 2),
                            ),
                          ],
                        ),
                        clipBehavior: Clip.antiAlias,
                        child: SvgPicture.asset(
                          isDark
                              ? 'assets/img/fira_emblem_white.svg'
                              : 'assets/img/fira_emblem.svg',
                          fit: BoxFit.contain,
                        ),
                      ),
                      const SizedBox(width: 10),
                      Text(
                        'FIRA LINK',
                        style: TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.w800,
                          letterSpacing: 1.4,
                          color: fira.onSurface,
                          fontFamily: 'Montserrat',
                        ),
                      ),
                    ],
                  ),
                  TextButton(
                    onPressed: _completeOnboarding,
                    style: TextButton.styleFrom(
                      foregroundColor: fira.mutedText,
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    ),
                    child: Text(
                      'Skip',
                      style: TextStyle(
                        fontSize: 13.5,
                        fontWeight: FontWeight.w600,
                        color: fira.mutedText,
                      ),
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 6),

              // Middle Page Content
              Expanded(
                child: PageView.builder(
                  controller: _pageController,
                  itemCount: _pages.length,
                  physics: const PageScrollPhysics(
                    parent: BouncingScrollPhysics(),
                  ),
                  onPageChanged: (index) {
                    HapticFeedback.selectionClick();
                    setState(() {
                      _currentIndex = index;
                    });
                  },
                  itemBuilder: (context, index) {
                    final content = _pages[index];
                    return Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const SizedBox(height: 12),

                        // Micro-Badge Pill
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 5),
                          decoration: BoxDecoration(
                            color: content.badgeColor.withValues(alpha: 0.12),
                            borderRadius: BorderRadius.circular(100),
                            border: Border.all(
                              color: content.badgeColor.withValues(alpha: 0.35),
                              width: 1.1,
                            ),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Container(
                                width: 6,
                                height: 6,
                                decoration: BoxDecoration(
                                  color: content.badgeColor,
                                  shape: BoxShape.circle,
                                ),
                              ),
                              const SizedBox(width: 6),
                              Text(
                                content.badge,
                                style: TextStyle(
                                  color: content.badgeColor,
                                  fontSize: 10.5,
                                  fontWeight: FontWeight.w800,
                                  letterSpacing: 1.1,
                                ),
                              ),
                            ],
                          ),
                        ),

                        const SizedBox(height: 12),

                        // Rich Title with Accent
                        _OnboardingTitle(content: content),

                        const SizedBox(height: 14),

                        // High-Def 4K Illustration Container with Ambient Glow
                        Expanded(
                          child: Center(
                            child: _OnboardingArtworkCard(
                              content: content,
                            ),
                          ),
                        ),

                        const SizedBox(height: 14),

                        // Description
                        Text(
                          content.description,
                          style: textTheme.bodyLarge?.copyWith(
                            color: fira.mutedText,
                            fontSize: 13.5,
                            height: 1.45,
                          ),
                        ),
                        const SizedBox(height: 8),
                      ],
                    );
                  },
                ),
              ),

              // Bottom Bar: Animated page dots & Next/Get Started
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  _Indicator(
                    length: _pages.length,
                    activeIndex: _currentIndex,
                    activeColor: _pages[_currentIndex].badgeColor,
                  ),
                  Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      // Page 1 gets quick "Get started" text button
                      if (_currentIndex == 0) ...[
                        TextButton(
                          onPressed: _completeOnboarding,
                          style: TextButton.styleFrom(
                            foregroundColor: fira.brandPrimary,
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          ),
                          child: Text(
                            'Get started',
                            style: TextStyle(
                              color: fira.brandPrimary,
                              fontWeight: FontWeight.w700,
                              fontSize: 14,
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                      ],

                      // Next / Complete Button
                      GestureDetector(
                        onTap: _handleNext,
                        child: AnimatedContainer(
                          duration: const Duration(milliseconds: 300),
                          height: 50,
                          padding: EdgeInsets.symmetric(
                            horizontal: _currentIndex == _pages.length - 1 ? 22 : 18,
                          ),
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              colors: [fira.brandPrimary, fira.brandDeep],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                            borderRadius: BorderRadius.circular(16),
                            boxShadow: [
                              BoxShadow(
                                color: fira.brandPrimary.withValues(alpha: 0.35),
                                blurRadius: 10,
                                offset: const Offset(0, 4),
                              ),
                            ],
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              if (_currentIndex == _pages.length - 1) ...[
                                const Text(
                                  'Explore Fira Link',
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontWeight: FontWeight.bold,
                                    fontSize: 14,
                                  ),
                                ),
                                const SizedBox(width: 8),
                              ],
                              const Icon(
                                Icons.arrow_forward_rounded,
                                color: Colors.white,
                                size: 20,
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _OnboardingArtworkCard extends StatelessWidget {
  const _OnboardingArtworkCard({required this.content});

  final _OnboardingContent content;

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;

    return Container(
      constraints: const BoxConstraints(maxHeight: 280),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: content.glowColor.withValues(alpha: 0.22),
            blurRadius: 28,
            offset: const Offset(0, 10),
            spreadRadius: 2,
          ),
          BoxShadow(
            color: fira.shadowColor,
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(24),
        child: Stack(
          alignment: Alignment.center,
          children: [
            // High-resolution visual
            Image.asset(
              content.imagePath,
              fit: BoxFit.cover,
              width: double.infinity,
              errorBuilder: (context, error, stackTrace) {
                // Fallback clean gradient container
                return Container(
                  height: 250,
                  width: double.infinity,
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: [
                        content.badgeColor.withValues(alpha: 0.15),
                        fira.card,
                      ],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                  ),
                  child: Center(
                    child: Icon(
                      Icons.hub_rounded,
                      size: 64,
                      color: content.badgeColor,
                    ),
                  ),
                );
              },
            ),

            // Subtle perimeter border
            Positioned.fill(
              child: Container(
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(24),
                  border: Border.all(
                    color: Colors.white.withValues(alpha: 0.18),
                    width: 1.5,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _OnboardingTitle extends StatelessWidget {
  const _OnboardingTitle({required this.content});

  final _OnboardingContent content;

  @override
  Widget build(BuildContext context) {
    final textTheme = Theme.of(context).textTheme;
    final fira = context.fira;

    return RichText(
      text: TextSpan(
        style: textTheme.headlineMedium?.copyWith(
          color: fira.onSurface,
          fontWeight: FontWeight.w400,
          fontSize: 25,
          height: 1.25,
        ),
        children: [
          TextSpan(text: content.titleLeading),
          if (content.titleAccent != null)
            WidgetSpan(
              alignment: PlaceholderAlignment.baseline,
              baseline: TextBaseline.alphabetic,
              child: _AccentTextWithUnderline(
                text: content.titleAccent!,
                style: textTheme.headlineMedium?.copyWith(
                      color: fira.brandPrimary,
                      fontWeight: FontWeight.w700,
                      fontStyle: FontStyle.italic,
                      fontSize: 25,
                    ) ??
                    const TextStyle(),
              ),
            ),
          if (content.titleTrailing != null)
            TextSpan(text: content.titleTrailing),
        ],
      ),
    );
  }
}

class _AccentTextWithUnderline extends StatelessWidget {
  const _AccentTextWithUnderline({required this.text, required this.style});

  final String text;
  final TextStyle style;

  @override
  Widget build(BuildContext context) {
    return Stack(
      clipBehavior: Clip.none,
      children: [
        Text(text, style: style),
        Positioned(
          left: 0,
          right: 0,
          top: (style.fontSize ?? 25) * 1.08,
          child: SizedBox(
            height: 6,
            child: CustomPaint(
              painter: _WavyUnderlinePainter(context: context),
            ),
          ),
        ),
      ],
    );
  }
}

class _WavyUnderlinePainter extends CustomPainter {
  const _WavyUnderlinePainter({required this.context});

  final BuildContext context;

  @override
  void paint(Canvas canvas, Size size) {
    final fira = context.fira;
    final paint = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = 3.0
      ..strokeCap = StrokeCap.round;

    final path = Path();
    final baseY = size.height / 2;
    const waveCount = 5;
    final waveLength = size.width / waveCount;

    path.moveTo(0, baseY);

    for (int i = 0; i < waveCount; i++) {
      final progress = i / (waveCount - 1);
      final amplitude = 1.5 + (progress * 4.5);
      final x1 = i * waveLength + waveLength * 0.25;
      final x2 = i * waveLength + waveLength * 0.75;
      final midX = i * waveLength + waveLength * 0.5;

      path.quadraticBezierTo(x1, baseY - amplitude, midX, baseY);
      path.quadraticBezierTo(
        x2,
        baseY + amplitude,
        (i + 1) * waveLength,
        baseY,
      );
    }

    final gradient = LinearGradient(
      colors: [
        fira.brandPrimary.withValues(alpha: 0.7),
        fira.brandPrimary.withValues(alpha: 0.9),
        fira.brandPrimary,
      ],
      stops: const [0.0, 0.5, 1.0],
      begin: Alignment.centerLeft,
      end: Alignment.centerRight,
    );

    final rect = Rect.fromLTWH(0, 0, size.width, size.height);
    paint.shader = gradient.createShader(rect);

    canvas.drawPath(path, paint);
  }

  @override
  bool shouldRepaint(_WavyUnderlinePainter oldDelegate) => true;
}

class _Indicator extends StatelessWidget {
  const _Indicator({
    required this.length,
    required this.activeIndex,
    required this.activeColor,
  });

  final int length;
  final int activeIndex;
  final Color activeColor;

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;

    return Row(
      children: List.generate(length, (index) {
        final bool isActive = index == activeIndex;
        return AnimatedContainer(
          duration: const Duration(milliseconds: 300),
          margin: const EdgeInsets.only(right: 8),
          height: 8,
          width: isActive ? 24 : 8,
          decoration: BoxDecoration(
            color: isActive ? activeColor : fira.brandTint,
            borderRadius: BorderRadius.circular(100),
          ),
        );
      }),
    );
  }
}

class _OnboardingContent {
  const _OnboardingContent({
    required this.badge,
    required this.badgeColor,
    required this.titleLeading,
    this.titleAccent,
    this.titleTrailing,
    required this.description,
    required this.imagePath,
    required this.glowColor,
  });

  final String badge;
  final Color badgeColor;
  final String titleLeading;
  final String? titleAccent;
  final String? titleTrailing;
  final String description;
  final String imagePath;
  final Color glowColor;
}
