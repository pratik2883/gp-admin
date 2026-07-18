import 'dart:convert';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:gp_app/app/router.dart';
import 'package:gp_app/features/notifications/state/unread_count_controller.dart';
import 'package:gp_app/app/role.dart';

@pragma('vm:entry-point')
Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  // Handled inherently by platform.
}

final fcmServiceProvider = Provider<FCMService>((ref) {
  return FCMService(ref);
});

class FCMService {
  final Ref _ref;
  final FlutterLocalNotificationsPlugin _localNotificationsPlugin =
      FlutterLocalNotificationsPlugin();
  bool _initialized = false;

  FCMService(this._ref);

  Future<void> initialize() async {
    if (_initialized) return;
    _initialized = true;
    FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);

    await FirebaseMessaging.instance.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );

    const AndroidInitializationSettings initializationSettingsAndroid =
        AndroidInitializationSettings('@mipmap/launcher_icon');
    const InitializationSettings initializationSettings =
        InitializationSettings(android: initializationSettingsAndroid);

    await _localNotificationsPlugin.initialize(initializationSettings,
        onDidReceiveNotificationResponse: (details) {
      _handlePayloadString(details.payload);
    });

    const channel = AndroidNotificationChannel(
      'gp_high_importance_channel',
      'High Importance Notifications',
      importance: Importance.max,
    );
    await _localNotificationsPlugin
        .resolvePlatformSpecificImplementation<
            AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(channel);

    FirebaseMessaging.onMessage.listen((RemoteMessage message) {
      RemoteNotification? notification = message.notification;
      AndroidNotification? android = message.notification?.android;

      _ref.read(unreadCountProvider.notifier).increment();

      if (notification != null && android != null) {
        _localNotificationsPlugin.show(
          notification.hashCode,
          notification.title,
          notification.body,
          const NotificationDetails(
            android: AndroidNotificationDetails(
              'gp_high_importance_channel',
              'High Importance Notifications',
              importance: Importance.max,
              priority: Priority.high,
            ),
          ),
          payload: jsonEncode(message.data),
        );
      }
    });

    FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
      _handlePayloadData(message.data);
    });

    FirebaseMessaging.instance
        .getInitialMessage()
        .then((RemoteMessage? message) {
      if (message != null) {
        Future.delayed(const Duration(seconds: 1), () {
          _handlePayloadData(message.data);
        });
      }
    });
  }

  void _handlePayloadString(String? payload) {
    if (payload == null) return;
    try {
      final data = jsonDecode(payload) as Map<String, dynamic>;
      _handlePayloadData(data);
    } catch (_) {
      // Fallback if primitive string
      _handlePayloadData({'target_id': payload});
    }
  }

  void _handlePayloadData(Map<String, dynamic> data) {
    final targetId = data['target_id']?.toString() ?? data['id']?.toString();
    final payloadRole = data['role']?.toString();
    final payloadType = data['type']?.toString();

    if (targetId == null) return;

    final router = _ref.read(routerProvider);

    if (payloadType == 'support_ticket_reply' ||
        payloadType == 'support_ticket_closed') {
      router.push('/support/$targetId');
      return;
    }

    // Route based on explicit role in payload, fallback to active AppRole
    final activeRole =
        _ref.read(selectedRoleProvider)?.name; // 'gp' or 'specialist'
    final routeRole = payloadRole ?? activeRole ?? 'gp';

    if (routeRole == 'gp') {
      router.push('/gp/referrals/$targetId');
    } else if (routeRole == 'specialist') {
      router.push('/sp/leads/$targetId');
    }
  }
}
