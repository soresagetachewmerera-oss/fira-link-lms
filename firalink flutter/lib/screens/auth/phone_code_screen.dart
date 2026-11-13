import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../l10n/app_localizations.dart';
import '../../providers/auth_provider.dart';
import '../../services/auth_service.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/auth/auth_messages.dart';
import '../../widgets/auth/auth_navigation.dart';
import '../../widgets/auth/code_input.dart';
import '../../widgets/fira_error_state.dart';
import 'full_name_screen.dart';
import 'new_password_screen.dart';

enum _CodeMode { signIn, reset, emailSignUp }

/// S-005 Verify phone code, and the reset-code step of Forgot password.
/// 6 digits, resend countdown from the server, expiry notice, "never share" warning.
class PhoneCodeScreen extends StatefulWidget {
  const PhoneCodeScreen.signIn({
    super.key,
    required String phone,
    required this.phoneMasked,
    required this.cooldownSeconds,
    required this.expiryMinutes,
    this.smsFailed = false,
    this.authService,
  })  : _mode = _CodeMode.signIn,
        _value = phone,
        channel = 'phone';

  const PhoneCodeScreen.reset({
    super.key,
    required this.channel,
    required String value,
    required this.cooldownSeconds,
    required this.expiryMinutes,
    this.authService,
  })  : _mode = _CodeMode.reset,
        _value = value,
        phoneMasked = value,
        smsFailed = false;

  /// Email sign-up: the code was sent to [email]; confirming it creates the account.
  const PhoneCodeScreen.emailSignUp({
    super.key,
    required String email,
    required this.cooldownSeconds,
    required this.expiryMinutes,
    this.authService,
  })  : _mode = _CodeMode.emailSignUp,
        _value = email,
        phoneMasked = email,
        channel = 'email',
        smsFailed = false;

  final _CodeMode _mode;

  /// Phone number (sign-in) or the phone/email the reset code was sent to.
  final String _value;
  final String channel;
  final String phoneMasked;
  final int cooldownSeconds;
  final int expiryMinutes;
  final bool smsFailed;
  final AuthService? authService;

  @override
  State<PhoneCodeScreen> createState() => _PhoneCodeScreenState();
}

class _PhoneCodeScreenState extends State<PhoneCodeScreen> {
  late final AuthService _auth = widget.authService ?? AuthService();
  final _codeController = TextEditingController();
  Timer? _timer;
  late int _secondsLeft = widget.cooldownSeconds;
  late bool _offerEmail = widget.smsFailed;
  bool _submitting = false;
  bool _hasError = false;

  @override
  void initState() {
    super.initState();
    _startCountdown(widget.cooldownSeconds);
  }

  @override
  void dispose() {
    _timer?.cancel();
    _codeController.dispose();
    super.dispose();
  }

  void _startCountdown(int seconds) {
    _timer?.cancel();
    setState(() => _secondsLeft = seconds);
    _timer = Timer.periodic(const Duration(seconds: 1), (t) {
      if (!mounted) return t.cancel();
      setState(() => _secondsLeft = _secondsLeft > 0 ? _secondsLeft - 1 : 0);
      if (_secondsLeft == 0) t.cancel();
    });
  }

  Future<void> _verify([String? value]) async {
    final code = value ?? _codeController.text;
    if (code.length != 6 || _submitting) return;
    setState(() {
      _submitting = true;
      _hasError = false;
    });

    try {
      final res = switch (widget._mode) {
        _CodeMode.signIn => await _auth.phoneVerify(widget._value, code),
        _CodeMode.reset => await _auth.passwordVerifyCode(channel: widget.channel, value: widget._value, code: code),
        _CodeMode.emailSignUp => await _auth.emailSignUpVerify(widget._value, code),
      };
      if (!mounted) return;
      final body = authBody(res);

      if (res.statusCode != 200) {
        setState(() => _hasError = true);
        _codeController.clear();
        AuthMessages.showResponse(context, res);
        return;
      }

      if (widget._mode == _CodeMode.reset) {
        _openNewPassword(body['reset_ticket'].toString());
        return;
      }

      if (widget._mode == _CodeMode.emailSignUp) {
        await context.read<AuthProvider>().completeSignIn(body);
        if (!mounted) return;
        goAfterSignIn(context, isNewUser: true);
        return;
      }

      switch (body['status']) {
        case 'new_user':
          Navigator.of(context).pushReplacement(MaterialPageRoute(
            builder: (_) => FullNameScreen(signupTicket: body['signup_ticket'].toString(), authService: _auth),
          ));
        case 'password_reset_required':
          AuthMessages.show(context, context.l10n.passwordResetRequired);
          _openNewPassword(body['reset_ticket'].toString());
        default:
          await context.read<AuthProvider>().completeSignIn(body);
          if (!mounted) return;
          goAfterSignIn(context, isNewUser: body['is_new_user'] == true);
      }
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  void _openNewPassword(String ticket) {
    Navigator.of(context).pushReplacement(MaterialPageRoute(
      builder: (_) => NewPasswordScreen(resetTicket: ticket, authService: _auth),
    ));
  }

  Future<void> _resend() async {
    if (widget._mode == _CodeMode.emailSignUp) {
      // The password is not kept on the device: resend from the sign-up form.
      Navigator.of(context).pop();
      return;
    }
    try {
      final res = widget._mode == _CodeMode.signIn
          ? await _auth.phoneStart(widget._value)
          : await _auth.passwordForgot(channel: widget.channel, value: widget._value);
      if (!mounted) return;
      final body = authBody(res);
      if (res.statusCode == 200) {
        _startCountdown((body['cooldown_seconds'] as num?)?.toInt() ?? widget.cooldownSeconds);
        setState(() => _offerEmail = false);
      } else {
        if (body['code'] == 'SMS_FAILED') setState(() => _offerEmail = true);
        if (body['cooldown_seconds'] is num) _startCountdown((body['cooldown_seconds'] as num).toInt());
        AuthMessages.showResponse(context, res);
      }
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    }
  }

  Future<void> _codeByEmail() async {
    try {
      final res = await _auth.phoneEmailFallback(widget._value);
      if (!mounted) return;
      if (res.statusCode == 200) {
        AuthMessages.show(context, context.l10n.codeSentByEmailIfAccount);
        setState(() => _offerEmail = false);
      } else {
        AuthMessages.showResponse(context, res);
      }
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    final textTheme = Theme.of(context).textTheme;
    final isSignIn = widget._mode == _CodeMode.signIn;
    final isEmailSignUp = widget._mode == _CodeMode.emailSignUp;

    return AuthPage(
      title: isSignIn || isEmailSignUp ? l10n.enterCodeTitle : l10n.checkYourMessages,
      subtitle: isSignIn
          ? l10n.translate('codeSentToPhone', {'phone': widget.phoneMasked})
          : l10n.translate('codeSentToDestination', {'destination': widget.phoneMasked}),
      children: [
        CodeInput(
          controller: _codeController,
          hasError: _hasError,
          enabled: !_submitting,
          onChanged: (_) {
            if (_hasError) setState(() => _hasError = false);
          },
          onCompleted: _verify,
        ),
        const SizedBox(height: 16),
        Row(
          children: [
            Expanded(
              child: Text(
                l10n.translate('codeValidMinutes', {'minutes': widget.expiryMinutes}),
                style: textTheme.bodySmall?.copyWith(color: fira.mutedText),
              ),
            ),
            TextButton(
              onPressed: _secondsLeft == 0 ? _resend : null,
              child: Text(_secondsLeft == 0
                  ? l10n.resendCode
                  : l10n.translate('resendCodeIn', {'seconds': _secondsLeft})),
            ),
          ],
        ),
        if (isSignIn && _offerEmail) ...[
          const SizedBox(height: 4),
          OutlinedButton.icon(
            icon: const Icon(Icons.mail_outline_rounded),
            label: Text(l10n.getCodeByEmail),
            onPressed: _codeByEmail,
          ),
        ],
        const SizedBox(height: 20),
        AuthPrimaryButton(
          label: isSignIn ? l10n.verify : l10n.verifyCodeAction,
          loading: _submitting,
          onPressed: () => _verify(),
        ),
        const SizedBox(height: 8),
        Center(
          child: TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: Text(isSignIn ? l10n.wrongNumber : l10n.useDifferentPhoneOrEmail),
          ),
        ),
        const SizedBox(height: 16),
        Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(color: fira.brandTint, borderRadius: BorderRadius.circular(16)),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(Icons.shield_outlined, color: fira.brandPrimary, size: 20),
              const SizedBox(width: 10),
              Expanded(
                child: Text(l10n.neverShareCode, style: textTheme.bodySmall?.copyWith(color: fira.onSurface)),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
