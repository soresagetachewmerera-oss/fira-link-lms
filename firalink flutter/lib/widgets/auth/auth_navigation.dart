import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../router/app_router.dart';
import '../../screens/splash/startup_route.dart';
import '../../theme/fira_colors.dart';
import 'auth_gate.dart';

/// Browse without an account (design: "Explore as guest").
Future<void> exploreAsGuest(BuildContext context) async {
  final prefs = await SharedPreferences.getInstance();
  await prefs.setBool(StartupPrefs.seenOnboarding, true);
  if (!context.mounted) return;
  Navigator.of(context).pushNamedAndRemoveUntil(AppRouter.home, (_) => false);
}

/// After sign-in: back to the screen that asked for it (a gate, see auth_gate.dart), otherwise new
/// accounts choose their intent first and returning users go Home.
void goAfterSignIn(BuildContext context, {required bool isNewUser}) {
  if (SignInResume.tryResume(context)) return;
  Navigator.of(context).pushNamedAndRemoveUntil(
    isNewUser ? AppRouter.intentSelection : AppRouter.home,
    (_) => false,
  );
}

/// Shared page frame for the auth flow: title, subtitle, content. Theme tokens only.
class AuthPage extends StatelessWidget {
  const AuthPage({
    super.key,
    required this.title,
    this.subtitle,
    required this.children,
    this.showBack = true,
  });

  final String title;
  final String? subtitle;
  final List<Widget> children;
  final bool showBack;

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final textTheme = Theme.of(context).textTheme;

    return Scaffold(
      appBar: AppBar(automaticallyImplyLeading: showBack),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
          children: [
            Text(title, style: textTheme.headlineMedium?.copyWith(color: fira.onSurface)),
            if (subtitle != null) ...[
              const SizedBox(height: 8),
              Text(subtitle!, style: textTheme.bodyLarge?.copyWith(color: fira.mutedText)),
            ],
            const SizedBox(height: 24),
            ...children,
          ],
        ),
      ),
    );
  }
}

/// Full-width primary button with a loading state.
class AuthPrimaryButton extends StatelessWidget {
  const AuthPrimaryButton({super.key, required this.label, required this.onPressed, this.loading = false});

  final String label;
  final VoidCallback? onPressed;
  final bool loading;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: double.infinity,
      child: FilledButton(
        onPressed: loading ? null : onPressed,
        child: loading
            ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2))
            : Text(label),
      ),
    );
  }
}
