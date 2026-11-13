import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../services/auth_service.dart';
import '../../widgets/auth/auth_messages.dart';
import '../../widgets/auth/auth_navigation.dart';
import '../../widgets/auth/password_checks.dart';
import '../../widgets/fira_error_state.dart';
import 'phone_code_screen.dart';

/// Email and password sign-up, for people without an Ethiopian phone number.
/// The account is created only after the emailed 6-digit code is confirmed.
class EmailSignUpScreen extends StatefulWidget {
  const EmailSignUpScreen({super.key, this.authService});

  final AuthService? authService;

  @override
  State<EmailSignUpScreen> createState() => _EmailSignUpScreenState();
}

class _EmailSignUpScreenState extends State<EmailSignUpScreen> {
  late final AuthService _auth = widget.authService ?? AuthService();
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _password = TextEditingController();
  final _confirm = TextEditingController();
  bool _terms = false;
  bool _obscure = true;
  bool _sending = false;
  String? _nameError;
  String? _emailError;

  static final _emailPattern = RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$');

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _password.dispose();
    _confirm.dispose();
    super.dispose();
  }

  bool get _passwordOk =>
      PasswordRuleResult.of(_password.text).allMet && _confirm.text == _password.text;

  Future<void> _submit() async {
    final l10n = context.l10n;
    final name = _name.text.trim();
    final email = _email.text.trim().toLowerCase();

    setState(() {
      _nameError = name.length < 2 ? l10n.fullNameRequired : null;
      _emailError = _emailPattern.hasMatch(email) ? null : l10n.invalidEmail;
    });
    if (_nameError != null || _emailError != null || !_passwordOk) return;
    if (!_terms) {
      AuthMessages.show(context, l10n.acceptTermsRequired);
      return;
    }

    setState(() => _sending = true);
    try {
      final res = await _auth.emailSignUpStart(
        fullName: name,
        email: email,
        password: _password.text,
        confirmation: _confirm.text,
      );
      if (!mounted) return;
      if (res.statusCode != 200) {
        AuthMessages.showResponse(context, res);
        return;
      }
      final body = authBody(res);
      Navigator.of(context).push(MaterialPageRoute(
        builder: (_) => PhoneCodeScreen.emailSignUp(
          email: email,
          cooldownSeconds: (body['cooldown_seconds'] as num?)?.toInt() ?? 60,
          expiryMinutes: (body['expiry_minutes'] as num?)?.toInt() ?? 10,
          authService: _auth,
        ),
      ));
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;

    return AuthPage(
      title: l10n.emailSignUpTitle,
      subtitle: l10n.emailSignUpSubtitle,
      children: [
        TextField(
          controller: _name,
          textCapitalization: TextCapitalization.words,
          autofillHints: const [AutofillHints.name],
          decoration: InputDecoration(labelText: l10n.fullNameLabel, errorText: _nameError),
        ),
        const SizedBox(height: 16),
        TextField(
          controller: _email,
          keyboardType: TextInputType.emailAddress,
          autofillHints: const [AutofillHints.email],
          decoration: InputDecoration(labelText: l10n.email, hintText: 'name@company.com', errorText: _emailError),
        ),
        const SizedBox(height: 16),
        TextField(
          controller: _password,
          obscureText: _obscure,
          autofillHints: const [AutofillHints.newPassword],
          onChanged: (_) => setState(() {}),
          decoration: InputDecoration(
            labelText: l10n.password,
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
            labelText: l10n.confirmPassword,
            hintText: l10n.typeItAgain,
            errorText: _confirm.text.isNotEmpty && _confirm.text != _password.text ? l10n.passwordsDoNotMatch : null,
          ),
        ),
        const SizedBox(height: 8),
        CheckboxListTile(
          contentPadding: EdgeInsets.zero,
          controlAffinity: ListTileControlAffinity.leading,
          value: _terms,
          onChanged: (v) => setState(() => _terms = v ?? false),
          title: Text(l10n.acceptTermsLabel),
        ),
        const SizedBox(height: 16),
        AuthPrimaryButton(label: l10n.sendCode, loading: _sending, onPressed: _passwordOk ? _submit : null),
      ],
    );
  }
}
