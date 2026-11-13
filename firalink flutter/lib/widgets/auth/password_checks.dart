import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../theme/fira_colors.dart';

/// The password rules, identical to the server's (App\Services\Auth\PasswordRules):
/// at least 8 characters, a number, a capital letter.
class PasswordRuleResult {
  const PasswordRuleResult({required this.minLength, required this.hasNumber, required this.hasCapital});

  factory PasswordRuleResult.of(String password) => PasswordRuleResult(
        minLength: password.length >= 8,
        hasNumber: RegExp(r'[0-9]').hasMatch(password),
        hasCapital: RegExp(r'[A-Z]').hasMatch(password),
      );

  final bool minLength;
  final bool hasNumber;
  final bool hasCapital;

  bool get allMet => minLength && hasNumber && hasCapital;
}

/// Live checklist under the new-password field.
class PasswordChecks extends StatelessWidget {
  const PasswordChecks({super.key, required this.password});

  final String password;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final result = PasswordRuleResult.of(password);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _Check(label: l10n.ruleMinLength, met: result.minLength),
        _Check(label: l10n.ruleNumber, met: result.hasNumber),
        _Check(label: l10n.ruleCapital, met: result.hasCapital),
      ],
    );
  }
}

class _Check extends StatelessWidget {
  const _Check({required this.label, required this.met});

  final String label;
  final bool met;

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Semantics(
        label: '$label: ${met ? 'met' : 'not met'}',
        excludeSemantics: true,
        child: Row(
          children: [
            Icon(
              met ? Icons.check_circle_rounded : Icons.radio_button_unchecked_rounded,
              size: 18,
              color: met ? fira.success : fira.mutedText,
            ),
            const SizedBox(width: 8),
            Text(
              label,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: met ? fira.onSurface : fira.mutedText),
            ),
          ],
        ),
      ),
    );
  }
}
