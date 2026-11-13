import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen>
    with TickerProviderStateMixin {
  late final AnimationController _entranceController;
  late final AnimationController _pulseController;

  late final Animation<double> _logoScale;
  late final Animation<double> _logoFade;
  late final Animation<Offset> _textSlide;
  late final Animation<double> _textFade;
  late final Animation<Offset> _pillarsSlide;
  late final Animation<double> _pillarsFade;
  late final Animation<double> _pulseAnimation;

  @override
  void initState() {
    super.initState();

    _entranceController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1800),
    );

    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 2400),
    )..repeat(reverse: true);

    _logoScale = CurvedAnimation(
      parent: _entranceController,
      curve: const Interval(0.0, 0.65, curve: Curves.easeOutBack),
    );

    _logoFade = CurvedAnimation(
      parent: _entranceController,
      curve: const Interval(0.0, 0.45, curve: Curves.easeIn),
    );

    _textSlide = Tween<Offset>(
      begin: const Offset(0, 0.35),
      end: Offset.zero,
    ).animate(
      CurvedAnimation(
        parent: _entranceController,
        curve: const Interval(0.35, 0.85, curve: Curves.easeOutCubic),
      ),
    );

    _textFade = CurvedAnimation(
      parent: _entranceController,
      curve: const Interval(0.35, 0.85, curve: Curves.easeIn),
    );

    _pillarsSlide = Tween<Offset>(
      begin: const Offset(0, 0.4),
      end: Offset.zero,
    ).animate(
      CurvedAnimation(
        parent: _entranceController,
        curve: const Interval(0.55, 1.0, curve: Curves.easeOutCubic),
      ),
    );

    _pillarsFade = CurvedAnimation(
      parent: _entranceController,
      curve: const Interval(0.55, 1.0, curve: Curves.easeIn),
    );

    _pulseAnimation = CurvedAnimation(
      parent: _pulseController,
      curve: Curves.easeInOut,
    );

    _entranceController.forward();
  }

  @override
  void dispose() {
    _entranceController.dispose();
    _pulseController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      backgroundColor: isDark ? const Color(0xFF0C0217) : const Color(0xFF140526),
      body: Stack(
        children: [
          // Ambient luxury background radial glow
          Positioned.fill(
            child: AnimatedBuilder(
              animation: _pulseAnimation,
              builder: (context, child) {
                return CustomPaint(
                  painter: _SplashBackgroundPainter(
                    pulseValue: _pulseAnimation.value,
                  ),
                );
              },
            ),
          ),

          SafeArea(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 24),
              child: Column(
                children: [
                  const Spacer(flex: 3),

                  // Center Emblem with Glowing Animated Rings
                  AnimatedBuilder(
                    animation: Listenable.merge([_logoScale, _pulseAnimation]),
                    builder: (context, child) {
                      return Transform.scale(
                        scale: _logoScale.value,
                        child: Opacity(
                          opacity: _logoFade.value.clamp(0.0, 1.0),
                          child: Stack(
                            alignment: Alignment.center,
                            children: [
                              // Outermost pulsing ring
                              Container(
                                width: 170 + (_pulseAnimation.value * 22),
                                height: 170 + (_pulseAnimation.value * 22),
                                decoration: BoxDecoration(
                                  shape: BoxShape.circle,
                                  border: Border.all(
                                    color: const Color(0xFFC084FC).withValues(
                                      alpha: 0.18 * (1.0 - _pulseAnimation.value * 0.4),
                                    ),
                                    width: 1.5,
                                  ),
                                ),
                              ),
                              // Inner golden aura ring
                              Container(
                                width: 140 + (_pulseAnimation.value * 12),
                                height: 140 + (_pulseAnimation.value * 12),
                                decoration: BoxDecoration(
                                  shape: BoxShape.circle,
                                  border: Border.all(
                                    color: const Color(0xFFF59E0B).withValues(
                                      alpha: 0.28 * (1.0 - _pulseAnimation.value * 0.3),
                                    ),
                                    width: 1.8,
                                  ),
                                ),
                              ),
                              // Center Glassmorphic Emblem Container
                              Container(
                                width: 104,
                                height: 104,
                                padding: const EdgeInsets.all(18),
                                decoration: BoxDecoration(
                                  gradient: const LinearGradient(
                                    colors: [
                                      Color(0xFF3B0764),
                                      Color(0xFF24043D),
                                    ],
                                    begin: Alignment.topLeft,
                                    end: Alignment.bottomRight,
                                  ),
                                  shape: BoxShape.circle,
                                  border: Border.all(
                                    color: const Color(0xFFF59E0B).withValues(alpha: 0.6),
                                    width: 2.0,
                                  ),
                                  boxShadow: [
                                    BoxShadow(
                                      color: const Color(0xFF6D1A95).withValues(alpha: 0.65),
                                      blurRadius: 36,
                                      spreadRadius: 4,
                                    ),
                                    BoxShadow(
                                      color: const Color(0xFFF59E0B).withValues(alpha: 0.3),
                                      blurRadius: 20,
                                      spreadRadius: 1,
                                    ),
                                  ],
                                ),
                                child: SvgPicture.asset(
                                  'assets/img/logo_white.svg',
                                  fit: BoxFit.contain,
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),

                  const SizedBox(height: 38),

                  // Staggered Title and Tagline
                  SlideTransition(
                    position: _textSlide,
                    child: FadeTransition(
                      opacity: _textFade,
                      child: Column(
                        children: [
                          // Brand Title
                          ShaderMask(
                            shaderCallback: (bounds) => const LinearGradient(
                              colors: [
                                Colors.white,
                                Color(0xFFFDE68A),
                                Colors.white,
                              ],
                              stops: [0.0, 0.5, 1.0],
                            ).createShader(bounds),
                            child: const Text(
                              'FIRA LINK',
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                fontSize: 30,
                                fontWeight: FontWeight.w900,
                                letterSpacing: 4.5,
                                color: Colors.white,
                                fontFamily: 'Montserrat',
                              ),
                            ),
                          ),
                          const SizedBox(height: 12),
                          // Multi-Service Subtitle
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                            decoration: BoxDecoration(
                              color: Colors.white.withValues(alpha: 0.08),
                              borderRadius: BorderRadius.circular(100),
                              border: Border.all(
                                color: Colors.white.withValues(alpha: 0.12),
                              ),
                            ),
                            child: const Text(
                              'GLOBAL TRADE  •  AI STUDIO  •  SAFARIS',
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w700,
                                letterSpacing: 1.8,
                                color: Color(0xFFE9D5FF),
                              ),
                            ),
                          ),
                          const SizedBox(height: 10),
                          Text(
                            'The Gateway of African Trade & Excellence',
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w400,
                              color: Colors.white.withValues(alpha: 0.65),
                              letterSpacing: 0.3,
                            ),
                          ),
                          const SizedBox(height: 24),
                          SizedBox(
                            width: 130,
                            child: ClipRRect(
                              borderRadius: BorderRadius.circular(8),
                              child: LinearProgressIndicator(
                                minHeight: 3,
                                backgroundColor: Colors.white.withValues(alpha: 0.12),
                                valueColor: const AlwaysStoppedAnimation<Color>(
                                  Color(0xFFF59E0B),
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),

                  const Spacer(flex: 3),

                  // Bottom 4-Pillar Ecosystem Track
                  SlideTransition(
                    position: _pillarsSlide,
                    child: FadeTransition(
                      opacity: _pillarsFade,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.06),
                          borderRadius: BorderRadius.circular(22),
                          border: Border.all(
                            color: Colors.white.withValues(alpha: 0.1),
                          ),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceAround,
                          children: const [
                            _PillarItem(
                              icon: Icons.public_rounded,
                              label: 'B2B Trade',
                              color: Color(0xFFF59E0B),
                            ),
                            _ConnectorDot(),
                            _PillarItem(
                              icon: Icons.auto_awesome_rounded,
                              label: 'Fira AI',
                              color: Color(0xFFC084FC),
                            ),
                            _ConnectorDot(),
                            _PillarItem(
                              icon: Icons.explore_rounded,
                              label: 'Safaris',
                              color: Color(0xFF10B981),
                            ),
                            _ConnectorDot(),
                            _PillarItem(
                              icon: Icons.workspace_premium_rounded,
                              label: 'Summits',
                              color: Color(0xFF60A5FA),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),

                  const SizedBox(height: 12),
                ],
              ),
            ),
          ),
        ],
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
            color: color.withValues(alpha: 0.18),
            shape: BoxShape.circle,
            border: Border.all(
              color: color.withValues(alpha: 0.45),
              width: 1.2,
            ),
          ),
          child: Icon(
            icon,
            color: color,
            size: 19,
          ),
        ),
        const SizedBox(height: 6),
        Text(
          label,
          style: const TextStyle(
            fontSize: 10.5,
            fontWeight: FontWeight.w600,
            color: Colors.white,
            letterSpacing: 0.3,
          ),
        ),
      ],
    );
  }
}

class _ConnectorDot extends StatelessWidget {
  const _ConnectorDot();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 4,
      height: 4,
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.35),
        shape: BoxShape.circle,
      ),
    );
  }
}

class _SplashBackgroundPainter extends CustomPainter {
  const _SplashBackgroundPainter({required this.pulseValue});

  final double pulseValue;

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height * 0.42);

    // Deep purple glow
    final purplePaint = Paint()
      ..shader = RadialGradient(
        colors: [
          const Color(0xFF6D1A95).withValues(alpha: 0.45 + (pulseValue * 0.12)),
          const Color(0xFF3B0764).withValues(alpha: 0.25),
          Colors.transparent,
        ],
        stops: const [0.0, 0.5, 1.0],
      ).createShader(
        Rect.fromCircle(
          center: center,
          radius: size.width * (0.8 + (pulseValue * 0.1)),
        ),
      );

    canvas.drawCircle(center, size.width * 0.85, purplePaint);

    // Warm golden micro-glow
    final goldCenter = Offset(size.width / 2, size.height * 0.38);
    final goldPaint = Paint()
      ..shader = RadialGradient(
        colors: [
          const Color(0xFFF59E0B).withValues(alpha: 0.22 + (pulseValue * 0.08)),
          Colors.transparent,
        ],
        stops: const [0.0, 1.0],
      ).createShader(
        Rect.fromCircle(center: goldCenter, radius: size.width * 0.4),
      );

    canvas.drawCircle(goldCenter, size.width * 0.4, goldPaint);
  }

  @override
  bool shouldRepaint(_SplashBackgroundPainter oldDelegate) {
    return oldDelegate.pulseValue != pulseValue;
  }
}
