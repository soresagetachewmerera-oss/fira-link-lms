import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../providers/auth_provider.dart';
import '../../router/app_router.dart';
import 'splash_screen.dart';
import 'startup_route.dart';

class SplashWrapper extends StatefulWidget {
  const SplashWrapper({super.key});

  @override
  State<SplashWrapper> createState() => _SplashWrapperState();
}

class _SplashWrapperState extends State<SplashWrapper> {
  @override
  void initState() {
    super.initState();
    Future.delayed(const Duration(milliseconds: 2600), () {
      if (mounted) {
        _checkAuthAndNavigate();
      }
    });
  }

  void _checkAuthAndNavigate() async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final SharedPreferences prefs = await SharedPreferences.getInstance();

    if (!mounted) return;

    final route = decideStartupRoute(
      isAuthenticated: authProvider.isAuthenticated,
      languageChosen: prefs.getBool(StartupPrefs.languageChosen) ?? false,
      seenOnboarding: prefs.getBool(StartupPrefs.seenOnboarding) ?? false,
    );

    final routeName = switch (route) {
      StartupRoute.home => AppRouter.home,
      StartupRoute.languagePicker => AppRouter.languageChoice,
      StartupRoute.onboarding => AppRouter.onboarding,
    };
    Navigator.of(context).pushReplacementNamed(routeName);
  }

  @override
  Widget build(BuildContext context) {
    return const SplashScreen();
  }
}
