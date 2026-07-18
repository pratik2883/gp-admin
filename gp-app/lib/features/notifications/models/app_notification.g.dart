// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'app_notification.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_$AppNotificationImpl _$$AppNotificationImplFromJson(
        Map<String, dynamic> json) =>
    _$AppNotificationImpl(
      id: json['id'] as String,
      type: json['type'] as String?,
      data: json['data'] as Map<String, dynamic>? ?? const {},
      read_at: json['read_at'] == null
          ? null
          : DateTime.parse(json['read_at'] as String),
      created_at: json['created_at'] == null
          ? null
          : DateTime.parse(json['created_at'] as String),
    );

Map<String, dynamic> _$$AppNotificationImplToJson(
        _$AppNotificationImpl instance) =>
    <String, dynamic>{
      'id': instance.id,
      'type': instance.type,
      'data': instance.data,
      'read_at': instance.read_at?.toIso8601String(),
      'created_at': instance.created_at?.toIso8601String(),
    };
