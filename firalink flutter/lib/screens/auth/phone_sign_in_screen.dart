import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../l10n/app_localizations.dart';
import '../../services/auth_service.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/auth/auth_messages.dart';
import '../../widgets/auth/auth_navigation.dart';
import '../../widgets/fira_error_state.dart';
import 'auth_screen.dart';
import 'phone_code_screen.dart';

/// S-004 Phone sign in: one step for new and returning users.
class PhoneSignInScreen extends StatefulWidget {
  const PhoneSignInScreen({super.key, this.authService});

  final AuthService? authService;

  @override
  State<PhoneSignInScreen> createState() => _PhoneSignInScreenState();
}

class _PhoneSignInScreenState extends State<PhoneSignInScreen> {
  late final AuthService _auth = widget.authService ?? AuthService();
  final _phoneController = TextEditingController();
  String _countryCode = '+251';
  bool _sending = false;
  String? _fieldError;

  /// Ethiopia first (the default). SMS codes are sent to Ethiopian numbers only;
  /// other codes get a clear "sign in with email" answer from the server.
  static const _countryCodes = ['+251', '+254', '+253', '+252', '+1', '+44', '+49', '+81', '+86', '+971'];

  static final _ethiopianMobile = RegExp(r'^(?:\+?251|0)?([79]\d{8})$');

  @override
  void dispose() {
    _phoneController.dispose();
    super.dispose();
  }

  Future<void> _sendCode() async {
    final l10n = context.l10n;
    final raw = _phoneController.text.replaceAll(RegExp(r'[\s\-()]'), '');

    if (_countryCode == '+251' && !_ethiopianMobile.hasMatch(raw)) {
      setState(() => _fieldError = l10n.invalidPhone);
      return;
    }
    setState(() {
      _fieldError = null;
      _sending = true;
    });

    try {
      final res = await _auth.phoneStart(raw, countryCode: _countryCode);
      if (!mounted) return;
      final body = authBody(res);

      if (res.statusCode == 200) {
        _openCode(raw, body, smsFailed: false);
      } else if (body['code'] == 'SMS_FAILED') {
        // The SMS did not go out: the code screen offers the code by email.
        AuthMessages.showResponse(context, res);
        _openCode(raw, body, smsFailed: true);
      } else {
        AuthMessages.showResponse(context, res);
      }
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  void _openCode(String phone, Map<String, dynamic> body, {required bool smsFailed}) {
    Navigator.of(context).push(MaterialPageRoute(
      builder: (_) => PhoneCodeScreen.signIn(
        phone: phone,
        phoneMasked: body['phone_masked']?.toString() ?? phone,
        cooldownSeconds: (body['cooldown_seconds'] as num?)?.toInt() ?? 60,
        expiryMinutes: (body['expiry_minutes'] as num?)?.toInt() ?? 10,
        smsFailed: smsFailed,
        authService: _auth,
      ),
    ));
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    final textTheme = Theme.of(context).textTheme;

    return AuthPage(
      title: l10n.phoneSignInTitle,
      subtitle: l10n.phoneSignInSubtitle,
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            SizedBox(
              width: 132,
              child: DropdownButtonFormField<String>(
                initialValue: _countryCode,
                isExpanded: true,
                decoration: InputDecoration(labelText: l10n.countryCodeLabel),
                items: _countryCodes.map((c) => DropdownMenuItem(value: c, child: Text(c))).toList(),
                onChanged: (v) => setState(() => _countryCode = v ?? '+251'),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: TextField(
                controller: _phoneController,
                keyboardType: TextInputType.phone,
                autofillHints: const [AutofillHints.telephoneNumberNational],
                inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[0-9 ]'))],
                textInputAction: TextInputAction.done,
                onSubmitted: (_) => _sendCode(),
                decoration: InputDecoration(
                  labelText: l10n.phoneNumberLabel,
                  hintText: '9XX XXX XXX',
                  errorText: _fieldError,
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 8),
        Text(l10n.countryCodeHint, style: textTheme.bodySmall?.copyWith(color: fira.mutedText)),
        const SizedBox(height: 24),
        AuthPrimaryButton(label: l10n.sendCode, loading: _sending, onPressed: _sendCode),
        const SizedBox(height: 20),
        Row(
          children: [
            const Expanded(child: Divider()),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 12),
              child: Text(l10n.orDivider, style: textTheme.bodyMedium?.copyWith(color: fira.mutedText)),
            ),
            const Expanded(child: Divider()),
          ],
        ),
        const SizedBox(height: 20),
        SizedBox(
          width: double.infinity,
          child: OutlinedButton.icon(
            icon: const Icon(Icons.mail_outline_rounded),
            label: Text(l10n.continueWithEmail),
            onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const AuthScreen())),
          ),
        ),
        const SizedBox(height: 24),
        Text(l10n.termsNotice, textAlign: TextAlign.center, style: textTheme.bodySmall?.copyWith(color: fira.mutedText)),
        const SizedBox(height: 8),
        Center(
          child: TextButton(onPressed: () => exploreAsGuest(context), child: Text(l10n.exploreAsGuest)),
        ),
      ],
    );
  }
}
