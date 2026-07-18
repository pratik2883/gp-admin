import 'package:freezed_annotation/freezed_annotation.dart';

part 'specialist_profile.freezed.dart';
part 'specialist_profile.g.dart';

@freezed
class SpecialistProfile with _$SpecialistProfile {
  const factory SpecialistProfile({
    int? id,
    String? name,
    String? email,
    String? mobile,
    String? speciality,
    @JsonKey(name: 'primary_specialty_code') String? primarySpecialtyCode,
    @JsonKey(name: 'additional_specialty_ids') List<int>? additionalSpecialtyIds,
    @JsonKey(name: 'additional_specialty_labels') List<String>? additionalSpecialtyLabels,
    @JsonKey(name: 'hospital_name') String? hospitalName,
    @JsonKey(name: 'clinic_street') String? clinicStreet,
    @JsonKey(name: 'clinic_area') String? clinicArea,
    @JsonKey(name: 'clinic_city') String? clinicCity,
    @JsonKey(name: 'clinic_pincode') String? clinicPincode,
    @JsonKey(name: 'clinic_address') String? clinicAddress,
    @JsonKey(name: 'registration_no') String? registrationNo,
    @JsonKey(name: 'council_name') String? councilName,
    List<String>? qualifications,
    @JsonKey(name: 'years_of_experience') int? yearsOfExperience,
    @JsonKey(name: 'sub_specialties') String? subSpecialties,
    @JsonKey(name: 'key_procedures') String? keyProcedures,
    List<String>? languages,
    @JsonKey(name: 'consultation_in_person') bool? consultationInPerson,
    @JsonKey(name: 'consultation_teleconsult') bool? consultationTeleconsult,
    String? bio,
    List<String>? videos,
    List<String>? certificates,
    @JsonKey(name: 'profile_photo') String? profilePhoto,
  }) = _SpecialistProfile;

  factory SpecialistProfile.fromJson(Map<String, dynamic> json) => _$SpecialistProfileFromJson(json);
}
