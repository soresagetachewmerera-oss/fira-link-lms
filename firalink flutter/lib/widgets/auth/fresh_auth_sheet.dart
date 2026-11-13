import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

import '../../l10n/app_localizations.dart';
import '../../services/auth_service.dart';
import '../../theme/fira_colors.dart';
import '../fira_error_state.dart';
import 'auth_messages.dart';
import 'code_input.dart';

/// True when the server refused a sensitive action until the user confirms it is them.
bool needsFreshAuth(http.Response res) => res.statusCode == 403 && authBody(res)['code'] == 'FRESH_AUTH_REQUIRED';

/// Bottom sheet for sensitive actions (verification documents, bank details, password or
/// phone changes): a 6-digit code to the verified phone/email, or the password.
/// Returns true once the server has marked this session fresh (10 minutes).
class FreshAuthSheet extends StatefulWidget {
  const FreshAuthSheet({super.key, this.authService});

  final AuthService? authService;

  static Future<bool> confirm(BuildContext context, {AuthService? authService}) async {
    final ok = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (_) => FreshAuthSheet(authService: authService),
    );
    return ok ?? false;
  }

  /// Runs [action]; if the server asks for fresh authentication, shows the sheet and retries once.
  static Future<http.Response> run(BuildContext context, Future<http.Response> Function() action) async {
    final res = await action();
    if (!needsFreshAuth(res) || !context.mounted) return res;
    final confirmed = await confirm(context);
    return confirmed ? action() : res;
  }

  @override
  State<FreshAuthSheet> createState() => _FreshAuthSheetState();
}

class _FreshAuthSheetState extends State<FreshAuthSheet> {
  late final AuthService _auth = widget.authService ?? AuthService();
  final _password = TextEditingController();
  bool _usePassword = false;
  bool _codeSent = false;
  bool _busy = false;
  String _code = '';
  String? _error;

  @override
  void dispose() {
    _password.dispose();
    super.dispose();
  }

  Future<void> _sendCode() async {
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final res = await _auth.stepUpSendCode();
      if (!mounted) return;
      if (res.statusCode == 200) {
        setState(() => _codeSent = true);
      } else if (authBody(res)['code'] == 'NO_CHANNEL') {
        setState(() => _usePassword = true);
      } else {
        setState(() => _error = AuthMessages.forResponse(context, res));
      }
    } catch (e) {
      if (mounted) setState(() => _error = FiraErrorText.message(context, e, isAction: true));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _confirm() async {
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final res = _usePassword
          ? await _auth.stepUpVerify(password: _password.text)
          : await _auth.stepUpVerify(code: _code);
      if (!mounted) return;
      if (res.statusCode == 200) {
        Navigator.of(context).pop(true);
      } else {
        setState(() => _error = context.l10n.codeOrPasswordWrong);
      }
    } catch (e) {
      if (mounted) setState(() => _error = FiraErrorText.message(context, e, isAction: true));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    final textTheme = Theme.of(context).textTheme;

    return Padding(
      padding: EdgeInsets.fromLTRB(20, 0, 20, MediaQuery.of(context).viewInsets.bottom + 24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(l10n.confirmItsYouTitle, style: textTheme.titleLarge?.copyWith(color: fira.onSurface)),
          const SizedBox(height: 6),
          Text(l10n.confirmItsYouBody, style: textTheme.bodyMedium?.copyWith(color: fira.mutedText)),
          const SizedBox(height: 20),
          if (_usePassword)
            TextField(
              controller: _password,
              obscureText: true,
              autofocus: true,
              autofillHints: const [AutofillHints.password],
              decoration: InputDecoration(labelText: l10n.password),
              onSubmitted: (_) => _confirm(),
            )
          else if (_codeSent)
            CodeInput(onChanged: (v) => _code = v, onCompleted: (_) => _confirm(), hasError: _error != null)
          else
            OutlinedButton(onPressed: _busy ? null : _sendCode, child: Text(l10n.sendMeACode)),
          if (_error != null) ...[
            const SizedBox(height: 10),
            Text(_error!, style: textTheme.bodySmall?.copyWith(color: fira.danger)),
          ],
          const SizedBox(height: 16),
          if (_usePassword || _codeSent)
            FilledButton(onPressed: _busy ? null : _confirm, child: Text(l10n.confirmAction)),
          TextButton(
            onPressed: () => setState(() {
              _usePassword = !_usePassword;
              _error = null;
            }),
            child: Text(_usePassword ? l10n.useCodeInstead : l10n.usePasswordInstead),
          ),
        ],
      ),
    );
  }
}
