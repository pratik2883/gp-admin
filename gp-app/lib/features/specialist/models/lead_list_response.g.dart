// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'lead_list_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_$LeadListResponseImpl _$$LeadListResponseImplFromJson(
        Map<String, dynamic> json) =>
    _$LeadListResponseImpl(
      data: (json['data'] as List<dynamic>)
          .map((e) => Lead.fromJson(e as Map<String, dynamic>))
          .toList(),
      current_page: (json['current_page'] as num?)?.toInt(),
      last_page: (json['last_page'] as num?)?.toInt(),
      total: (json['total'] as num?)?.toInt(),
    );

Map<String, dynamic> _$$LeadListResponseImplToJson(
        _$LeadListResponseImpl instance) =>
    <String, dynamic>{
      'data': instance.data,
      'current_page': instance.current_page,
      'last_page': instance.last_page,
      'total': instance.total,
    };
