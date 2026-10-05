// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'user.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_$UserImpl _$$UserImplFromJson(Map<String, dynamic> json) => _$UserImpl(
      id: (json['id'] as num).toInt(),
      name: json['name'] as String,
      email: json['email'] as String?,
      mobile: json['mobile'] as String,
      role: json['role'] as String,
      gp_id: (json['gp_id'] as num?)?.toInt(),
      gpStatus: json['gp_status'] as String?,
      specialist_id: (json['specialist_id'] as num?)?.toInt(),
      registrationNumber: json['registration_number'] as String?,
      speciality: json['speciality'] as String?,
      designation: json['designation'] as String?,
      address: json['address'] as String?,
      city: json['city'] as String?,
      clinicName: json['clinic_name'] as String?,
      defaultLocationId: (json['default_location_id'] as num?)?.toInt(),
      defaultLocationName: json['default_location_name'] as String?,
    );

Map<String, dynamic> _$$UserImplToJson(_$UserImpl instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'email': instance.email,
      'mobile': instance.mobile,
      'role': instance.role,
      'gp_id': instance.gp_id,
      'gp_status': instance.gpStatus,
      'specialist_id': instance.specialist_id,
      'registration_number': instance.registrationNumber,
      'speciality': instance.speciality,
      'designation': instance.designation,
      'address': instance.address,
      'city': instance.city,
      'clinic_name': instance.clinicName,
      'default_location_id': instance.defaultLocationId,
      'default_location_name': instance.defaultLocationName,
    };
