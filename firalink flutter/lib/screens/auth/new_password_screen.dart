import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../services/auth_service.dart';
import '../../widgets/auth/auth_messages.dart';
import '../../widgets/auth/auth_navigation.dart';
import '../../widgets/auth/password_checks.dart';
import '../../widgets/fira_error_state.dart';
import 'reset_done_screen.dart';

/// Set a new password: live checks for the three rules the server enforces.
class NewPasswordScreen extends StatefulWidget {
  const NewPasswordScreen({super.key, required this.resetTicket, this.authService});

  final String resetTicket;
  final AuthService? authService;

  @override
  State<NewPasswordScreen> createState() => _NewPasswordScreenState();
}

class _NewPasswordScreenState extends State<NewPasswordScreen> {
  late final AuthService _auth = widget.authService ?? AuthService();
  final _password = TextEditingController();
  final _confirm = TextEditingController();
  bool _obscure = true;
  bool _saving = false;

  @override
  void dispose() {
    _password.dispose();
    _confirm.dispose();
    super.dispose();
  }

  bool get _matches => _confirm.text.isNotEmpty && _confirm.text == _password.text;

  bool get _canSave => PasswordRuleResult.of(_password.text).allMet && _matches;

  Future<void> _save() async {
    if (!_canSave) return;
    setState(() => _saving = true);
    try {
      final res = await _auth.passwordReset(
        ticket: widget.resetTicket,
        password: _password.text,
        confirmation: _confirm.text,
      );
      if (!mounted) return;
      if (res.statusCode == 200) {
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const ResetDoneScreen()),
          (route) => route.isFirst,
        );
        return;
      }
      final body = authBody(res);
      AuthMessages.show(
        context,
        body['code'] == 'TICKET_EXPIRED' ? context.l10n.resetExpired : AuthMessages.forResponse(context, res),
      );
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;

    return AuthPage(
      title: l10n.createNewPasswordTitle,
      showBack: false,
      children: [
        TextField(
          controller: _password,
          obscureText: _obscure,
          autofillHints: const [AutofillHints.newPassword],
          onChanged: (_) => setState(() {}),
          decoration: InputDecoration(
            labelText: l10n.newPassword,
            suffixIcon: IconButton(
              tooltip: _obscure ? l10n.show : l10n.hide,
              icon: Icon(_obscure ? Icons.visibility_outlined : Icons.visibility_off_outlined),
              onPressed: () => setState(() => _obscure = !_obscure),
            ),
          ),
        ),
        const SizedBox(height: 12),
        PasswordChecks(password: _password.text),
        const SizedBox(height: 16),
        TextField(
          controller: _confirm,
          obscureText: _obscure,
          autofillHints: const [AutofillHints.newPassword],
          onChanged: (_) => setState(() {}),
          decoration: InputDecoration(
            labelText: l10n.confirmNewPassword,
            hintText: l10n.typeItAgain,
            errorText: _confirm.text.isNotEmpty && !_matches ? l10n.passwordsDoNotMatch : null,
          ),
        ),
        const SizedBox(height: 24),
        AuthPrimaryButton(label: l10n.savePassword, loading: _saving, onPressed: _canSave ? _save : null),
      ],
    );
  }
}
