import 'dart:async';

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

  /// Waits until the stored session is checked (never decides while it is still unknown).
  Future<void> _waitForSession(AuthProvider auth) async {
    if (auth.status != AuthStatus.unknown) return;
    final done = Completer<void>();
    void listener() {
      if (auth.status != AuthStatus.unknown && !done.isCompleted) done.complete();
    }

    auth.addListener(listener);
    try {
      await done.future.timeout(const Duration(seconds: 15), onTimeout: () {});
    } finally {
      auth.removeListener(listener);
    }
  }

  void _checkAuthAndNavigate() async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    await _waitForSession(authProvider);
    final SharedPreferences prefs = await SharedPreferences.getInstance();

    if (!mounted) return;

    final route = decideStartupRoute(
      // A stored session that could not be checked (offline) is kept: Home, with protected screens
      // offering Retry. It is never shown as a guest (decision D-13).
      isAuthenticated: authProvider.isAuthenticated || authProvider.status == AuthStatus.offline,
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
