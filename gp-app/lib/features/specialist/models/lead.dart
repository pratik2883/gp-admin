import 'package:freezed_annotation/freezed_annotation.dart';

part 'lead.freezed.dart';
part 'lead.g.dart';

@freezed
class Lead with _$Lead {
  const factory Lead({
    required int id,
    String? lead_code,
    String? status,
    String? gp_name,
    String? patient_name,
    String? patient_mobile,
    int? patient_age,
    String? patient_gender,
    String? notes,
    String? hospital_name,
    @Default([]) List<LeadAttachment> attachments,
    DateTime? created_at,
  }) = _Lead;

  factory Lead.fromJson(Map<String, dynamic> json) => _$LeadFromJson(json);
}

@freezed
class LeadAttachment with _$LeadAttachment {
  const factory LeadAttachment({
    required int id,
    String? name,
    String? url,
  }) = _LeadAttachment;

  factory LeadAttachment.fromJson(Map<String, dynamic> json) => _$LeadAttachmentFromJson(json);
}
