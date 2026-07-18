// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'notification_list_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_$NotificationListResponseImpl _$$NotificationListResponseImplFromJson(
        Map<String, dynamic> json) =>
    _$NotificationListResponseImpl(
      data: (json['data'] as List<dynamic>?)
              ?.map((e) => AppNotification.fromJson(e as Map<String, dynamic>))
              .toList() ??
          const [],
      total: (json['total'] as num?)?.toInt() ?? 0,
      currentPage: (json['current_page'] as num?)?.toInt() ?? 1,
      lastPage: (json['last_page'] as num?)?.toInt() ?? 1,
    );

Map<String, dynamic> _$$NotificationListResponseImplToJson(
        _$NotificationListResponseImpl instance) =>
    <String, dynamic>{
      'data': instance.data,
      'total': instance.total,
      'current_page': instance.currentPage,
      'last_page': instance.lastPage,
    };
