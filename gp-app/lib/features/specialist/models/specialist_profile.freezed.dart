// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'specialist_profile.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
    'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models');

SpecialistProfile _$SpecialistProfileFromJson(Map<String, dynamic> json) {
  return _SpecialistProfile.fromJson(json);
}

/// @nodoc
mixin _$SpecialistProfile {
  int? get id => throw _privateConstructorUsedError;
  String? get name => throw _privateConstructorUsedError;
  String? get email => throw _privateConstructorUsedError;
  String? get mobile => throw _privateConstructorUsedError;
  String? get speciality => throw _privateConstructorUsedError;
  @JsonKey(name: 'primary_specialty_code')
  String? get primarySpecialtyCode => throw _privateConstructorUsedError;
  @JsonKey(name: 'additional_specialty_ids')
  List<int>? get additionalSpecialtyIds => throw _privateConstructorUsedError;
  @JsonKey(name: 'additional_specialty_labels')
  List<String>? get additionalSpecialtyLabels =>
      throw _privateConstructorUsedError;
  @JsonKey(name: 'hospital_name')
  String? get hospitalName => throw _privateConstructorUsedError;
  @JsonKey(name: 'clinic_street')
  String? get clinicStreet => throw _privateConstructorUsedError;
  @JsonKey(name: 'clinic_area')
  String? get clinicArea => throw _privateConstructorUsedError;
  @JsonKey(name: 'clinic_city')
  String? get clinicCity => throw _privateConstructorUsedError;
  @JsonKey(name: 'clinic_pincode')
  String? get clinicPincode => throw _privateConstructorUsedError;
  @JsonKey(name: 'clinic_address')
  String? get clinicAddress => throw _privateConstructorUsedError;
  @JsonKey(name: 'clinic_timings')
  String? get clinicTimings => throw _privateConstructorUsedError;
  @JsonKey(name: 'hospital_visiting_hours')
  String? get hospitalVisitingHours => throw _privateConstructorUsedError;
  @JsonKey(name: 'show_mobile_number')
  bool get showMobileNumber => throw _privateConstructorUsedError;
  @JsonKey(name: 'show_whatsapp_number')
  bool get showWhatsappNumber => throw _privateConstructorUsedError;
  @JsonKey(name: 'whatsapp_number')
  String? get whatsappNumber => throw _privateConstructorUsedError;
  @JsonKey(name: 'registration_no')
  String? get registrationNo => throw _privateConstructorUsedError;
  @JsonKey(name: 'council_name')
  String? get councilName => throw _privateConstructorUsedError;
  List<String>? get qualifications => throw _privateConstructorUsedError;
  @JsonKey(name: 'years_of_experience')
  int? get yearsOfExperience => throw _privateConstructorUsedError;
  @JsonKey(name: 'sub_specialties')
  String? get subSpecialties => throw _privateConstructorUsedError;
  @JsonKey(name: 'key_procedures')
  String? get keyProcedures => throw _privateConstructorUsedError;
  List<String>? get languages => throw _privateConstructorUsedError;
  @JsonKey(name: 'consultation_in_person')
  bool? get consultationInPerson => throw _privateConstructorUsedError;
  @JsonKey(name: 'consultation_teleconsult')
  bool? get consultationTeleconsult => throw _privateConstructorUsedError;
  String? get bio => throw _privateConstructorUsedError;
  List<String>? get videos => throw _privateConstructorUsedError;
  List<String>? get certificates => throw _privateConstructorUsedError;
  @JsonKey(name: 'profile_photo')
  String? get profilePhoto => throw _privateConstructorUsedError;

  /// Serializes this SpecialistProfile to a JSON map.
  Map<String, dynamic> toJson() => throw _privateConstructorUsedError;

  /// Create a copy of SpecialistProfile
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $SpecialistProfileCopyWith<SpecialistProfile> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $SpecialistProfileCopyWith<$Res> {
  factory $SpecialistProfileCopyWith(
          SpecialistProfile value, $Res Function(SpecialistProfile) then) =
      _$SpecialistProfileCopyWithImpl<$Res, SpecialistProfile>;
  @useResult
  $Res call(
      {int? id,
      String? name,
      String? email,
      String? mobile,
      String? speciality,
      @JsonKey(name: 'primary_specialty_code') String? primarySpecialtyCode,
      @JsonKey(name: 'additional_specialty_ids')
      List<int>? additionalSpecialtyIds,
      @JsonKey(name: 'additional_specialty_labels')
      List<String>? additionalSpecialtyLabels,
      @JsonKey(name: 'hospital_name') String? hospitalName,
      @JsonKey(name: 'clinic_street') String? clinicStreet,
      @JsonKey(name: 'clinic_area') String? clinicArea,
      @JsonKey(name: 'clinic_city') String? clinicCity,
      @JsonKey(name: 'clinic_pincode') String? clinicPincode,
      @JsonKey(name: 'clinic_address') String? clinicAddress,
      @JsonKey(name: 'clinic_timings') String? clinicTimings,
      @JsonKey(name: 'hospital_visiting_hours') String? hospitalVisitingHours,
      @JsonKey(name: 'show_mobile_number') bool showMobileNumber,
      @JsonKey(name: 'show_whatsapp_number') bool showWhatsappNumber,
      @JsonKey(name: 'whatsapp_number') String? whatsappNumber,
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
      @JsonKey(name: 'profile_photo') String? profilePhoto});
}

/// @nodoc
class _$SpecialistProfileCopyWithImpl<$Res, $Val extends SpecialistProfile>
    implements $SpecialistProfileCopyWith<$Res> {
  _$SpecialistProfileCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of SpecialistProfile
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = freezed,
    Object? name = freezed,
    Object? email = freezed,
    Object? mobile = freezed,
    Object? speciality = freezed,
    Object? primarySpecialtyCode = freezed,
    Object? additionalSpecialtyIds = freezed,
    Object? additionalSpecialtyLabels = freezed,
    Object? hospitalName = freezed,
    Object? clinicStreet = freezed,
    Object? clinicArea = freezed,
    Object? clinicCity = freezed,
    Object? clinicPincode = freezed,
    Object? clinicAddress = freezed,
    Object? clinicTimings = freezed,
    Object? hospitalVisitingHours = freezed,
    Object? showMobileNumber = null,
    Object? showWhatsappNumber = null,
    Object? whatsappNumber = freezed,
    Object? registrationNo = freezed,
    Object? councilName = freezed,
    Object? qualifications = freezed,
    Object? yearsOfExperience = freezed,
    Object? subSpecialties = freezed,
    Object? keyProcedures = freezed,
    Object? languages = freezed,
    Object? consultationInPerson = freezed,
    Object? consultationTeleconsult = freezed,
    Object? bio = freezed,
    Object? videos = freezed,
    Object? certificates = freezed,
    Object? profilePhoto = freezed,
  }) {
    return _then(_value.copyWith(
      id: freezed == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int?,
      name: freezed == name
          ? _value.name
          : name // ignore: cast_nullable_to_non_nullable
              as String?,
      email: freezed == email
          ? _value.email
          : email // ignore: cast_nullable_to_non_nullable
              as String?,
      mobile: freezed == mobile
          ? _value.mobile
          : mobile // ignore: cast_nullable_to_non_nullable
              as String?,
      speciality: freezed == speciality
          ? _value.speciality
          : speciality // ignore: cast_nullable_to_non_nullable
              as String?,
      primarySpecialtyCode: freezed == primarySpecialtyCode
          ? _value.primarySpecialtyCode
          : primarySpecialtyCode // ignore: cast_nullable_to_non_nullable
              as String?,
      additionalSpecialtyIds: freezed == additionalSpecialtyIds
          ? _value.additionalSpecialtyIds
          : additionalSpecialtyIds // ignore: cast_nullable_to_non_nullable
              as List<int>?,
      additionalSpecialtyLabels: freezed == additionalSpecialtyLabels
          ? _value.additionalSpecialtyLabels
          : additionalSpecialtyLabels // ignore: cast_nullable_to_non_nullable
              as List<String>?,
      hospitalName: freezed == hospitalName
          ? _value.hospitalName
          : hospitalName // ignore: cast_nullable_to_non_nullable
              as String?,
      clinicStreet: freezed == clinicStreet
          ? _value.clinicStreet
          : clinicStreet // ignore: cast_nullable_to_non_nullable
              as String?,
      clinicArea: freezed == clinicArea
          ? _value.clinicArea
          : clinicArea // ignore: cast_nullable_to_non_nullable
              as String?,
      clinicCity: freezed == clinicCity
          ? _value.clinicCity
          : clinicCity // ignore: cast_nullable_to_non_nullable
              as String?,
      clinicPincode: freezed == clinicPincode
          ? _value.clinicPincode
          : clinicPincode // ignore: cast_nullable_to_non_nullable
              as String?,
      clinicAddress: freezed == clinicAddress
          ? _value.clinicAddress
          : clinicAddress // ignore: cast_nullable_to_non_nullable
              as String?,
      clinicTimings: freezed == clinicTimings
          ? _value.clinicTimings
          : clinicTimings // ignore: cast_nullable_to_non_nullable
              as String?,
      hospitalVisitingHours: freezed == hospitalVisitingHours
          ? _value.hospitalVisitingHours
          : hospitalVisitingHours // ignore: cast_nullable_to_non_nullable
              as String?,
      showMobileNumber: null == showMobileNumber
          ? _value.showMobileNumber
          : showMobileNumber // ignore: cast_nullable_to_non_nullable
              as bool,
      showWhatsappNumber: null == showWhatsappNumber
          ? _value.showWhatsappNumber
          : showWhatsappNumber // ignore: cast_nullable_to_non_nullable
              as bool,
      whatsappNumber: freezed == whatsappNumber
          ? _value.whatsappNumber
          : whatsappNumber // ignore: cast_nullable_to_non_nullable
              as String?,
      registrationNo: freezed == registrationNo
          ? _value.registrationNo
          : registrationNo // ignore: cast_nullable_to_non_nullable
              as String?,
      councilName: freezed == councilName
          ? _value.councilName
          : councilName // ignore: cast_nullable_to_non_nullable
              as String?,
      qualifications: freezed == qualifications
          ? _value.qualifications
          : qualifications // ignore: cast_nullable_to_non_nullable
              as List<String>?,
      yearsOfExperience: freezed == yearsOfExperience
          ? _value.yearsOfExperience
          : yearsOfExperience // ignore: cast_nullable_to_non_nullable
              as int?,
      subSpecialties: freezed == subSpecialties
          ? _value.subSpecialties
          : subSpecialties // ignore: cast_nullable_to_non_nullable
              as String?,
      keyProcedures: freezed == keyProcedures
          ? _value.keyProcedures
          : keyProcedures // ignore: cast_nullable_to_non_nullable
              as String?,
      languages: freezed == languages
          ? _value.languages
          : languages // ignore: cast_nullable_to_non_nullable
              as List<String>?,
      consultationInPerson: freezed == consultationInPerson
          ? _value.consultationInPerson
          : consultationInPerson // ignore: cast_nullable_to_non_nullable
              as bool?,
      consultationTeleconsult: freezed == consultationTeleconsult
          ? _value.consultationTeleconsult
          : consultationTeleconsult // ignore: cast_nullable_to_non_nullable
              as bool?,
      bio: freezed == bio
          ? _value.bio
          : bio // ignore: cast_nullable_to_non_nullable
              as String?,
      videos: freezed == videos
          ? _value.videos
          : videos // ignore: cast_nullable_to_non_nullable
              as List<String>?,
      certificates: freezed == certificates
          ? _value.certificates
          : certificates // ignore: cast_nullable_to_non_nullable
              as List<String>?,
      profilePhoto: freezed == profilePhoto
          ? _value.profilePhoto
          : profilePhoto // ignore: cast_nullable_to_non_nullable
              as String?,
    ) as $Val);
  }
}

/// @nodoc
abstract class _$$SpecialistProfileImplCopyWith<$Res>
    implements $SpecialistProfileCopyWith<$Res> {
  factory _$$SpecialistProfileImplCopyWith(_$SpecialistProfileImpl value,
          $Res Function(_$SpecialistProfileImpl) then) =
      __$$SpecialistProfileImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call(
      {int? id,
      String? name,
      String? email,
      String? mobile,
      String? speciality,
      @JsonKey(name: 'primary_specialty_code') String? primarySpecialtyCode,
      @JsonKey(name: 'additional_specialty_ids')
      List<int>? additionalSpecialtyIds,
      @JsonKey(name: 'additional_specialty_labels')
      List<String>? additionalSpecialtyLabels,
      @JsonKey(name: 'hospital_name') String? hospitalName,
      @JsonKey(name: 'clinic_street') String? clinicStreet,
      @JsonKey(name: 'clinic_area') String? clinicArea,
      @JsonKey(name: 'clinic_city') String? clinicCity,
      @JsonKey(name: 'clinic_pincode') String? clinicPincode,
      @JsonKey(name: 'clinic_address') String? clinicAddress,
      @JsonKey(name: 'clinic_timings') String? clinicTimings,
      @JsonKey(name: 'hospital_visiting_hours') String? hospitalVisitingHours,
      @JsonKey(name: 'show_mobile_number') bool showMobileNumber,
      @JsonKey(name: 'show_whatsapp_number') bool showWhatsappNumber,
      @JsonKey(name: 'whatsapp_number') String? whatsappNumber,
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
      @JsonKey(name: 'profile_photo') String? profilePhoto});
}

/// @nodoc
class __$$SpecialistProfileImplCopyWithImpl<$Res>
    extends _$SpecialistProfileCopyWithImpl<$Res, _$SpecialistProfileImpl>
    implements _$$SpecialistProfileImplCopyWith<$Res> {
  __$$SpecialistProfileImplCopyWithImpl(_$SpecialistProfileImpl _value,
      $Res Function(_$SpecialistProfileImpl) _then)
      : super(_value, _then);

  /// Create a copy of SpecialistProfile
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = freezed,
    Object? name = freezed,
    Object? email = freezed,
    Object? mobile = freezed,
    Object? speciality = freezed,
    Object? primarySpecialtyCode = freezed,
    Object? additionalSpecialtyIds = freezed,
    Object? additionalSpecialtyLabels = freezed,
    Object? hospitalName = freezed,
    Object? clinicStreet = freezed,
    Object? clinicArea = freezed,
    Object? clinicCity = freezed,
    Object? clinicPincode = freezed,
    Object? clinicAddress = freezed,
    Object? clinicTimings = freezed,
    Object? hospitalVisitingHours = freezed,
    Object? showMobileNumber = null,
    Object? showWhatsappNumber = null,
    Object? whatsappNumber = freezed,
    Object? registrationNo = freezed,
    Object? councilName = freezed,
    Object? qualifications = freezed,
    Object? yearsOfExperience = freezed,
    Object? subSpecialties = freezed,
    Object? keyProcedures = freezed,
    Object? languages = freezed,
    Object? consultationInPerson = freezed,
    Object? consultationTeleconsult = freezed,
    Object? bio = freezed,
    Object? videos = freezed,
    Object? certificates = freezed,
    Object? profilePhoto = freezed,
  }) {
    return _then(_$SpecialistProfileImpl(
      id: freezed == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int?,
      name: freezed == name
          ? _value.name
          : name // ignore: cast_nullable_to_non_nullable
              as String?,
      email: freezed == email
          ? _value.email
          : email // ignore: cast_nullable_to_non_nullable
              as String?,
      mobile: freezed == mobile
          ? _value.mobile
          : mobile // ignore: cast_nullable_to_non_nullable
              as String?,
      speciality: freezed == speciality
          ? _value.speciality
          : speciality // ignore: cast_nullable_to_non_nullable
              as String?,
      primarySpecialtyCode: freezed == primarySpecialtyCode
          ? _value.primarySpecialtyCode
          : primarySpecialtyCode // ignore: cast_nullable_to_non_nullable
              as String?,
      additionalSpecialtyIds: freezed == additionalSpecialtyIds
          ? _value._additionalSpecialtyIds
          : additionalSpecialtyIds // ignore: cast_nullable_to_non_nullable
              as List<int>?,
      additionalSpecialtyLabels: freezed == additionalSpecialtyLabels
          ? _value._additionalSpecialtyLabels
          : additionalSpecialtyLabels // ignore: cast_nullable_to_non_nullable
              as List<String>?,
      hospitalName: freezed == hospitalName
          ? _value.hospitalName
          : hospitalName // ignore: cast_nullable_to_non_nullable
              as String?,
      clinicStreet: freezed == clinicStreet
          ? _value.clinicStreet
          : clinicStreet // ignore: cast_nullable_to_non_nullable
              as String?,
      clinicArea: freezed == clinicArea
          ? _value.clinicArea
          : clinicArea // ignore: cast_nullable_to_non_nullable
              as String?,
      clinicCity: freezed == clinicCity
          ? _value.clinicCity
          : clinicCity // ignore: cast_nullable_to_non_nullable
              as String?,
      clinicPincode: freezed == clinicPincode
          ? _value.clinicPincode
          : clinicPincode // ignore: cast_nullable_to_non_nullable
              as String?,
      clinicAddress: freezed == clinicAddress
          ? _value.clinicAddress
          : clinicAddress // ignore: cast_nullable_to_non_nullable
              as String?,
      clinicTimings: freezed == clinicTimings
          ? _value.clinicTimings
          : clinicTimings // ignore: cast_nullable_to_non_nullable
              as String?,
      hospitalVisitingHours: freezed == hospitalVisitingHours
          ? _value.hospitalVisitingHours
          : hospitalVisitingHours // ignore: cast_nullable_to_non_nullable
              as String?,
      showMobileNumber: null == showMobileNumber
          ? _value.showMobileNumber
          : showMobileNumber // ignore: cast_nullable_to_non_nullable
              as bool,
      showWhatsappNumber: null == showWhatsappNumber
          ? _value.showWhatsappNumber
          : showWhatsappNumber // ignore: cast_nullable_to_non_nullable
              as bool,
      whatsappNumber: freezed == whatsappNumber
          ? _value.whatsappNumber
          : whatsappNumber // ignore: cast_nullable_to_non_nullable
              as String?,
      registrationNo: freezed == registrationNo
          ? _value.registrationNo
          : registrationNo // ignore: cast_nullable_to_non_nullable
              as String?,
      councilName: freezed == councilName
          ? _value.councilName
          : councilName // ignore: cast_nullable_to_non_nullable
              as String?,
      qualifications: freezed == qualifications
          ? _value._qualifications
          : qualifications // ignore: cast_nullable_to_non_nullable
              as List<String>?,
      yearsOfExperience: freezed == yearsOfExperience
          ? _value.yearsOfExperience
          : yearsOfExperience // ignore: cast_nullable_to_non_nullable
              as int?,
      subSpecialties: freezed == subSpecialties
          ? _value.subSpecialties
          : subSpecialties // ignore: cast_nullable_to_non_nullable
              as String?,
      keyProcedures: freezed == keyProcedures
          ? _value.keyProcedures
          : keyProcedures // ignore: cast_nullable_to_non_nullable
              as String?,
      languages: freezed == languages
          ? _value._languages
          : languages // ignore: cast_nullable_to_non_nullable
              as List<String>?,
      consultationInPerson: freezed == consultationInPerson
          ? _value.consultationInPerson
          : consultationInPerson // ignore: cast_nullable_to_non_nullable
              as bool?,
      consultationTeleconsult: freezed == consultationTeleconsult
          ? _value.consultationTeleconsult
          : consultationTeleconsult // ignore: cast_nullable_to_non_nullable
              as bool?,
      bio: freezed == bio
          ? _value.bio
          : bio // ignore: cast_nullable_to_non_nullable
              as String?,
      videos: freezed == videos
          ? _value._videos
          : videos // ignore: cast_nullable_to_non_nullable
              as List<String>?,
      certificates: freezed == certificates
          ? _value._certificates
          : certificates // ignore: cast_nullable_to_non_nullable
              as List<String>?,
      profilePhoto: freezed == profilePhoto
          ? _value.profilePhoto
          : profilePhoto // ignore: cast_nullable_to_non_nullable
              as String?,
    ));
  }
}

/// @nodoc
@JsonSerializable()
class _$SpecialistProfileImpl implements _SpecialistProfile {
  const _$SpecialistProfileImpl(
      {this.id,
      this.name,
      this.email,
      this.mobile,
      this.speciality,
      @JsonKey(name: 'primary_specialty_code') this.primarySpecialtyCode,
      @JsonKey(name: 'additional_specialty_ids')
      final List<int>? additionalSpecialtyIds,
      @JsonKey(name: 'additional_specialty_labels')
      final List<String>? additionalSpecialtyLabels,
      @JsonKey(name: 'hospital_name') this.hospitalName,
      @JsonKey(name: 'clinic_street') this.clinicStreet,
      @JsonKey(name: 'clinic_area') this.clinicArea,
      @JsonKey(name: 'clinic_city') this.clinicCity,
      @JsonKey(name: 'clinic_pincode') this.clinicPincode,
      @JsonKey(name: 'clinic_address') this.clinicAddress,
      @JsonKey(name: 'clinic_timings') this.clinicTimings,
      @JsonKey(name: 'hospital_visiting_hours') this.hospitalVisitingHours,
      @JsonKey(name: 'show_mobile_number') this.showMobileNumber = true,
      @JsonKey(name: 'show_whatsapp_number') this.showWhatsappNumber = true,
      @JsonKey(name: 'whatsapp_number') this.whatsappNumber,
      @JsonKey(name: 'registration_no') this.registrationNo,
      @JsonKey(name: 'council_name') this.councilName,
      final List<String>? qualifications,
      @JsonKey(name: 'years_of_experience') this.yearsOfExperience,
      @JsonKey(name: 'sub_specialties') this.subSpecialties,
      @JsonKey(name: 'key_procedures') this.keyProcedures,
      final List<String>? languages,
      @JsonKey(name: 'consultation_in_person') this.consultationInPerson,
      @JsonKey(name: 'consultation_teleconsult') this.consultationTeleconsult,
      this.bio,
      final List<String>? videos,
      final List<String>? certificates,
      @JsonKey(name: 'profile_photo') this.profilePhoto})
      : _additionalSpecialtyIds = additionalSpecialtyIds,
        _additionalSpecialtyLabels = additionalSpecialtyLabels,
        _qualifications = qualifications,
        _languages = languages,
        _videos = videos,
        _certificates = certificates;

  factory _$SpecialistProfileImpl.fromJson(Map<String, dynamic> json) =>
      _$$SpecialistProfileImplFromJson(json);

  @override
  final int? id;
  @override
  final String? name;
  @override
  final String? email;
  @override
  final String? mobile;
  @override
  final String? speciality;
  @override
  @JsonKey(name: 'primary_specialty_code')
  final String? primarySpecialtyCode;
  final List<int>? _additionalSpecialtyIds;
  @override
  @JsonKey(name: 'additional_specialty_ids')
  List<int>? get additionalSpecialtyIds {
    final value = _additionalSpecialtyIds;
    if (value == null) return null;
    if (_additionalSpecialtyIds is EqualUnmodifiableListView)
      return _additionalSpecialtyIds;
    // ignore: implicit_dynamic_type
    return EqualUnmodifiableListView(value);
  }

  final List<String>? _additionalSpecialtyLabels;
  @override
  @JsonKey(name: 'additional_specialty_labels')
  List<String>? get additionalSpecialtyLabels {
    final value = _additionalSpecialtyLabels;
    if (value == null) return null;
    if (_additionalSpecialtyLabels is EqualUnmodifiableListView)
      return _additionalSpecialtyLabels;
    // ignore: implicit_dynamic_type
    return EqualUnmodifiableListView(value);
  }

  @override
  @JsonKey(name: 'hospital_name')
  final String? hospitalName;
  @override
  @JsonKey(name: 'clinic_street')
  final String? clinicStreet;
  @override
  @JsonKey(name: 'clinic_area')
  final String? clinicArea;
  @override
  @JsonKey(name: 'clinic_city')
  final String? clinicCity;
  @override
  @JsonKey(name: 'clinic_pincode')
  final String? clinicPincode;
  @override
  @JsonKey(name: 'clinic_address')
  final String? clinicAddress;
  @override
  @JsonKey(name: 'clinic_timings')
  final String? clinicTimings;
  @override
  @JsonKey(name: 'hospital_visiting_hours')
  final String? hospitalVisitingHours;
  @override
  @JsonKey(name: 'show_mobile_number')
  final bool showMobileNumber;
  @override
  @JsonKey(name: 'show_whatsapp_number')
  final bool showWhatsappNumber;
  @override
  @JsonKey(name: 'whatsapp_number')
  final String? whatsappNumber;
  @override
  @JsonKey(name: 'registration_no')
  final String? registrationNo;
  @override
  @JsonKey(name: 'council_name')
  final String? councilName;
  final List<String>? _qualifications;
  @override
  List<String>? get qualifications {
    final value = _qualifications;
    if (value == null) return null;
    if (_qualifications is EqualUnmodifiableListView) return _qualifications;
    // ignore: implicit_dynamic_type
    return EqualUnmodifiableListView(value);
  }

  @override
  @JsonKey(name: 'years_of_experience')
  final int? yearsOfExperience;
  @override
  @JsonKey(name: 'sub_specialties')
  final String? subSpecialties;
  @override
  @JsonKey(name: 'key_procedures')
  final String? keyProcedures;
  final List<String>? _languages;
  @override
  List<String>? get languages {
    final value = _languages;
    if (value == null) return null;
    if (_languages is EqualUnmodifiableListView) return _languages;
    // ignore: implicit_dynamic_type
    return EqualUnmodifiableListView(value);
  }

  @override
  @JsonKey(name: 'consultation_in_person')
  final bool? consultationInPerson;
  @override
  @JsonKey(name: 'consultation_teleconsult')
  final bool? consultationTeleconsult;
  @override
  final String? bio;
  final List<String>? _videos;
  @override
  List<String>? get videos {
    final value = _videos;
    if (value == null) return null;
    if (_videos is EqualUnmodifiableListView) return _videos;
    // ignore: implicit_dynamic_type
    return EqualUnmodifiableListView(value);
  }

  final List<String>? _certificates;
  @override
  List<String>? get certificates {
    final value = _certificates;
    if (value == null) return null;
    if (_certificates is EqualUnmodifiableListView) return _certificates;
    // ignore: implicit_dynamic_type
    return EqualUnmodifiableListView(value);
  }

  @override
  @JsonKey(name: 'profile_photo')
  final String? profilePhoto;

  @override
  String toString() {
    return 'SpecialistProfile(id: $id, name: $name, email: $email, mobile: $mobile, speciality: $speciality, primarySpecialtyCode: $primarySpecialtyCode, additionalSpecialtyIds: $additionalSpecialtyIds, additionalSpecialtyLabels: $additionalSpecialtyLabels, hospitalName: $hospitalName, clinicStreet: $clinicStreet, clinicArea: $clinicArea, clinicCity: $clinicCity, clinicPincode: $clinicPincode, clinicAddress: $clinicAddress, clinicTimings: $clinicTimings, hospitalVisitingHours: $hospitalVisitingHours, showMobileNumber: $showMobileNumber, showWhatsappNumber: $showWhatsappNumber, whatsappNumber: $whatsappNumber, registrationNo: $registrationNo, councilName: $councilName, qualifications: $qualifications, yearsOfExperience: $yearsOfExperience, subSpecialties: $subSpecialties, keyProcedures: $keyProcedures, languages: $languages, consultationInPerson: $consultationInPerson, consultationTeleconsult: $consultationTeleconsult, bio: $bio, videos: $videos, certificates: $certificates, profilePhoto: $profilePhoto)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$SpecialistProfileImpl &&
            (identical(other.id, id) || other.id == id) &&
            (identical(other.name, name) || other.name == name) &&
            (identical(other.email, email) || other.email == email) &&
            (identical(other.mobile, mobile) || other.mobile == mobile) &&
            (identical(other.speciality, speciality) ||
                other.speciality == speciality) &&
            (identical(other.primarySpecialtyCode, primarySpecialtyCode) ||
                other.primarySpecialtyCode == primarySpecialtyCode) &&
            const DeepCollectionEquality().equals(
                other._additionalSpecialtyIds, _additionalSpecialtyIds) &&
            const DeepCollectionEquality().equals(
                other._additionalSpecialtyLabels, _additionalSpecialtyLabels) &&
            (identical(other.hospitalName, hospitalName) ||
                other.hospitalName == hospitalName) &&
            (identical(other.clinicStreet, clinicStreet) ||
                other.clinicStreet == clinicStreet) &&
            (identical(other.clinicArea, clinicArea) ||
                other.clinicArea == clinicArea) &&
            (identical(other.clinicCity, clinicCity) ||
                other.clinicCity == clinicCity) &&
            (identical(other.clinicPincode, clinicPincode) ||
                other.clinicPincode == clinicPincode) &&
            (identical(other.clinicAddress, clinicAddress) ||
                other.clinicAddress == clinicAddress) &&
            (identical(other.clinicTimings, clinicTimings) ||
                other.clinicTimings == clinicTimings) &&
            (identical(other.hospitalVisitingHours, hospitalVisitingHours) ||
                other.hospitalVisitingHours == hospitalVisitingHours) &&
            (identical(other.showMobileNumber, showMobileNumber) ||
                other.showMobileNumber == showMobileNumber) &&
            (identical(other.showWhatsappNumber, showWhatsappNumber) ||
                other.showWhatsappNumber == showWhatsappNumber) &&
            (identical(other.whatsappNumber, whatsappNumber) ||
                other.whatsappNumber == whatsappNumber) &&
            (identical(other.registrationNo, registrationNo) ||
                other.registrationNo == registrationNo) &&
            (identical(other.councilName, councilName) ||
                other.councilName == councilName) &&
            const DeepCollectionEquality()
                .equals(other._qualifications, _qualifications) &&
            (identical(other.yearsOfExperience, yearsOfExperience) ||
                other.yearsOfExperience == yearsOfExperience) &&
            (identical(other.subSpecialties, subSpecialties) ||
                other.subSpecialties == subSpecialties) &&
            (identical(other.keyProcedures, keyProcedures) ||
                other.keyProcedures == keyProcedures) &&
            const DeepCollectionEquality()
                .equals(other._languages, _languages) &&
            (identical(other.consultationInPerson, consultationInPerson) ||
                other.consultationInPerson == consultationInPerson) &&
            (identical(
                    other.consultationTeleconsult, consultationTeleconsult) ||
                other.consultationTeleconsult == consultationTeleconsult) &&
            (identical(other.bio, bio) || other.bio == bio) &&
            const DeepCollectionEquality().equals(other._videos, _videos) &&
            const DeepCollectionEquality()
                .equals(other._certificates, _certificates) &&
            (identical(other.profilePhoto, profilePhoto) ||
                other.profilePhoto == profilePhoto));
  }

  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  int get hashCode => Object.hashAll([
        runtimeType,
        id,
        name,
        email,
        mobile,
        speciality,
        primarySpecialtyCode,
        const DeepCollectionEquality().hash(_additionalSpecialtyIds),
        const DeepCollectionEquality().hash(_additionalSpecialtyLabels),
        hospitalName,
        clinicStreet,
        clinicArea,
        clinicCity,
        clinicPincode,
        clinicAddress,
        clinicTimings,
        hospitalVisitingHours,
        showMobileNumber,
        showWhatsappNumber,
        whatsappNumber,
        registrationNo,
        councilName,
        const DeepCollectionEquality().hash(_qualifications),
        yearsOfExperience,
        subSpecialties,
        keyProcedures,
        const DeepCollectionEquality().hash(_languages),
        consultationInPerson,
        consultationTeleconsult,
        bio,
        const DeepCollectionEquality().hash(_videos),
        const DeepCollectionEquality().hash(_certificates),
        profilePhoto
      ]);

  /// Create a copy of SpecialistProfile
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$SpecialistProfileImplCopyWith<_$SpecialistProfileImpl> get copyWith =>
      __$$SpecialistProfileImplCopyWithImpl<_$SpecialistProfileImpl>(
          this, _$identity);

  @override
  Map<String, dynamic> toJson() {
    return _$$SpecialistProfileImplToJson(
      this,
    );
  }
}

abstract class _SpecialistProfile implements SpecialistProfile {
  const factory _SpecialistProfile(
      {final int? id,
      final String? name,
      final String? email,
      final String? mobile,
      final String? speciality,
      @JsonKey(name: 'primary_specialty_code')
      final String? primarySpecialtyCode,
      @JsonKey(name: 'additional_specialty_ids')
      final List<int>? additionalSpecialtyIds,
      @JsonKey(name: 'additional_specialty_labels')
      final List<String>? additionalSpecialtyLabels,
      @JsonKey(name: 'hospital_name') final String? hospitalName,
      @JsonKey(name: 'clinic_street') final String? clinicStreet,
      @JsonKey(name: 'clinic_area') final String? clinicArea,
      @JsonKey(name: 'clinic_city') final String? clinicCity,
      @JsonKey(name: 'clinic_pincode') final String? clinicPincode,
      @JsonKey(name: 'clinic_address') final String? clinicAddress,
      @JsonKey(name: 'clinic_timings') final String? clinicTimings,
      @JsonKey(name: 'hospital_visiting_hours')
      final String? hospitalVisitingHours,
      @JsonKey(name: 'show_mobile_number') final bool showMobileNumber,
      @JsonKey(name: 'show_whatsapp_number') final bool showWhatsappNumber,
      @JsonKey(name: 'whatsapp_number') final String? whatsappNumber,
      @JsonKey(name: 'registration_no') final String? registrationNo,
      @JsonKey(name: 'council_name') final String? councilName,
      final List<String>? qualifications,
      @JsonKey(name: 'years_of_experience') final int? yearsOfExperience,
      @JsonKey(name: 'sub_specialties') final String? subSpecialties,
      @JsonKey(name: 'key_procedures') final String? keyProcedures,
      final List<String>? languages,
      @JsonKey(name: 'consultation_in_person') final bool? consultationInPerson,
      @JsonKey(name: 'consultation_teleconsult')
      final bool? consultationTeleconsult,
      final String? bio,
      final List<String>? videos,
      final List<String>? certificates,
      @JsonKey(name: 'profile_photo')
      final String? profilePhoto}) = _$SpecialistProfileImpl;

  factory _SpecialistProfile.fromJson(Map<String, dynamic> json) =
      _$SpecialistProfileImpl.fromJson;

  @override
  int? get id;
  @override
  String? get name;
  @override
  String? get email;
  @override
  String? get mobile;
  @override
  String? get speciality;
  @override
  @JsonKey(name: 'primary_specialty_code')
  String? get primarySpecialtyCode;
  @override
  @JsonKey(name: 'additional_specialty_ids')
  List<int>? get additionalSpecialtyIds;
  @override
  @JsonKey(name: 'additional_specialty_labels')
  List<String>? get additionalSpecialtyLabels;
  @override
  @JsonKey(name: 'hospital_name')
  String? get hospitalName;
  @override
  @JsonKey(name: 'clinic_street')
  String? get clinicStreet;
  @override
  @JsonKey(name: 'clinic_area')
  String? get clinicArea;
  @override
  @JsonKey(name: 'clinic_city')
  String? get clinicCity;
  @override
  @JsonKey(name: 'clinic_pincode')
  String? get clinicPincode;
  @override
  @JsonKey(name: 'clinic_address')
  String? get clinicAddress;
  @override
  @JsonKey(name: 'clinic_timings')
  String? get clinicTimings;
  @override
  @JsonKey(name: 'hospital_visiting_hours')
  String? get hospitalVisitingHours;
  @override
  @JsonKey(name: 'show_mobile_number')
  bool get showMobileNumber;
  @override
  @JsonKey(name: 'show_whatsapp_number')
  bool get showWhatsappNumber;
  @override
  @JsonKey(name: 'whatsapp_number')
  String? get whatsappNumber;
  @override
  @JsonKey(name: 'registration_no')
  String? get registrationNo;
  @override
  @JsonKey(name: 'council_name')
  String? get councilName;
  @override
  List<String>? get qualifications;
  @override
  @JsonKey(name: 'years_of_experience')
  int? get yearsOfExperience;
  @override
  @JsonKey(name: 'sub_specialties')
  String? get subSpecialties;
  @override
  @JsonKey(name: 'key_procedures')
  String? get keyProcedures;
  @override
  List<String>? get languages;
  @override
  @JsonKey(name: 'consultation_in_person')
  bool? get consultationInPerson;
  @override
  @JsonKey(name: 'consultation_teleconsult')
  bool? get consultationTeleconsult;
  @override
  String? get bio;
  @override
  List<String>? get videos;
  @override
  List<String>? get certificates;
  @override
  @JsonKey(name: 'profile_photo')
  String? get profilePhoto;

  /// Create a copy of SpecialistProfile
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$SpecialistProfileImplCopyWith<_$SpecialistProfileImpl> get copyWith =>
      throw _privateConstructorUsedError;
}
