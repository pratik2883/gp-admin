// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recommended_specialist.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_$RecommendedSpecialistImpl _$$RecommendedSpecialistImplFromJson(
        Map<String, dynamic> json) =>
    _$RecommendedSpecialistImpl(
      id: (json['id'] as num).toInt(),
      name: json['name'] as String,
      speciality: json['speciality'] as String,
      areaName: json['area_name'] as String,
      isPremium: json['is_premium'] as bool? ?? false,
      matchType: json['match_type'] as String?,
      locationId: (json['location_id'] as num?)?.toInt(),
      categoryCode: json['category_code'] as String?,
      hospitalId: (json['hospital_id'] as num?)?.toInt(),
      hospitalName: json['hospital_name'] as String?,
      clinicAddress: json['clinic_address'] as String?,
      yearsOfExperience: (json['years_of_experience'] as num?)?.toInt(),
      languages: (json['languages'] as List<dynamic>?)
          ?.map((e) => e as String)
          .toList(),
      consultationFlags: json['consultation_flags'] as Map<String, dynamic>?,
      isSuperSpecialist: json['is_super_specialist'] as bool? ?? false,
      department: json['department'] as String?,
      role: json['role'] as String?,
    );

Map<String, dynamic> _$$RecommendedSpecialistImplToJson(
        _$RecommendedSpecialistImpl instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'speciality': instance.speciality,
      'area_name': instance.areaName,
      'is_premium': instance.isPremium,
      'match_type': instance.matchType,
      'location_id': instance.locationId,
      'category_code': instance.categoryCode,
      'hospital_id': instance.hospitalId,
      'hospital_name': instance.hospitalName,
      'clinic_address': instance.clinicAddress,
      'years_of_experience': instance.yearsOfExperience,
      'languages': instance.languages,
      'consultation_flags': instance.consultationFlags,
      'is_super_specialist': instance.isSuperSpecialist,
      'department': instance.department,
      'role': instance.role,
    };
