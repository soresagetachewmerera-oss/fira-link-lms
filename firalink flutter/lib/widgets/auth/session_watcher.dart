import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../l10n/app_localizations.dart';
import '../../providers/ai_chat_provider.dart';
import '../../providers/ai_suggestions_provider.dart';
import '../../providers/auth_provider.dart';
import '../../providers/chat_provider.dart';
import '../../providers/coffee_toolkit_provider.dart';
import '../../providers/course_discussion_provider.dart';
import '../../providers/dashboard_provider.dart';
import '../../providers/wishlist_provider.dart';
import '../../providers/workspace_provider.dart';

/// Keeps the in-memory app state in line with [AuthProvider] (auth gating 2026-11-20, M-4/M-5).
///
/// - A session ended or a new one started ([AuthProvider.sessionEpoch] changed): every provider
///   that holds personal data forgets it.
/// - Signed in: the workspaces of the new person are loaded.
/// - The server rejected the token (expired): back to Home with an empty stack, and a message.
class SessionWatcher extends StatefulWidget {
  const SessionWatcher({super.key, required this.child, required this.navigatorKey, required this.messengerKey});

  final Widget child;
  final GlobalKey<NavigatorState> navigatorKey;
  final GlobalKey<ScaffoldMessengerState> messengerKey;

  /// Route to show after the session expired.
  static String homeRoute = '/home';

  @override
  State<SessionWatcher> createState() => _SessionWatcherState();
}

class _SessionWatcherState extends State<SessionWatcher> {
  AuthProvider? _auth;
  int _epoch = 0;
  AuthStatus _status = AuthStatus.unknown;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final auth = context.read<AuthProvider>();
    if (!identical(auth, _auth)) {
      _auth?.removeListener(_onAuthChanged);
      _auth = auth..addListener(_onAuthChanged);
      _epoch = auth.sessionEpoch;
      _status = auth.status;
    }
  }

  @override
  void dispose() {
    _auth?.removeListener(_onAuthChanged);
    super.dispose();
  }

  void _onAuthChanged() {
    final auth = _auth;
    if (auth == null || !mounted) return;
    final previous = _status;
    _status = auth.status;

    if (auth.sessionEpoch != _epoch) {
      _epoch = auth.sessionEpoch;
      _resetProviders();
    }

    if (auth.status == AuthStatus.signedIn && previous != AuthStatus.signedIn) {
      context.read<WorkspaceProvider>().loadWorkspaces();
    }

    if (auth.status == AuthStatus.expired && previous != AuthStatus.expired) {
      widget.navigatorKey.currentState?.pushNamedAndRemoveUntil(SessionWatcher.homeRoute, (_) => false);
      final messenger = widget.messengerKey.currentState;
      final ctx = widget.navigatorKey.currentContext;
      if (messenger != null && ctx != null) {
        messenger
          ..hideCurrentSnackBar()
          ..showSnackBar(SnackBar(content: Text(ctx.l10n.sessionExpired)));
      }
    }
  }

  void _resetProviders() {
    void attempt(void Function() reset) {
      try {
        reset();
      } catch (_) {
        // A provider not in this tree (tests): nothing to reset.
      }
    }

    attempt(() => context.read<WorkspaceProvider>().resetSession());
    attempt(() => context.read<ChatProvider>().resetSession());
    attempt(() => context.read<DashboardProvider>().resetSession());
    attempt(() => context.read<CoffeeToolkitProvider>().resetSession());
    attempt(() => context.read<AiChatProvider>().resetSession());
    attempt(() => context.read<CourseDiscussionProvider>().resetSession());
    attempt(() => context.read<AiSuggestionsProvider>().resetSession());
    attempt(() => context.read<WishlistProvider>().resetSession());
  }

  @override
  Widget build(BuildContext context) => widget.child;
}
