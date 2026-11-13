import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/auth/auth_navigation.dart';
import 'auth_screen.dart';

/// Password updated: the server has already signed out every other device.
class ResetDoneScreen extends StatelessWidget {
  const ResetDoneScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;

    return AuthPage(
      title: l10n.passwordUpdatedTitle,
      subtitle: l10n.passwordUpdatedBody,
      showBack: false,
      children: [
        Center(
          child: Container(
            width: 88,
            height: 88,
            decoration: BoxDecoration(color: fira.brandTint, shape: BoxShape.circle),
            child: Icon(Icons.check_rounded, size: 44, color: fira.brandPrimary),
          ),
        ),
        const SizedBox(height: 32),
        AuthPrimaryButton(
          label: l10n.signInAction,
          onPressed: () => Navigator.of(context).pushAndRemoveUntil(
            MaterialPageRoute(builder: (_) => const AuthScreen()),
            (route) => route.isFirst,
          ),
        ),
      ],
    );
  }
}
