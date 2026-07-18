import 'package:freezed_annotation/freezed_annotation.dart';

part 'recommended_specialist.freezed.dart';
part 'recommended_specialist.g.dart';

@freezed
class RecommendedSpecialist with _$RecommendedSpecialist {
  const factory RecommendedSpecialist({
    required int id,
    required String name,
    required String speciality,
    @JsonKey(name: 'area_name') required String areaName,
    @JsonKey(name: 'is_premium') @Default(false) bool isPremium,
    @JsonKey(name: 'match_type') String? matchType,
    @JsonKey(name: 'location_id') int? locationId,
    @JsonKey(name: 'category_code') String? categoryCode,
    @JsonKey(name: 'hospital_id') int? hospitalId,
    @JsonKey(name: 'hospital_name') String? hospitalName,
    @JsonKey(name: 'clinic_address') String? clinicAddress,
    @JsonKey(name: 'years_of_experience') int? yearsOfExperience,
    List<String>? languages,
    @JsonKey(name: 'consultation_flags') Map<String, dynamic>? consultationFlags,
    @JsonKey(name: 'is_super_specialist') @Default(false) bool isSuperSpecialist,
    String? department,
    String? role,
  }) = _RecommendedSpecialist;

  factory RecommendedSpecialist.fromJson(Map<String, dynamic> json) => _$RecommendedSpecialistFromJson(json);
}
