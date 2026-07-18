// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'specialist_category.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_$SpecialistCategoryImpl _$$SpecialistCategoryImplFromJson(
        Map<String, dynamic> json) =>
    _$SpecialistCategoryImpl(
      id: (json['id'] as num).toInt(),
      name: json['name'] as String,
      isPremium: json['is_premium'] as bool? ?? false,
      code: json['code'] as String?,
    );

Map<String, dynamic> _$$SpecialistCategoryImplToJson(
        _$SpecialistCategoryImpl instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'is_premium': instance.isPremium,
      'code': instance.code,
    };
