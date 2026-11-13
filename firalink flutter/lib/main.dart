import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import 'app.dart';
import 'config/app_providers.dart';
import 'services/push_notification_service.dart';

/// Main entry point of the application
void main() async {
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
