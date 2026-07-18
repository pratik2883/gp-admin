// ignore_for_file: non_constant_identifier_names

import 'package:freezed_annotation/freezed_annotation.dart';

part 'referral.freezed.dart';
part 'referral.g.dart';

@freezed
class Referral with _$Referral {
  const factory Referral({
    required int id,
    String? lead_code,
    String? referral_type,
    String? status,
    int? gp_id,
    @JsonKey(readValue: _readSpecialistId) int? specialist_id,
    @JsonKey(readValue: _readHospitalId) int? hospital_id,
    @JsonKey(readValue: _readSpecialistName) String? specialist_name,
    @JsonKey(readValue: _readHospitalName) String? hospital_name,
    @JsonKey(readValue: _readDiagnosticCenterId) int? diagnostic_center_id,
    @JsonKey(readValue: _readDiagnosticCenterName) String? diagnostic_center_name,
    String? department,
    @JsonKey(name: 'appointment_type') String? visit_type,
    String? priority,
    String? patient_name,
    String? patient_mobile,
    int? patient_age,
    String? patient_gender,
    @JsonKey(name: 'case_summary') String? notes,
    DateTime? created_at,
    // Extended specialist details from ReferralResource
    @JsonKey(readValue: _readSpecialistSpeciality) String? specialist_speciality,
    @JsonKey(readValue: _readSpecialistAddress) String? specialist_address,
    @JsonKey(readValue: _readSpecialistExperience) int? specialist_experience,
    @JsonKey(readValue: _readHospitalAddress) String? hospital_address,
    @JsonKey(readValue: _readHospitalCity) String? hospital_city,
    // Specialist nested object (full details)
    @JsonKey(name: 'specialist') Map<String, dynamic>? specialist_details,
    @JsonKey(name: 'hospital') Map<String, dynamic>? hospital_details,
    // Files
    @JsonKey(name: 'files') List<Map<String, dynamic>>? files,
    // Timeline
    DateTime? accepted_at,
    DateTime? consulted_at,
    DateTime? closed_at,
  }) = _Referral;

  factory Referral.fromJson(Map<String, dynamic> json) => _$ReferralFromJson(json);
}

Object? _readSpecialistName(Map<dynamic, dynamic> json, String key) {
  final direct = json[key];
  if (direct != null && '$direct'.trim().isNotEmpty) return direct;
  final specialist = json['specialist'];
  if (specialist is Map && specialist['name'] != null) return specialist['name'];
  return null;
}

Object? _readHospitalName(Map<dynamic, dynamic> json, String key) {
  final direct = json[key];
  if (direct != null && '$direct'.trim().isNotEmpty) return direct;
  final hospital = json['hospital'];
  if (hospital is Map && hospital['name'] != null) return hospital['name'];
  return null;
}

Object? _readSpecialistId(Map<dynamic, dynamic> json, String key) {
  final direct = json[key];
  if (direct != null) return direct;
  final specialist = json['specialist'];
  if (specialist is Map && specialist['id'] != null) return specialist['id'];
  return null;
}

Object? _readHospitalId(Map<dynamic, dynamic> json, String key) {
  final direct = json[key];
  if (direct != null) return direct;
  final hospital = json['hospital'];
  if (hospital is Map && hospital['id'] != null) return hospital['id'];
  return null;
}

Object? _readDiagnosticCenterName(Map<dynamic, dynamic> json, String key) {
  final direct = json[key];
  if (direct != null && '$direct'.trim().isNotEmpty) return direct;
  final center = json['diagnostic_center'];
  if (center is Map && center['name'] != null) return center['name'];
  return null;
}

Object? _readDiagnosticCenterId(Map<dynamic, dynamic> json, String key) {
  final direct = json[key];
  if (direct != null) return direct;
  final center = json['diagnostic_center'];
  if (center is Map && center['id'] != null) return center['id'];
  return null;
}

Object? _readSpecialistSpeciality(Map<dynamic, dynamic> json, String key) {
  final direct = json[key];
  if (direct != null && '$direct'.trim().isNotEmpty) return direct;
  final specialist = json['specialist'];
  if (specialist is Map && specialist['speciality'] != null) return specialist['speciality'];
  return null;
}

Object? _readSpecialistAddress(Map<dynamic, dynamic> json, String key) {
  final direct = json[key];
  if (direct != null && '$direct'.trim().isNotEmpty) return direct;
  final specialist = json['specialist'];
  if (specialist is Map && specialist['clinic_address'] != null) return specialist['clinic_address'];
  return null;
}

Object? _readSpecialistExperience(Map<dynamic, dynamic> json, String key) {
  final direct = json[key];
  if (direct != null) return direct;
  final specialist = json['specialist'];
  if (specialist is Map && specialist['years_of_experience'] != null) return specialist['years_of_experience'];
  return null;
}

Object? _readHospitalAddress(Map<dynamic, dynamic> json, String key) {
  final direct = json[key];
  if (direct != null && '$direct'.trim().isNotEmpty) return direct;
  final hospital = json['hospital'];
  if (hospital is Map && hospital['address'] != null) return hospital['address'];
  return null;
}

Object? _readHospitalCity(Map<dynamic, dynamic> json, String key) {
  final direct = json[key];
  if (direct != null && '$direct'.trim().isNotEmpty) return direct;
  final hospital = json['hospital'];
  if (hospital is Map && hospital['city'] != null) return hospital['city'];
  return null;
}
