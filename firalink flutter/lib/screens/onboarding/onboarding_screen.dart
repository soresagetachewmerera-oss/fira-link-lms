import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../l10n/app_localizations.dart';
import '../../router/app_router.dart';
import '../splash/startup_route.dart';
import '../../theme/fira_colors.dart';

class OnboardingScreen extends StatefulWidget {
  const OnboardingScreen({super.key});

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  final PageController _pageController = PageController();
  int _currentIndex = 0;

  static const int _pageCount = 3;

  /// The three onboarding messages from the design (S-003), localized.
  /// Visual treatment (badges, artwork, glow) is the existing app's.
  List<_OnboardingContent> _pages(BuildContext context) {
    final l10n = context.l10n;
    return [
      _OnboardingContent(
        badge: l10n.onboardingBadge1,
        badgeColor: const Color(0xFFF59E0B),
        titleLeading: l10n.onboardingTitle1,
        description: l10n.onboardingBody1,
        imagePath: 'assets/img/onboarding/onboarding_b2b_trade.png',
        glowColor: const Color(0xFFF59E0B),
      ),
      _OnboardingContent(
        badge: l10n.onboardingBadge2,
        badgeColor: const Color(0xFFC084FC),
        titleLeading: l10n.onboardingTitle2,
        description: l10n.onboardingBody2,
        imagePath: 'assets/img/onboarding/onboarding_ai_docs.png',
        glowColor: const Color(0xFF6D1A95),
      ),
      _OnboardingContent(
        badge: l10n.onboardingBadge3,
        badgeColor: const Color(0xFF10B981),
        titleLeading: l10n.onboardingTitle3,
        description: l10n.onboardingBody3,
        imagePath: 'assets/img/onboarding/onboarding_safari_tours.png',
        glowColor: const Color(0xFF10B981),
      ),
    ];
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  void _handleNext() {
    HapticFeedback.lightImpact();
    if (_currentIndex < _pageCount - 1) {
      _pageController.nextPage(
        duration: const Duration(milliseconds: 400),
        curve: Curves.easeOutCubic,
      );
    } else {
      _completeOnboarding(toSignIn: true);
    }
  }

  /// Skip and Get started go to phone sign-in; Explore as guest goes to Home.
  Future<void> _completeOnboarding({required bool toSignIn}) async {
    HapticFeedback.mediumImpact();
    final SharedPreferences prefs = await SharedPreferences.getInstance();
    await prefs.setBool(StartupPrefs.seenOnboarding, true);

    if (!mounted) return;

    Navigator.of(context).pushReplacementNamed(toSignIn ? AppRouter.auth : AppRouter.home);
  }

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final textTheme = Theme.of(context).textTheme;
    final bool isDark = Theme.of(context).brightness == Brightness.dark;
    final pages = _pages(context);

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
                    onPressed: () => _completeOnboarding(toSignIn: true),
                    style: TextButton.styleFrom(
                      foregroundColor: fira.mutedText,
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    ),
                    child: Text(
                      context.l10n.skipAction,
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
                  itemCount: pages.length,
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
                    final content = pages[index];
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
                    length: pages.length,
                    activeIndex: _currentIndex,
                    activeColor: pages[_currentIndex].badgeColor,
                  ),
                  Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      // Every page: browse without an account
                      TextButton(
                        onPressed: () => _completeOnboarding(toSignIn: false),
                        style: TextButton.styleFrom(
                          foregroundColor: fira.brandPrimary,
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        ),
                        child: Text(
                          context.l10n.exploreAsGuest,
                          style: TextStyle(
                            color: fira.brandPrimary,
                            fontWeight: FontWeight.w700,
                            fontSize: 14,
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),

                      // Next / Complete Button
                      GestureDetector(
                        onTap: _handleNext,
                        child: AnimatedContainer(
                          duration: const Duration(milliseconds: 300),
                          height: 50,
                          padding: EdgeInsets.symmetric(
                            horizontal: _currentIndex == pages.length - 1 ? 22 : 18,
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
                              if (_currentIndex == pages.length - 1) ...[
                                Text(
                                  context.l10n.getStartedAction,
                                  style: const TextStyle(
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

    // One localized sentence per page (the split accent styling needs fixed English word order).
    return Text(
      content.titleLeading,
      style: textTheme.headlineMedium?.copyWith(
        color: fira.onSurface,
        fontWeight: FontWeight.w700,
        fontSize: 25,
        height: 1.25,
      ),
    );
  }
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
    required this.description,
    required this.imagePath,
    required this.glowColor,
  });

  final String badge;
  final Color badgeColor;
  final String titleLeading;
  final String description;
  final String imagePath;
  final Color glowColor;
}
