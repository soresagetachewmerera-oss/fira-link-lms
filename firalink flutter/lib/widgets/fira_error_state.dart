import 'package:flutter/material.dart';

import '../l10n/app_localizations.dart';
import '../services/api_exception.dart';
import '../theme/fira_colors.dart';

/// Localized text for a failed request. Used by [FiraErrorState] for loads and
/// by screens for snackbars after a failed action.
class FiraErrorText {
  const FiraErrorText._();

  static String title(BuildContext context, Object? error) {
    final l10n = AppLocalizations.of(context);
    if (error is ApiException && error.isNetwork) return l10n.connectionProblemTitle;
    return l10n.loadFailedTitle;
  }

  /// Message for a failed load or action.
  static String message(BuildContext context, Object? error, {bool isAction = false}) {
    final l10n = AppLocalizations.of(context);
    if (error is ApiException) {
      if (error.isNetwork) return l10n.connectionProblemBody;
      if (error.isUnauthenticated) return l10n.sessionExpired;
      if (error.isFileRejected) return fileRejection(context, error.reason);
      if (error.code == 'CONFIRMATION_REQUIRED') return l10n.milestoneConfirmationRequired;
      if (error.code == 'FRESH_AUTH_REQUIRED') return l10n.confirmItsYouBody;
      if (error.code == 'CHAPA_OFF') return l10n.chapaPaymentsOff;
      if (error.isForbidden) return l10n.notAllowed;
    }
    return isAction ? l10n.actionFailed : l10n.loadFailedBody;
  }

  /// Message for a file the client or server rejected, by reason code.
  static String fileRejection(BuildContext context, String? reason) {
    final l10n = AppLocalizations.of(context);
    switch (reason) {
      case 'empty':
        return l10n.fileRejectedEmpty;
      case 'too_large':
        return l10n.fileRejectedTooLarge;
      case 'too_small':
        return l10n.fileRejectedTooSmall;
      case 'bad_type':
        return l10n.fileRejectedType;
      case 'placeholder':
        return l10n.fileRejectedPlaceholder;
      default:
        return l10n.fileRejectedUnreadable;
    }
  }
}

/// Full-area error state with a retry button, for screens whose data failed to load.
/// Built only from existing theme text styles and [FiraColors] tokens.
class FiraErrorState extends StatelessWidget {
  const FiraErrorState({super.key, required this.error, required this.onRetry});

  final Object? error;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final theme = Theme.of(context);
    final isNetwork = error is ApiException && (error as ApiException).isNetwork;

    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 64,
              height: 64,
              decoration: BoxDecoration(color: fira.brandTint, shape: BoxShape.circle),
              child: Icon(
                isNetwork ? Icons.wifi_off_rounded : Icons.error_outline_rounded,
                color: fira.brandPrimary,
                size: 30,
              ),
            ),
            const SizedBox(height: 16),
            Text(
              FiraErrorText.title(context, error),
              textAlign: TextAlign.center,
              style: theme.textTheme.titleMedium?.copyWith(color: fira.onSurface),
            ),
            const SizedBox(height: 6),
            Text(
              FiraErrorText.message(context, error),
              textAlign: TextAlign.center,
              style: theme.textTheme.bodyMedium?.copyWith(color: fira.mutedText),
            ),
            const SizedBox(height: 20),
            FilledButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh_rounded),
              label: Text(AppLocalizations.of(context).retry),
            ),
          ],
        ),
      ),
    );
  }
}

/// Shows a localized snackbar for a failed action (submit, upload, save).
void showFiraActionError(BuildContext context, Object error) {
  ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(content: Text(FiraErrorText.message(context, error, isAction: true))),
  );
}
