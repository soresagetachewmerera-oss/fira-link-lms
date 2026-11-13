import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../l10n/app_localizations.dart';
import '../../providers/auth_provider.dart';
import '../../services/auth_service.dart';
import '../../widgets/auth/auth_messages.dart';
import '../../widgets/auth/auth_navigation.dart';
import '../../widgets/fira_error_state.dart';

/// New phone users: one required field after the code, then Intent.
/// Email and password are optional, added later in Profile.
class FullNameScreen extends StatefulWidget {
  const FullNameScreen({super.key, required this.signupTicket, this.authService});

  final String signupTicket;
  final AuthService? authService;

  @override
  State<FullNameScreen> createState() => _FullNameScreenState();
}

class _FullNameScreenState extends State<FullNameScreen> {
  late final AuthService _auth = widget.authService ?? AuthService();
  final _nameController = TextEditingController();
  bool _saving = false;
  String? _error;

  @override
  void dispose() {
    _nameController.dispose();
    super.dispose();
  }

  Future<void> _continue() async {
    final name = _nameController.text.trim().replaceAll(RegExp(r'\s+'), ' ');
    if (name.length < 2) {
      setState(() => _error = context.l10n.fullNameRequired);
      return;
    }
    setState(() {
      _error = null;
      _saving = true;
    });

    try {
      final res = await _auth.phoneComplete(widget.signupTicket, name);
      if (!mounted) return;
      final body = authBody(res);

      if (res.statusCode == 200) {
        await context.read<AuthProvider>().completeSignIn(body);
        if (!mounted) return;
        goAfterSignIn(context, isNewUser: true);
        return;
      }

      AuthMessages.showResponse(context, res);
      // The ticket is gone: start again from the phone step.
      if (body['code'] == 'TICKET_EXPIRED' || body['code'] == 'PHONE_TAKEN') {
        Navigator.of(context).pop();
      }
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
      title: l10n.fullNameTitle,
      subtitle: l10n.fullNameSubtitle,
      showBack: false,
      children: [
        TextField(
          controller: _nameController,
          autofocus: true,
          textCapitalization: TextCapitalization.words,
          autofillHints: const [AutofillHints.name],
          textInputAction: TextInputAction.done,
          onSubmitted: (_) => _continue(),
          decoration: InputDecoration(labelText: l10n.fullNameLabel, errorText: _error),
        ),
        const SizedBox(height: 24),
        AuthPrimaryButton(label: l10n.continueAction, loading: _saving, onPressed: _continue),
      ],
    );
  }
}
