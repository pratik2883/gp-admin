// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'referral.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_$ReferralImpl _$$ReferralImplFromJson(Map<String, dynamic> json) =>
    _$ReferralImpl(
      id: (json['id'] as num).toInt(),
      lead_code: json['lead_code'] as String?,
      referral_type: json['referral_type'] as String?,
      status: json['status'] as String?,
      gp_id: (json['gp_id'] as num?)?.toInt(),
      specialist_id:
          (_readSpecialistId(json, 'specialist_id') as num?)?.toInt(),
      hospital_id: (_readHospitalId(json, 'hospital_id') as num?)?.toInt(),
      specialist_name: _readSpecialistName(json, 'specialist_name') as String?,
      hospital_name: _readHospitalName(json, 'hospital_name') as String?,
      diagnostic_center_id:
          (_readDiagnosticCenterId(json, 'diagnostic_center_id') as num?)
              ?.toInt(),
      diagnostic_center_name:
          _readDiagnosticCenterName(json, 'diagnostic_center_name') as String?,
      department: json['department'] as String?,
      visit_type: json['appointment_type'] as String?,
      priority: json['priority'] as String?,
      patient_name: json['patient_name'] as String?,
      patient_mobile: json['patient_mobile'] as String?,
      patient_age: (_readPatientAge(json, 'patient_age') as num?)?.toInt(),
      patient_gender: json['patient_gender'] as String?,
      notes: json['case_summary'] as String?,
      created_at: json['created_at'] == null
          ? null
          : DateTime.parse(json['created_at'] as String),
      specialist_speciality:
          _readSpecialistSpeciality(json, 'specialist_speciality') as String?,
      specialist_address:
          _readSpecialistAddress(json, 'specialist_address') as String?,
      specialist_experience:
          (_readSpecialistExperience(json, 'specialist_experience') as num?)
              ?.toInt(),
      hospital_address:
          _readHospitalAddress(json, 'hospital_address') as String?,
      hospital_city: _readHospitalCity(json, 'hospital_city') as String?,
      specialist_details: json['specialist'] as Map<String, dynamic>?,
      hospital_details: json['hospital'] as Map<String, dynamic>?,
      files: (json['files'] as List<dynamic>?)
          ?.map((e) => e as Map<String, dynamic>)
          .toList(),
      accepted_at: json['accepted_at'] == null
          ? null
          : DateTime.parse(json['accepted_at'] as String),
      consulted_at: json['consulted_at'] == null
          ? null
          : DateTime.parse(json['consulted_at'] as String),
      closed_at: json['closed_at'] == null
          ? null
          : DateTime.parse(json['closed_at'] as String),
    );

Map<String, dynamic> _$$ReferralImplToJson(_$ReferralImpl instance) =>
    <String, dynamic>{
      'id': instance.id,
      'lead_code': instance.lead_code,
      'referral_type': instance.referral_type,
      'status': instance.status,
      'gp_id': instance.gp_id,
      'specialist_id': instance.specialist_id,
      'hospital_id': instance.hospital_id,
      'specialist_name': instance.specialist_name,
      'hospital_name': instance.hospital_name,
      'diagnostic_center_id': instance.diagnostic_center_id,
      'diagnostic_center_name': instance.diagnostic_center_name,
      'department': instance.department,
      'appointment_type': instance.visit_type,
      'priority': instance.priority,
      'patient_name': instance.patient_name,
      'patient_mobile': instance.patient_mobile,
      'patient_age': instance.patient_age,
      'patient_gender': instance.patient_gender,
      'case_summary': instance.notes,
      'created_at': instance.created_at?.toIso8601String(),
      'specialist_speciality': instance.specialist_speciality,
      'specialist_address': instance.specialist_address,
      'specialist_experience': instance.specialist_experience,
      'hospital_address': instance.hospital_address,
      'hospital_city': instance.hospital_city,
      'specialist': instance.specialist_details,
      'hospital': instance.hospital_details,
      'files': instance.files,
      'accepted_at': instance.accepted_at?.toIso8601String(),
      'consulted_at': instance.consulted_at?.toIso8601String(),
      'closed_at': instance.closed_at?.toIso8601String(),
    };
