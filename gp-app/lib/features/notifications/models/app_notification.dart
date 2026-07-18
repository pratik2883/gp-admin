import 'package:freezed_annotation/freezed_annotation.dart';

part 'app_notification.freezed.dart';
part 'app_notification.g.dart';

@freezed
class AppNotification with _$AppNotification {
  const AppNotification._();

  const factory AppNotification({
    required String id,
    String? type,
    @Default({}) Map<String, dynamic> data,
    DateTime? read_at,
    DateTime? created_at,
  }) = _AppNotification;

  factory AppNotification.fromJson(Map<String, dynamic> json) => _$AppNotificationFromJson(json);

  String get title => data['title'] as String? ?? 'Notification';
  String get body => data['body'] as String? ?? '';
  String? get targetId => data['target_id']?.toString() ?? data['id']?.toString();
  bool get isRead => read_at != null;
}
