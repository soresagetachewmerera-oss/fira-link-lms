import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:hugeicons/hugeicons.dart';
import '../../l10n/app_localizations.dart';
import '../../services/auth_service.dart';
import '../../theme/app_theme.dart';
import '../../widgets/auth/auth_messages.dart';
import '../../widgets/fira_error_state.dart';
import 'auth_screen.dart';
import 'phone_code_screen.dart';

/// Forgot password by phone or email. The server answers the same way whether or not the
/// account exists, and this screen says so.
class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({super.key, this.initialMessage, this.authService});

  /// Shown on arrival, e.g. "choose a new password before signing in".
  final String? initialMessage;
  final AuthService? authService;

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  @override
  void initState() {
    super.initState();
    if (widget.initialMessage != null) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) AuthMessages.show(context, widget.initialMessage!);
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final Size size = MediaQuery.of(context).size;
    return Scaffold(
      body: Stack(
        children: <Widget>[
          _BackgroundDecoration(size: size),
          SafeArea(
            child: SingleChildScrollView(
              child: Column(
                children: <Widget>[
                  const SizedBox(height: 80),
                  _HeaderSection(),
                  const SizedBox(height: 40),
                  _ForgotPasswordCard(authService: widget.authService ?? AuthService()),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _BackgroundDecoration extends StatelessWidget {
  const _BackgroundDecoration({required this.size});

  final Size size;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: <Color>[
            AppTheme.getBackgroundColor(context),
            AppTheme.getCardColor(context),
          ],
          stops: const <double>[0.0, 0.5],
        ),
      ),
    );
  }
}

class _HeaderSection extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    final bool isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final String logoPath = isDarkMode ? 'assets/img/logo_white.svg' : 'assets/img/logo.svg';

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 24.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: <Widget>[
          SvgPicture.asset(logoPath, width: 142, height: 44),
        ],
      ),
    );
  }
}

class _ForgotPasswordCard extends StatefulWidget {
  const _ForgotPasswordCard({required this.authService});

  final AuthService authService;

  @override
  State<_ForgotPasswordCard> createState() => _ForgotPasswordCardState();
}

class _ForgotPasswordCardState extends State<_ForgotPasswordCard> {
  final TextEditingController _valueController = TextEditingController();
  String _channel = 'phone';
  bool _sending = false;
  String? _fieldError;

  static final _ethiopianMobile = RegExp(r'^(?:\+?251|0)?([79]\d{8})$');
  static final _email = RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$');

  @override
  void dispose() {
    _valueController.dispose();
    super.dispose();
  }

  Future<void> _send() async {
    final l10n = context.l10n;
    final trimmed = _valueController.text.trim();
    final value = _channel == 'phone' ? trimmed.replaceAll(RegExp(r'[\s\-()]'), '') : trimmed;

    final valid = _channel == 'phone' ? _ethiopianMobile.hasMatch(value) : _email.hasMatch(value);
    if (!valid) {
      setState(() => _fieldError = _channel == 'phone' ? l10n.invalidPhone : l10n.invalidEmail);
      return;
    }
    setState(() {
      _fieldError = null;
      _sending = true;
    });

    try {
      final res = await widget.authService.passwordForgot(channel: _channel, value: value);
      if (!mounted) return;
      final body = authBody(res);
      if (res.statusCode != 200) {
        AuthMessages.showResponse(context, res);
        return;
      }
      // Same message whether or not the account exists.
      AuthMessages.show(context, l10n.resetCodeSentNotice);
      Navigator.of(context).push(MaterialPageRoute(
        builder: (_) => PhoneCodeScreen.reset(
          channel: _channel,
          value: value,
          cooldownSeconds: (body['cooldown_seconds'] as num?)?.toInt() ?? 60,
          expiryMinutes: (body['expiry_minutes'] as num?)?.toInt() ?? 10,
          authService: widget.authService,
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
    final isPhone = _channel == 'phone';

    return Container(
      decoration: BoxDecoration(
        color: AppTheme.getCardColor(context),
        borderRadius: const BorderRadius.only(
          topLeft: Radius.circular(32),
          topRight: Radius.circular(32),
        ),
        boxShadow: <BoxShadow>[
          BoxShadow(
            color: AppTheme.getTextColor(context).withValues(alpha: 0.08),
            blurRadius: 20,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(24.0, 32.0, 24.0, 40.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            IconButton(
              icon: HugeIcon(
                icon: HugeIcons.strokeRoundedArrowLeft01,
                size: 20,
                color: AppTheme.getTextColor(context),
              ),
              onPressed: () => Navigator.of(context).pop(),
              padding: EdgeInsets.zero,
              alignment: Alignment.centerLeft,
            ),
            const SizedBox(height: 16),
            Text(
              l10n.forgotTitle,
              style: TextStyle(
                color: AppTheme.getTextColor(context),
                fontSize: 28,
                fontWeight: FontWeight.w700,
                letterSpacing: 0.2,
              ),
            ),
            const SizedBox(height: 12),
            Text(
              l10n.forgotSubtitle,
              style: TextStyle(
                color: AppTheme.getTextColor(context).withValues(alpha: 0.7),
                fontSize: 14,
                fontWeight: FontWeight.w400,
                height: 1.5,
              ),
            ),
            const SizedBox(height: 24),
            SegmentedButton<String>(
              segments: [
                ButtonSegment(value: 'phone', label: Text(l10n.phoneTab), icon: const Icon(Icons.phone_iphone_rounded)),
                ButtonSegment(value: 'email', label: Text(l10n.email), icon: const Icon(Icons.mail_outline_rounded)),
              ],
              selected: {_channel},
              onSelectionChanged: (s) => setState(() {
                _channel = s.first;
                _fieldError = null;
                _valueController.clear();
              }),
            ),
            const SizedBox(height: 20),
            TextField(
              controller: _valueController,
              keyboardType: isPhone ? TextInputType.phone : TextInputType.emailAddress,
              autofillHints: [isPhone ? AutofillHints.telephoneNumber : AutofillHints.email],
              decoration: InputDecoration(
                labelText: isPhone ? l10n.phoneNumberLabel : l10n.email,
                hintText: isPhone ? '+251 9XX XXX XXX' : 'name@company.com',
                errorText: _fieldError,
                filled: true,
                fillColor: Theme.of(context).brightness == Brightness.dark
                    ? AppTheme.surfaceDark.withValues(alpha: 0.5)
                    : Colors.grey[50],
                labelStyle: TextStyle(
                  color: AppTheme.getTextColor(context),
                  fontSize: 14,
                  fontWeight: FontWeight.w500,
                ),
                hintStyle: TextStyle(
                  color: AppTheme.getTextColor(context).withValues(alpha: 0.5),
                  fontSize: 16,
                ),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide(
                    color: AppTheme.getTextColor(context).withValues(alpha: 0.2),
                    width: 1,
                  ),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide(
                    color: AppTheme.getTextColor(context),
                    width: 2,
                  ),
                ),
                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
              ),
              style: TextStyle(color: AppTheme.getTextColor(context), fontSize: 16),
              onSubmitted: (_) => _send(),
            ),
            const SizedBox(height: 32),
            SizedBox(
              width: double.infinity,
              height: 56,
              child: ElevatedButton(
                onPressed: _sending ? null : _send,
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppTheme.getTextColor(context),
                  foregroundColor: AppTheme.getCardColor(context),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  elevation: 0,
                ),
                child: _sending
                    ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2))
                    : Text(
                        l10n.sendCode,
                        style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600, letterSpacing: 0.3),
                      ),
              ),
            ),
            const SizedBox(height: 16),
            Center(
              child: TextButton(
                onPressed: () {
                  Navigator.of(context).pushReplacement(
                    MaterialPageRoute(builder: (context) => const AuthScreen()),
                  );
                },
                style: TextButton.styleFrom(
                  padding: EdgeInsets.zero,
                  minimumSize: Size.zero,
                  tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                ),
                child: Text(
                  l10n.backToSignIn,
                  style: TextStyle(
                    color: AppTheme.getTextColor(context).withValues(alpha: 0.7),
                    fontSize: 14,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
            ),
            const SizedBox(height: 16),
            Text(
              l10n.resetCodeSentNotice,
              style: TextStyle(
                color: AppTheme.getTextColor(context).withValues(alpha: 0.6),
                fontSize: 12,
                height: 1.5,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
