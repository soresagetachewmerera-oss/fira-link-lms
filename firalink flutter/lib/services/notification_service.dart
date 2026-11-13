import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/notification.dart';
import 'base_service.dart';

class NotificationService extends BaseService {
  Future<NotificationResponse> fetchNotifications({int page = 1}) async {
    final response = await http.get(
      Uri.parse('${BaseService.baseUrl}/notifications?page=$page'),
      headers: await getHeaders(),
    );

    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      return NotificationResponse.fromJson(data);
    } else {
      throw Exception('Failed to load notifications');
    }
  }

  Future<void> markAsRead(String id) async {
    final response = await http.post(
      Uri.parse('${BaseService.baseUrl}/notifications/$id/read'),
      headers: await getHeaders(),
    );

    if (response.statusCode != 200) {
      throw Exception('Failed to mark notification as read');
    }
  }

  Future<void> markAllRead() async {
    final response = await http.post(
      Uri.parse('${BaseService.baseUrl}/notifications/read-all'),
      headers: await getHeaders(),
    );

    if (response.statusCode != 200) {
      throw Exception('Failed to mark all notifications as read');
    }
  }

  Future<void> deleteNotification(String id) async {
    final response = await http.delete(
      Uri.parse('${BaseService.baseUrl}/notifications/$id'),
      headers: await getHeaders(),
    );

    if (response.statusCode != 200) {
      throw Exception('Failed to delete notification');
    }
  }

  Future<Map<String, dynamic>> getNotificationSettings() async {
    final response = await http.get(
      Uri.parse('${BaseService.baseUrl}/user/settings/notifications'),
      headers: await getHeaders(),
    );

    if (response.statusCode == 200) {
      final jsonResponse = json.decode(response.body);
      return Map<String, dynamic>.from(jsonResponse['data']);
    } else {
      throw Exception('Failed to load notification settings');
    }
  }

  Future<void> updateNotificationSettings(Map<String, bool> settings) async {
    final response = await http.put(
      Uri.parse('${BaseService.baseUrl}/user/settings/notifications'),
      headers: await getHeaders(),
      body: json.encode(settings),
    );

    if (response.statusCode != 200) {
      throw Exception('Failed to update notification settings');
    }
  }

  /// Register FCM push token for this device with the Laravel backend
  Future<bool> registerDeviceToken(String fcmToken, {String deviceType = 'android', String? deviceName}) async {
    try {
      final response = await http.post(
        Uri.parse('${BaseService.baseUrl}/user/device-token'),
        headers: await getHeaders(),
        body: json.encode({
          'fcm_token': fcmToken,
          'device_type': deviceType,
          if (deviceName != null) 'device_name': deviceName,
        }),
      );
      return response.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  /// Unregister FCM push token on user logout
  Future<bool> unregisterDeviceToken(String fcmToken) async {
    try {
      final response = await http.delete(
        Uri.parse('${BaseService.baseUrl}/user/device-token'),
        headers: await getHeaders(),
        body: json.encode({
          'fcm_token': fcmToken,
        }),
      );
      return response.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  /// Trigger a test push notification to verify device connectivity
  Future<Map<String, dynamic>> sendTestPush({String? title, String? body}) async {
    final response = await http.post(
      Uri.parse('${BaseService.baseUrl}/notifications/test-push'),
      headers: await getHeaders(),
      body: json.encode({
        if (title != null) 'title': title,
        if (body != null) 'body': body,
      }),
    );

    if (response.statusCode == 200) {
      return json.decode(response.body);
    } else {
      throw Exception('Failed to send test push: ${response.body}');
    }
  }
}
