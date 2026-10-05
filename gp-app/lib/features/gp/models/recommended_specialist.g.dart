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
      profilePhoto: json['profile_photo'] as String?,
      speciality: json['speciality'] as String,
      areaName: json['area_name'] as String,
      isPremium: json['is_premium'] as bool? ?? false,
      matchType: json['match_type'] as String?,
      locationId: (json['location_id'] as num?)?.toInt(),
      categoryCode: json['category_code'] as String?,
      hospitalId: (json['hospital_id'] as num?)?.toInt(),
      hospitalName: json['hospital_name'] as String?,
      clinicAddress: json['clinic_address'] as String?,
      clinicTimings: json['clinic_timings'] as String?,
      hospitalVisitingHours: json['hospital_visiting_hours'] as String?,
      showMobileNumber: json['show_mobile_number'] as bool? ?? true,
      showWhatsappNumber: json['show_whatsapp_number'] as bool? ?? true,
      mobile: json['mobile'] as String?,
      whatsappNumber: json['whatsapp_number'] as String?,
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
      'profile_photo': instance.profilePhoto,
      'speciality': instance.speciality,
      'area_name': instance.areaName,
      'is_premium': instance.isPremium,
      'match_type': instance.matchType,
      'location_id': instance.locationId,
      'category_code': instance.categoryCode,
      'hospital_id': instance.hospitalId,
      'hospital_name': instance.hospitalName,
      'clinic_address': instance.clinicAddress,
      'clinic_timings': instance.clinicTimings,
      'hospital_visiting_hours': instance.hospitalVisitingHours,
      'show_mobile_number': instance.showMobileNumber,
      'show_whatsapp_number': instance.showWhatsappNumber,
      'mobile': instance.mobile,
      'whatsapp_number': instance.whatsappNumber,
      'years_of_experience': instance.yearsOfExperience,
      'languages': instance.languages,
      'consultation_flags': instance.consultationFlags,
      'is_super_specialist': instance.isSuperSpecialist,
      'department': instance.department,
      'role': instance.role,
    };
