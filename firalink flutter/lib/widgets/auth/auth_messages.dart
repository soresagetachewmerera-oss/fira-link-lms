import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:intl/intl.dart';

import '../../l10n/app_localizations.dart';
import '../../services/api_exception.dart';
import '../fira_error_state.dart';

/// Decoded JSON body of an auth response (empty map if not JSON).
Map<String, dynamic> authBody(http.Response res) {
  try {
    final decoded = jsonDecode(res.body);
    if (decoded is Map<String, dynamic>) return decoded;
  } catch (_) {}
  return const {};
}

/// Localized text for the auth API's error codes. Falls back to the shared error texts.
class AuthMessages {
  const AuthMessages._();

  static String forResponse(BuildContext context, http.Response res) {
    final body = authBody(res);
    final l10n = context.l10n;

    switch (body['code']) {
      case 'INVALID_PHONE':
        return l10n.invalidPhone;
      case 'INVALID_INPUT':
        return l10n.invalidEmail;
      case 'SMS_COUNTRY_UNSUPPORTED':
        return l10n.smsCountryUnsupported;
      case 'SMS_UNAVAILABLE':
        return l10n.smsUnavailable;
      case 'SMS_FAILED':
        return l10n.smsFailed;
      case 'COOLDOWN_ACTIVE':
        return l10n.waitBeforeNewCode;
      case 'TOO_MANY_REQUESTS':
        return l10n.tooManyCodes;
      case 'LOCKED':
        final until = DateTime.tryParse('${body['locked_until']}')?.toLocal();
        final time = until == null ? '' : DateFormat.Hm().format(until);
        return l10n.translate('codeLocked', {'time': time});
      case 'INVALID_CODE':
        final left = body['attempts_remaining'];
        return left is int
            ? l10n.translate('codeInvalidAttempts', {'count': left})
            : l10n.codeNotCorrectOrExpired;
      case 'CODE_EXPIRED':
        return l10n.codeExpired;
      case 'CODE_INVALIDATED':
        return l10n.codeInvalidated;
      case 'PHONE_SHARED':
        return l10n.phoneSharedBlocked;
      case 'PHONE_NOT_VERIFIED':
        return l10n.phoneNotVerifiedOnAccount;
      case 'PHONE_TAKEN':
        return l10n.phoneAlreadyLinked;
      case 'TICKET_EXPIRED':
        return l10n.signupTicketExpired;
      case 'INVALID_CREDENTIALS':
        return l10n.invalidEmailOrPassword;
      case 'PASSWORD_RESET_REQUIRED':
        return l10n.passwordResetRequired;
    }
    return FiraErrorText.message(context, ApiException.fromResponse(res), isAction: true);
  }

  static void show(BuildContext context, String message) {
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(message)));
  }

  static void showResponse(BuildContext context, http.Response res) => show(context, forResponse(context, res));
}
