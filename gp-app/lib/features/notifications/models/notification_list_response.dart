import 'package:freezed_annotation/freezed_annotation.dart';
import 'package:gp_app/features/notifications/models/app_notification.dart';

part 'notification_list_response.freezed.dart';

@freezed
class NotificationListResponse with _$NotificationListResponse {
  const factory NotificationListResponse({
    @Default([]) List<AppNotification> data,
    @Default(0) int total,
    @JsonKey(name: 'current_page') @Default(1) int currentPage,
    @JsonKey(name: 'last_page') @Default(1) int lastPage,
  }) = _NotificationListResponse;

  factory NotificationListResponse.fromJson(Map<String, dynamic> json) {
    final meta = json['meta'] as Map<String, dynamic>?;
    final total = (json['total'] ?? meta?['total'] as num?)?.toInt() ?? 0;
    final currentPage = (json['current_page'] ?? meta?['current_page'] as num?)?.toInt() ?? 1;
    final lastPage = (json['last_page'] ?? meta?['last_page'] as num?)?.toInt() ?? 1;
    final rawData = json['data'] as List<dynamic>? ?? [];
    final items = rawData
        .map((e) => AppNotification.fromJson(e as Map<String, dynamic>))
        .toList();

    return NotificationListResponse(
      data: items,
      total: total,
      currentPage: currentPage,
      lastPage: lastPage,
    );
  }
}
