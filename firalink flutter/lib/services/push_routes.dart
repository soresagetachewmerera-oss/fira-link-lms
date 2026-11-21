import 'package:flutter/widgets.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

import '../l10n/lang_am.dart';
import '../l10n/lang_en.dart';
import '../router/app_router.dart';
import '../theme/fira_colors.dart';

/// Where a push or inbox tap goes (messaging slice 4).
///
/// Catalogue messages carry `route` (an AppRouter route) and `route_id`. Only routes the app knows
/// are opened, and every route except the public ones goes through `RequireSignIn`: a signed-out
/// person sees "Sign in to continue" and lands on the target after signing in. Nothing private
/// opens without a session.
class PushTarget {
  const PushTarget(this.route, this.argument);

  final String route;
  final String? argument;

  /// The target for a catalogue payload, or null (older payloads use their own handling).
  static PushTarget? fromData(Map<String, dynamic> data) {
    final route = data['route']?.toString();
    if (route == null || !AppRouter.isKnownRoute(route)) return null;
    final id = data['route_id']?.toString();

    return PushTarget(route, (id == null || id.isEmpty) ? null : id);
  }
}

/// Android notification channels: one per kind of message, so people can mute "Updates" and keep
/// "Security". Names follow the app language; the small icon is tinted brand purple.
class PushChannels {
  PushChannels._();

  static const ids = ['security', 'deals', 'learning', 'updates'];

  /// Brand purple (FiraColors.light.brandPrimary) for the small icon and accent.
  static Color get color => FiraColors.light.brandPrimary;

  static String _t(String languageCode, String key) =>
      (languageCode == 'am' ? kLangAm[key] : null) ?? kLangEn[key] ?? key;

  static String _cap(String id) => id[0].toUpperCase() + id.substring(1);

  static AndroidNotificationChannel channel(String id, String languageCode) {
    final safe = ids.contains(id) ? id : 'updates';
    return AndroidNotificationChannel(
      safe,
      _t(languageCode, 'pushChannel${_cap(safe)}'),
      description: _t(languageCode, 'pushChannel${_cap(safe)}Body'),
      importance: safe == 'learning' || safe == 'updates' ? Importance.defaultImportance : Importance.high,
      playSound: true,
      enableVibration: safe != 'updates',
    );
  }

  static List<AndroidNotificationChannel> all(String languageCode) => [for (final id in ids) channel(id, languageCode)];

  /// The channel a payload asks for; older payloads (no channel) count as updates.
  static AndroidNotificationChannel forData(Map<String, dynamic> data, String languageCode) =>
      channel(data['android_channel']?.toString() ?? 'updates', languageCode);
}
