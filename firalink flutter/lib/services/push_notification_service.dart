import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../firebase_options.dart';
import '../router/app_router.dart';
import 'notification_service.dart';

/// Top-level background notification handler required by Firebase Messaging.
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  try {
    await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);
  } catch (_) {}
}

class PushNotificationService {
  static final PushNotificationService _instance = PushNotificationService._internal();
  factory PushNotificationService() => _instance;
  PushNotificationService._internal();

  final FirebaseMessaging _fcm = FirebaseMessaging.instance;
  final FlutterLocalNotificationsPlugin _localNotifications = FlutterLocalNotificationsPlugin();
  final NotificationService _apiNotificationService = NotificationService();

  static const String _fcmTokenKey = 'cached_fcm_token';
  bool _isInitialized = false;

  /// High importance notification channel for Android heads-up alerts
  static const AndroidNotificationChannel _highPriorityChannel = AndroidNotificationChannel(
    'high_importance_channel',
    'Important Announcements & Live Classes',
    description: 'Delivers real-time notifications for live sessions, assignments, and updates.',
    importance: Importance.high,
    playSound: true,
    enableVibration: true,
  );

  /// Initialize Firebase Messaging & Local Notification plugins
  Future<void> initialize() async {
    if (_isInitialized) return;

    // Supported platforms: Web, Android, iOS
    final bool isMobile = !kIsWeb &&
        (defaultTargetPlatform == TargetPlatform.android ||
            defaultTargetPlatform == TargetPlatform.iOS);

    if (!kIsWeb && !isMobile) {
      return;
    }

    try {
      // 1. Initialize Firebase Core
      await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);

      // 2. Set background messaging handler
      if (isMobile) {
        FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);
      }

      // 3. Request permissions from user
      final NotificationSettings settings = await _fcm.requestPermission(
        alert: true,
        announcement: false,
        badge: true,
        carPlay: false,
        criticalAlert: false,
        provisional: false,
        sound: true,
      );

      if (settings.authorizationStatus == AuthorizationStatus.authorized ||
          settings.authorizationStatus == AuthorizationStatus.provisional) {
        // Setup foreground notifications on mobile via local notification plugin
        if (isMobile) {
          await _setupLocalNotifications();
        }

        // Listen for foreground FCM messages
        FirebaseMessaging.onMessage.listen(_handleForegroundMessage);

        // Listen for notification taps when app is opened from background
        FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
          handleNotificationTap(message.data);
        });

        // Check if app was opened directly from a terminated state via a notification
        final RemoteMessage? initialMessage = await _fcm.getInitialMessage();
        if (initialMessage != null) {
          Future.delayed(const Duration(milliseconds: 500), () {
            handleNotificationTap(initialMessage.data);
          });
        }

        // Listen for FCM token refreshes
        _fcm.onTokenRefresh.listen((String newToken) {
          _syncTokenToServer(newToken);
        });

        _isInitialized = true;
      }
    } catch (e) {
      if (kDebugMode) {
        print('[PushNotificationService] Initialization warning: $e');
      }
    }
  }

  /// Setup flutter_local_notifications plugin and create Android notification channel
  Future<void> _setupLocalNotifications() async {
    const AndroidInitializationSettings androidSettings =
        AndroidInitializationSettings('@mipmap/ic_launcher');

    const DarwinInitializationSettings iosSettings = DarwinInitializationSettings(
      requestAlertPermission: false,
      requestBadgePermission: false,
      requestSoundPermission: false,
    );

    const InitializationSettings initSettings = InitializationSettings(
      android: androidSettings,
      iOS: iosSettings,
    );

    await _localNotifications.initialize(
      settings: initSettings,
      onDidReceiveNotificationResponse: (NotificationResponse response) {
        if (response.payload != null && response.payload!.isNotEmpty) {
          try {
            final Map<String, dynamic> data = jsonDecode(response.payload!);
            handleNotificationTap(data);
          } catch (_) {}
        }
      },
    );

    // Create the high priority notification channel on Android
    final androidPlugin = _localNotifications
        .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>();
    if (androidPlugin != null) {
      await androidPlugin.createNotificationChannel(_highPriorityChannel);
    }

    // Set foreground notification presentation options for iOS
    await _fcm.setForegroundNotificationPresentationOptions(
      alert: true,
      badge: true,
      sound: true,
    );
  }

  /// Display a heads-up banner when a push arrives while app is in foreground
  void _handleForegroundMessage(RemoteMessage message) {
    if (kIsWeb) return;

    final RemoteNotification? notification = message.notification;
    final Map<String, dynamic> data = message.data;

    final String title = notification?.title ?? data['title'] ?? 'Fira Link Academy';
    final String body = notification?.body ?? data['message'] ?? data['body'] ?? '';

    _localNotifications.show(
      id: message.hashCode,
      title: title,
      body: body,
      notificationDetails: NotificationDetails(
        android: AndroidNotificationDetails(
          _highPriorityChannel.id,
          _highPriorityChannel.name,
          channelDescription: _highPriorityChannel.description,
          importance: Importance.high,
          priority: Priority.high,
          icon: '@mipmap/ic_launcher',
          playSound: true,
        ),
        iOS: const DarwinNotificationDetails(
          presentAlert: true,
          presentBadge: true,
          presentSound: true,
        ),
      ),
      payload: jsonEncode(data),
    );
  }

  /// Sync the current device FCM token with the Laravel backend
  Future<void> syncTokenWithServer() async {
    try {
      final bool isMobile = !kIsWeb &&
          (defaultTargetPlatform == TargetPlatform.android ||
              defaultTargetPlatform == TargetPlatform.iOS);

      if (!_isInitialized) return;
      if (!kIsWeb && !isMobile) return;

      final String? token = await _fcm.getToken();
      if (token != null && token.isNotEmpty) {
        await _syncTokenToServer(token);
      }
    } catch (e) {
      if (kDebugMode) {
        print('[PushNotificationService] Failed to retrieve FCM token: $e');
      }
    }
  }

  Future<void> _syncTokenToServer(String token) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final String? lastSaved = prefs.getString(_fcmTokenKey);

      // Avoid unnecessary duplicate network calls if token hasn't changed
      if (lastSaved == token) return;

      final String deviceType = kIsWeb
          ? 'web'
          : (defaultTargetPlatform == TargetPlatform.iOS ? 'ios' : 'android');

      final bool success = await _apiNotificationService.registerDeviceToken(
        token,
        deviceType: deviceType,
      );

      if (success) {
        await prefs.setString(_fcmTokenKey, token);
      }
    } catch (_) {}
  }

  /// Delete device token when user logs out
  Future<void> deleteToken() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final String? token = prefs.getString(_fcmTokenKey);

      if (token != null && token.isNotEmpty) {
        await _apiNotificationService.unregisterDeviceToken(token);
        await prefs.remove(_fcmTokenKey);
      }

      await _fcm.deleteToken();
    } catch (_) {}
  }

  /// Handle notification tap & deep link routing
  void handleNotificationTap(Map<String, dynamic> data) {
    final navigator = AppRouter.navigatorKey.currentState;
    if (navigator == null) return;

    final String? type = data['type']?.toString();
    final String? courseId = data['course_id']?.toString();
    final String? liveClassId = data['live_class_id']?.toString();

    if (type == 'live_class' || liveClassId != null) {
      navigator.pushNamed(AppRouter.notifications);
    } else if (type == 'course' && courseId != null) {
      navigator.pushNamed(AppRouter.myCourses);
    } else {
      navigator.pushNamed(AppRouter.notifications);
    }
  }
}
