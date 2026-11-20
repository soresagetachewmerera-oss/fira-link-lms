import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:http/http.dart' as http;
import 'package:provider/provider.dart';

import 'app.dart';
import 'config/app_providers.dart';
import 'services/fira_http_client.dart';
import 'services/push_notification_service.dart';

/// Main entry point of the application. Everything runs with [FiraHttpClient] as the default HTTP
/// client, so no request sends a token outside a signed-in session and every rejected token signs out.
void main() {
  http.runWithClient(_main, () => FiraHttpClient(http.Client()));
}

Future<void> _main() async {
  WidgetsFlutterBinding.ensureInitialized();

  await SystemChrome.setPreferredOrientations(<DeviceOrientation>[
    DeviceOrientation.portraitUp,
    DeviceOrientation.portraitDown,
  ]);

  FlutterError.onError = (FlutterErrorDetails details) {
    if (kDebugMode) {
      FlutterError.presentError(details);
    }
  };

  PlatformDispatcher.instance.onError = (Object error, StackTrace stack) {
    return true;
  };

  // Initialize push notification system (FCM & local heads-up banners)
  try {
    await PushNotificationService().initialize();
  } catch (_) {}

  runApp(
    MultiProvider(providers: AppProviders.providers, child: const EduExApp()),
  );
}
