// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'referral_list_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_$ReferralListResponseImpl _$$ReferralListResponseImplFromJson(
        Map<String, dynamic> json) =>
    _$ReferralListResponseImpl(
      data: (json['data'] as List<dynamic>)
          .map((e) => Referral.fromJson(e as Map<String, dynamic>))
          .toList(),
      current_page: (json['current_page'] as num?)?.toInt(),
      last_page: (json['last_page'] as num?)?.toInt(),
      total: (json['total'] as num?)?.toInt(),
    );

Map<String, dynamic> _$$ReferralListResponseImplToJson(
        _$ReferralListResponseImpl instance) =>
    <String, dynamic>{
      'data': instance.data,
      'current_page': instance.current_page,
      'last_page': instance.last_page,
      'total': instance.total,
    };
