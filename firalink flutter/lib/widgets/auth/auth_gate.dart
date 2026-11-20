import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../l10n/app_localizations.dart';
import '../../providers/auth_provider.dart';
import '../../screens/auth/phone_sign_in_screen.dart';
import '../../theme/fira_colors.dart';

/// Sign-in gates for the whole app (auth gating 2026-11-20). Built from `context.fira` tokens and
/// existing Material components; no new colors, fonts or radii.
///
/// - [ensureSignedIn]: call BEFORE any request that needs an account (save, message, request a
///   sample, post a request, book, enroll). Shows the sign-in sheet to guests and, after sign-in,
///   returns true so the caller continues the interrupted action.
/// - [SignInRequiredView]: what a protected screen shows instead of its content to a guest.
/// - [SessionCheckFailedView]: a stored session that could not be confirmed (offline, server error).

/// Brings the person back to where they were after signing in.
class SignInResume {
  SignInResume._();

  static Route<dynamic>? _returnTo;
  static Completer<bool>? _done;

  /// True while a sign-in started from a gate is in progress.
  static bool get pending => _returnTo != null;

  /// Opens phone sign-in and completes with true once signed in (false if the person goes back).
  static Future<bool> start(BuildContext context) {
    final navigator = Navigator.of(context);
    _returnTo = ModalRoute.of(context);
    final done = Completer<bool>();
    _done = done;
    navigator
        .push(MaterialPageRoute<void>(builder: (_) => const PhoneSignInScreen()))
        .then((_) => _finish(false));
    return done.future;
  }

  /// Called by the sign-in flow when it succeeded. Returns true when it brought the person back to
  /// the screen that asked for sign-in (the caller must not navigate any further).
  static bool tryResume(BuildContext context) {
    final target = _returnTo;
    if (target == null || !target.isActive) {
      _finish(false);
      return false;
    }
    Navigator.of(context).popUntil((route) => route == target);
    _finish(true);
    return true;
  }

  static void _finish(bool signedIn) {
    final done = _done;
    _returnTo = null;
    _done = null;
    if (done != null && !done.isCompleted) done.complete(signedIn);
  }
}

/// True when signed in. Guests see the sign-in sheet first (no request is sent before that);
/// an unconfirmed session shows the connection message. After sign-in, true: continue the action.
Future<bool> ensureSignedIn(BuildContext context) async {
  final auth = context.read<AuthProvider>();
  if (auth.isAuthenticated) return true;

  if (auth.status == AuthStatus.offline || auth.status == AuthStatus.unknown) {
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(context.l10n.sessionCheckFailedBody)));
    return false;
  }

  final wantsSignIn = await SignInPromptSheet.show(context);
  if (wantsSignIn != true || !context.mounted) return false;
  final ok = await SignInResume.start(context);
  return ok && context.mounted && context.read<AuthProvider>().isAuthenticated;
}

/// "Sign in to continue" bottom sheet. Returns true when the person chose to sign in.
class SignInPromptSheet extends StatelessWidget {
  const SignInPromptSheet({super.key});

  static Future<bool?> show(BuildContext context) => showModalBottomSheet<bool>(
        context: context,
        isScrollControlled: true,
        builder: (_) => const SignInPromptSheet(),
      );

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final l10n = context.l10n;
    final textTheme = Theme.of(context).textTheme;

    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Semantics(
              header: true,
              child: Text(l10n.signInToContinueTitle, style: textTheme.titleLarge?.copyWith(color: fira.onSurface)),
            ),
            const SizedBox(height: 8),
            Text(l10n.signInToContinueBody, style: textTheme.bodyMedium?.copyWith(color: fira.mutedText)),
            const SizedBox(height: 20),
            FilledButton(
              key: const Key('signin-prompt-continue'),
              onPressed: () => Navigator.of(context).pop(true),
              child: Text(l10n.signInOrCreateAccount),
            ),
            const SizedBox(height: 8),
            TextButton(
              key: const Key('signin-prompt-not-now'),
              onPressed: () => Navigator.of(context).pop(false),
              child: Text(l10n.notNow),
            ),
          ],
        ),
      ),
    );
  }
}

/// Shown instead of a protected screen's content to a guest (deep link, notification, back stack).
/// Nothing of the protected screen is built, so nothing renders first and hides later.
class SignInRequiredView extends StatelessWidget {
  const SignInRequiredView({super.key});

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final l10n = context.l10n;
    final textTheme = Theme.of(context).textTheme;

    return Scaffold(
      appBar: AppBar(),
      body: SafeArea(
        child: Center(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 64,
                  height: 64,
                  decoration: BoxDecoration(color: fira.brandTint, shape: BoxShape.circle),
                  child: Icon(Icons.lock_outline_rounded, color: fira.brandPrimary, size: 30),
                ),
                const SizedBox(height: 16),
                Semantics(
                  header: true,
                  child: Text(l10n.signInToContinueTitle, textAlign: TextAlign.center,
                      style: textTheme.titleMedium?.copyWith(color: fira.onSurface)),
                ),
                const SizedBox(height: 6),
                Text(l10n.signInToContinueBody, textAlign: TextAlign.center,
                    style: textTheme.bodyMedium?.copyWith(color: fira.mutedText)),
                const SizedBox(height: 20),
                FilledButton(
                  key: const Key('signin-required-continue'),
                  onPressed: () => SignInResume.start(context),
                  child: Text(l10n.signInOrCreateAccount),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

/// A session is stored but could not be confirmed (offline, server error). Keeps the session.
class SessionCheckFailedView extends StatelessWidget {
  const SessionCheckFailedView({super.key, required this.onRetry});

  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final l10n = context.l10n;
    final textTheme = Theme.of(context).textTheme;

    return Scaffold(
      appBar: AppBar(),
      body: SafeArea(
        child: Center(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 64,
                  height: 64,
                  decoration: BoxDecoration(color: fira.brandTint, shape: BoxShape.circle),
                  child: Icon(Icons.wifi_off_rounded, color: fira.brandPrimary, size: 30),
                ),
                const SizedBox(height: 16),
                Text(l10n.connectionProblemTitle, textAlign: TextAlign.center,
                    style: textTheme.titleMedium?.copyWith(color: fira.onSurface)),
                const SizedBox(height: 6),
                Text(l10n.sessionCheckFailedBody, textAlign: TextAlign.center,
                    style: textTheme.bodyMedium?.copyWith(color: fira.mutedText)),
                const SizedBox(height: 20),
                FilledButton.icon(
                  key: const Key('session-check-retry'),
                  onPressed: onRetry,
                  icon: const Icon(Icons.refresh_rounded),
                  label: Text(l10n.retry),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

/// Wraps a protected screen: the content only for a confirmed session.
class RequireSignIn extends StatelessWidget {
  const RequireSignIn({super.key, required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    switch (auth.status) {
      case AuthStatus.signedIn:
        return auth.isAuthenticated ? child : const SignInRequiredView();
      case AuthStatus.unknown:
        return const Scaffold(body: Center(child: CircularProgressIndicator()));
      case AuthStatus.offline:
        return SessionCheckFailedView(onRetry: auth.loadUser);
      case AuthStatus.guest:
      case AuthStatus.expired:
        return const SignInRequiredView();
    }
  }
}
