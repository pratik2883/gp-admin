import 'package:freezed_annotation/freezed_annotation.dart';
import 'package:gp_app/features/notifications/models/app_notification.dart';

part 'notification_list_response.freezed.dart';
part 'notification_list_response.g.dart';

@freezed
class NotificationListResponse with _$NotificationListResponse {
  const factory NotificationListResponse({
    @Default([]) List<AppNotification> data,
    @Default(0) int total,
    @JsonKey(name: 'current_page') @Default(1) int currentPage,
    @JsonKey(name: 'last_page') @Default(1) int lastPage,
  }) = _NotificationListResponse;

  factory NotificationListResponse.fromJson(Map<String, dynamic> json) => _$NotificationListResponseFromJson(json);
}
