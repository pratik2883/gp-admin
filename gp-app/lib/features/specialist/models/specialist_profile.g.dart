// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'specialist_profile.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_$SpecialistProfileImpl _$$SpecialistProfileImplFromJson(
        Map<String, dynamic> json) =>
    _$SpecialistProfileImpl(
      id: (json['id'] as num?)?.toInt(),
      name: json['name'] as String?,
      email: json['email'] as String?,
      mobile: json['mobile'] as String?,
      speciality: json['speciality'] as String?,
      primarySpecialtyCode: json['primary_specialty_code'] as String?,
      additionalSpecialtyIds:
          (json['additional_specialty_ids'] as List<dynamic>?)
              ?.map((e) => (e as num).toInt())
              .toList(),
      additionalSpecialtyLabels:
          (json['additional_specialty_labels'] as List<dynamic>?)
              ?.map((e) => e as String)
              .toList(),
      hospitalName: json['hospital_name'] as String?,
      clinicStreet: json['clinic_street'] as String?,
      clinicArea: json['clinic_area'] as String?,
      clinicCity: json['clinic_city'] as String?,
      clinicPincode: json['clinic_pincode'] as String?,
      clinicAddress: json['clinic_address'] as String?,
      registrationNo: json['registration_no'] as String?,
      councilName: json['council_name'] as String?,
      qualifications: (json['qualifications'] as List<dynamic>?)
          ?.map((e) => e as String)
          .toList(),
      yearsOfExperience: (json['years_of_experience'] as num?)?.toInt(),
      subSpecialties: json['sub_specialties'] as String?,
      keyProcedures: json['key_procedures'] as String?,
      languages: (json['languages'] as List<dynamic>?)
          ?.map((e) => e as String)
          .toList(),
      consultationInPerson: json['consultation_in_person'] as bool?,
      consultationTeleconsult: json['consultation_teleconsult'] as bool?,
      bio: json['bio'] as String?,
      videos:
          (json['videos'] as List<dynamic>?)?.map((e) => e as String).toList(),
      certificates: (json['certificates'] as List<dynamic>?)
          ?.map((e) => e as String)
          .toList(),
      profilePhoto: json['profile_photo'] as String?,
    );

Map<String, dynamic> _$$SpecialistProfileImplToJson(
        _$SpecialistProfileImpl instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'email': instance.email,
      'mobile': instance.mobile,
      'speciality': instance.speciality,
      'primary_specialty_code': instance.primarySpecialtyCode,
      'additional_specialty_ids': instance.additionalSpecialtyIds,
      'additional_specialty_labels': instance.additionalSpecialtyLabels,
      'hospital_name': instance.hospitalName,
      'clinic_street': instance.clinicStreet,
      'clinic_area': instance.clinicArea,
      'clinic_city': instance.clinicCity,
      'clinic_pincode': instance.clinicPincode,
      'clinic_address': instance.clinicAddress,
      'registration_no': instance.registrationNo,
      'council_name': instance.councilName,
      'qualifications': instance.qualifications,
      'years_of_experience': instance.yearsOfExperience,
      'sub_specialties': instance.subSpecialties,
      'key_procedures': instance.keyProcedures,
      'languages': instance.languages,
      'consultation_in_person': instance.consultationInPerson,
      'consultation_teleconsult': instance.consultationTeleconsult,
      'bio': instance.bio,
      'videos': instance.videos,
      'certificates': instance.certificates,
      'profile_photo': instance.profilePhoto,
    };
