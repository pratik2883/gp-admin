// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'lead.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_$LeadImpl _$$LeadImplFromJson(Map<String, dynamic> json) => _$LeadImpl(
      id: (json['id'] as num).toInt(),
      lead_code: json['lead_code'] as String?,
      status: json['status'] as String?,
      gp_name: json['gp_name'] as String?,
      patient_name: json['patient_name'] as String?,
      patient_mobile: json['patient_mobile'] as String?,
      patient_age: (json['patient_age'] as num?)?.toInt(),
      patient_gender: json['patient_gender'] as String?,
      notes: json['notes'] as String?,
      hospital_name: json['hospital_name'] as String?,
      attachments: (json['attachments'] as List<dynamic>?)
              ?.map((e) => LeadAttachment.fromJson(e as Map<String, dynamic>))
              .toList() ??
          const [],
      created_at: json['created_at'] == null
          ? null
          : DateTime.parse(json['created_at'] as String),
    );

Map<String, dynamic> _$$LeadImplToJson(_$LeadImpl instance) =>
    <String, dynamic>{
      'id': instance.id,
      'lead_code': instance.lead_code,
      'status': instance.status,
      'gp_name': instance.gp_name,
      'patient_name': instance.patient_name,
      'patient_mobile': instance.patient_mobile,
      'patient_age': instance.patient_age,
      'patient_gender': instance.patient_gender,
      'notes': instance.notes,
      'hospital_name': instance.hospital_name,
      'attachments': instance.attachments,
      'created_at': instance.created_at?.toIso8601String(),
    };

_$LeadAttachmentImpl _$$LeadAttachmentImplFromJson(Map<String, dynamic> json) =>
    _$LeadAttachmentImpl(
      id: (json['id'] as num).toInt(),
      name: json['name'] as String?,
      url: json['url'] as String?,
    );

Map<String, dynamic> _$$LeadAttachmentImplToJson(
        _$LeadAttachmentImpl instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'url': instance.url,
    };
