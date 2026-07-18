import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter/foundation.dart';
import 'package:gp_app/core/http/dio_client.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/features/notifications/models/notification_list_response.dart';
import 'package:gp_app/features/notifications/models/notification_preferences.dart';

final notificationRepositoryProvider = Provider<NotificationRepository>((ref) {
  return NotificationRepository(ref.read(dioClientProvider));
});

class NotificationRepository {
  final Dio _dio;
  NotificationRepository(DioClient client) : _dio = client.dio;

  Future<NotificationListResponse> fetchNotifications({int page = 1, int perPage = 20}) async {
    final res = await _dio.get('/api/notifications', queryParameters: {
      'page': page,
      'per_page': perPage,
    });
    return NotificationListResponse.fromJson(res.data as Map<String, dynamic>);
  }

  Future<int> fetchUnreadCount() async {
    final res = await _dio.get('/api/notifications/unread-count');
    final data = res.data as Map<String, dynamic>;
    return (data['count'] as num?)?.toInt() ?? (data['unread_count'] as num?)?.toInt() ?? 0;
  }

  Future<void> markAsRead(String id) async {
    await _dio.post('/api/notifications/$id/mark-read');
  }

  Future<void> markAllAsRead() async {
    await _dio.post('/api/notifications/mark-all-read');
  }

  Future<void> registerDeviceToken(String token) async {
    try {
      await _dio.post('/api/device-tokens', data: {
        'token': token,
        'platform': _platformName(),
      });
    } catch (_) {
      // Fail silently if endpoint is not fully supported yet
    }
  }

  Future<void> unregisterDeviceToken(String token) async {
    try {
      await _dio.delete('/api/device-tokens', data: {'token': token});
    } catch (_) {
      // Fail silently
    }
  }

  Future<NotificationPreferences> fetchPreferences() async {
    final res = await _dio.get('/api/notification-preferences');
    return NotificationPreferences.fromJson(res.data as Map<String, dynamic>);
  }

  Future<NotificationPreferences> savePreferences(NotificationPreferences preferences) async {
    final res = await _dio.put('/api/notification-preferences', data: preferences.toJson());
    return NotificationPreferences.fromJson(res.data as Map<String, dynamic>);
  }

  String _platformName() {
    if (kIsWeb) return 'web';
    switch (defaultTargetPlatform) {
      case TargetPlatform.iOS:
        return 'ios';
      case TargetPlatform.android:
        return 'android';
      default:
        return 'android';
    }
  }
}
