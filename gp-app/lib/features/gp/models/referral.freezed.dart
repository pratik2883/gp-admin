// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'referral.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
    'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models');

Referral _$ReferralFromJson(Map<String, dynamic> json) {
  return _Referral.fromJson(json);
}

/// @nodoc
mixin _$Referral {
  int get id => throw _privateConstructorUsedError;
  String? get lead_code => throw _privateConstructorUsedError;
  String? get referral_type => throw _privateConstructorUsedError;
  String? get status => throw _privateConstructorUsedError;
  int? get gp_id => throw _privateConstructorUsedError;
  @JsonKey(readValue: _readSpecialistId)
  int? get specialist_id => throw _privateConstructorUsedError;
  @JsonKey(readValue: _readHospitalId)
  int? get hospital_id => throw _privateConstructorUsedError;
  @JsonKey(readValue: _readSpecialistName)
  String? get specialist_name => throw _privateConstructorUsedError;
  @JsonKey(readValue: _readHospitalName)
  String? get hospital_name => throw _privateConstructorUsedError;
  @JsonKey(readValue: _readDiagnosticCenterId)
  int? get diagnostic_center_id => throw _privateConstructorUsedError;
  @JsonKey(readValue: _readDiagnosticCenterName)
  String? get diagnostic_center_name => throw _privateConstructorUsedError;
  String? get department => throw _privateConstructorUsedError;
  @JsonKey(name: 'appointment_type')
  String? get visit_type => throw _privateConstructorUsedError;
  String? get priority => throw _privateConstructorUsedError;
  String? get patient_name => throw _privateConstructorUsedError;
  String? get patient_mobile => throw _privateConstructorUsedError;
  @JsonKey(readValue: _readPatientAge)
  int? get patient_age => throw _privateConstructorUsedError;
  String? get patient_gender => throw _privateConstructorUsedError;
  @JsonKey(name: 'case_summary')
  String? get notes => throw _privateConstructorUsedError;
  DateTime? get created_at =>
      throw _privateConstructorUsedError; // Extended specialist details from ReferralResource
  @JsonKey(readValue: _readSpecialistSpeciality)
  String? get specialist_speciality => throw _privateConstructorUsedError;
  @JsonKey(readValue: _readSpecialistAddress)
  String? get specialist_address => throw _privateConstructorUsedError;
  @JsonKey(readValue: _readSpecialistExperience)
  int? get specialist_experience => throw _privateConstructorUsedError;
  @JsonKey(readValue: _readHospitalAddress)
  String? get hospital_address => throw _privateConstructorUsedError;
  @JsonKey(readValue: _readHospitalCity)
  String? get hospital_city =>
      throw _privateConstructorUsedError; // Specialist nested object (full details)
  @JsonKey(name: 'specialist')
  Map<String, dynamic>? get specialist_details =>
      throw _privateConstructorUsedError;
  @JsonKey(name: 'hospital')
  Map<String, dynamic>? get hospital_details =>
      throw _privateConstructorUsedError; // Files
  @JsonKey(name: 'files')
  List<Map<String, dynamic>>? get files =>
      throw _privateConstructorUsedError; // Timeline
  DateTime? get accepted_at => throw _privateConstructorUsedError;
  DateTime? get consulted_at => throw _privateConstructorUsedError;
  DateTime? get closed_at => throw _privateConstructorUsedError;

  /// Serializes this Referral to a JSON map.
  Map<String, dynamic> toJson() => throw _privateConstructorUsedError;

  /// Create a copy of Referral
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $ReferralCopyWith<Referral> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $ReferralCopyWith<$Res> {
  factory $ReferralCopyWith(Referral value, $Res Function(Referral) then) =
      _$ReferralCopyWithImpl<$Res, Referral>;
  @useResult
  $Res call(
      {int id,
      String? lead_code,
      String? referral_type,
      String? status,
      int? gp_id,
      @JsonKey(readValue: _readSpecialistId) int? specialist_id,
      @JsonKey(readValue: _readHospitalId) int? hospital_id,
      @JsonKey(readValue: _readSpecialistName) String? specialist_name,
      @JsonKey(readValue: _readHospitalName) String? hospital_name,
      @JsonKey(readValue: _readDiagnosticCenterId) int? diagnostic_center_id,
      @JsonKey(readValue: _readDiagnosticCenterName)
      String? diagnostic_center_name,
      String? department,
      @JsonKey(name: 'appointment_type') String? visit_type,
      String? priority,
      String? patient_name,
      String? patient_mobile,
      @JsonKey(readValue: _readPatientAge) int? patient_age,
      String? patient_gender,
      @JsonKey(name: 'case_summary') String? notes,
      DateTime? created_at,
      @JsonKey(readValue: _readSpecialistSpeciality)
      String? specialist_speciality,
      @JsonKey(readValue: _readSpecialistAddress) String? specialist_address,
      @JsonKey(readValue: _readSpecialistExperience) int? specialist_experience,
      @JsonKey(readValue: _readHospitalAddress) String? hospital_address,
      @JsonKey(readValue: _readHospitalCity) String? hospital_city,
      @JsonKey(name: 'specialist') Map<String, dynamic>? specialist_details,
      @JsonKey(name: 'hospital') Map<String, dynamic>? hospital_details,
      @JsonKey(name: 'files') List<Map<String, dynamic>>? files,
      DateTime? accepted_at,
      DateTime? consulted_at,
      DateTime? closed_at});
}

/// @nodoc
class _$ReferralCopyWithImpl<$Res, $Val extends Referral>
    implements $ReferralCopyWith<$Res> {
  _$ReferralCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of Referral
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? lead_code = freezed,
    Object? referral_type = freezed,
    Object? status = freezed,
    Object? gp_id = freezed,
    Object? specialist_id = freezed,
    Object? hospital_id = freezed,
    Object? specialist_name = freezed,
    Object? hospital_name = freezed,
    Object? diagnostic_center_id = freezed,
    Object? diagnostic_center_name = freezed,
    Object? department = freezed,
    Object? visit_type = freezed,
    Object? priority = freezed,
    Object? patient_name = freezed,
    Object? patient_mobile = freezed,
    Object? patient_age = freezed,
    Object? patient_gender = freezed,
    Object? notes = freezed,
    Object? created_at = freezed,
    Object? specialist_speciality = freezed,
    Object? specialist_address = freezed,
    Object? specialist_experience = freezed,
    Object? hospital_address = freezed,
    Object? hospital_city = freezed,
    Object? specialist_details = freezed,
    Object? hospital_details = freezed,
    Object? files = freezed,
    Object? accepted_at = freezed,
    Object? consulted_at = freezed,
    Object? closed_at = freezed,
  }) {
    return _then(_value.copyWith(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int,
      lead_code: freezed == lead_code
          ? _value.lead_code
          : lead_code // ignore: cast_nullable_to_non_nullable
              as String?,
      referral_type: freezed == referral_type
          ? _value.referral_type
          : referral_type // ignore: cast_nullable_to_non_nullable
              as String?,
      status: freezed == status
          ? _value.status
          : status // ignore: cast_nullable_to_non_nullable
              as String?,
      gp_id: freezed == gp_id
          ? _value.gp_id
          : gp_id // ignore: cast_nullable_to_non_nullable
              as int?,
      specialist_id: freezed == specialist_id
          ? _value.specialist_id
          : specialist_id // ignore: cast_nullable_to_non_nullable
              as int?,
      hospital_id: freezed == hospital_id
          ? _value.hospital_id
          : hospital_id // ignore: cast_nullable_to_non_nullable
              as int?,
      specialist_name: freezed == specialist_name
          ? _value.specialist_name
          : specialist_name // ignore: cast_nullable_to_non_nullable
              as String?,
      hospital_name: freezed == hospital_name
          ? _value.hospital_name
          : hospital_name // ignore: cast_nullable_to_non_nullable
              as String?,
      diagnostic_center_id: freezed == diagnostic_center_id
          ? _value.diagnostic_center_id
          : diagnostic_center_id // ignore: cast_nullable_to_non_nullable
              as int?,
      diagnostic_center_name: freezed == diagnostic_center_name
          ? _value.diagnostic_center_name
          : diagnostic_center_name // ignore: cast_nullable_to_non_nullable
              as String?,
      department: freezed == department
          ? _value.department
          : department // ignore: cast_nullable_to_non_nullable
              as String?,
      visit_type: freezed == visit_type
          ? _value.visit_type
          : visit_type // ignore: cast_nullable_to_non_nullable
              as String?,
      priority: freezed == priority
          ? _value.priority
          : priority // ignore: cast_nullable_to_non_nullable
              as String?,
      patient_name: freezed == patient_name
          ? _value.patient_name
          : patient_name // ignore: cast_nullable_to_non_nullable
              as String?,
      patient_mobile: freezed == patient_mobile
          ? _value.patient_mobile
          : patient_mobile // ignore: cast_nullable_to_non_nullable
              as String?,
      patient_age: freezed == patient_age
          ? _value.patient_age
          : patient_age // ignore: cast_nullable_to_non_nullable
              as int?,
      patient_gender: freezed == patient_gender
          ? _value.patient_gender
          : patient_gender // ignore: cast_nullable_to_non_nullable
              as String?,
      notes: freezed == notes
          ? _value.notes
          : notes // ignore: cast_nullable_to_non_nullable
              as String?,
      created_at: freezed == created_at
          ? _value.created_at
          : created_at // ignore: cast_nullable_to_non_nullable
              as DateTime?,
      specialist_speciality: freezed == specialist_speciality
          ? _value.specialist_speciality
          : specialist_speciality // ignore: cast_nullable_to_non_nullable
              as String?,
      specialist_address: freezed == specialist_address
          ? _value.specialist_address
          : specialist_address // ignore: cast_nullable_to_non_nullable
              as String?,
      specialist_experience: freezed == specialist_experience
          ? _value.specialist_experience
          : specialist_experience // ignore: cast_nullable_to_non_nullable
              as int?,
      hospital_address: freezed == hospital_address
          ? _value.hospital_address
          : hospital_address // ignore: cast_nullable_to_non_nullable
              as String?,
      hospital_city: freezed == hospital_city
          ? _value.hospital_city
          : hospital_city // ignore: cast_nullable_to_non_nullable
              as String?,
      specialist_details: freezed == specialist_details
          ? _value.specialist_details
          : specialist_details // ignore: cast_nullable_to_non_nullable
              as Map<String, dynamic>?,
      hospital_details: freezed == hospital_details
          ? _value.hospital_details
          : hospital_details // ignore: cast_nullable_to_non_nullable
              as Map<String, dynamic>?,
      files: freezed == files
          ? _value.files
          : files // ignore: cast_nullable_to_non_nullable
              as List<Map<String, dynamic>>?,
      accepted_at: freezed == accepted_at
          ? _value.accepted_at
          : accepted_at // ignore: cast_nullable_to_non_nullable
              as DateTime?,
      consulted_at: freezed == consulted_at
          ? _value.consulted_at
          : consulted_at // ignore: cast_nullable_to_non_nullable
              as DateTime?,
      closed_at: freezed == closed_at
          ? _value.closed_at
          : closed_at // ignore: cast_nullable_to_non_nullable
              as DateTime?,
    ) as $Val);
  }
}

/// @nodoc
abstract class _$$ReferralImplCopyWith<$Res>
    implements $ReferralCopyWith<$Res> {
  factory _$$ReferralImplCopyWith(
          _$ReferralImpl value, $Res Function(_$ReferralImpl) then) =
      __$$ReferralImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call(
      {int id,
      String? lead_code,
      String? referral_type,
      String? status,
      int? gp_id,
      @JsonKey(readValue: _readSpecialistId) int? specialist_id,
      @JsonKey(readValue: _readHospitalId) int? hospital_id,
      @JsonKey(readValue: _readSpecialistName) String? specialist_name,
      @JsonKey(readValue: _readHospitalName) String? hospital_name,
      @JsonKey(readValue: _readDiagnosticCenterId) int? diagnostic_center_id,
      @JsonKey(readValue: _readDiagnosticCenterName)
      String? diagnostic_center_name,
      String? department,
      @JsonKey(name: 'appointment_type') String? visit_type,
      String? priority,
      String? patient_name,
      String? patient_mobile,
      @JsonKey(readValue: _readPatientAge) int? patient_age,
      String? patient_gender,
      @JsonKey(name: 'case_summary') String? notes,
      DateTime? created_at,
      @JsonKey(readValue: _readSpecialistSpeciality)
      String? specialist_speciality,
      @JsonKey(readValue: _readSpecialistAddress) String? specialist_address,
      @JsonKey(readValue: _readSpecialistExperience) int? specialist_experience,
      @JsonKey(readValue: _readHospitalAddress) String? hospital_address,
      @JsonKey(readValue: _readHospitalCity) String? hospital_city,
      @JsonKey(name: 'specialist') Map<String, dynamic>? specialist_details,
      @JsonKey(name: 'hospital') Map<String, dynamic>? hospital_details,
      @JsonKey(name: 'files') List<Map<String, dynamic>>? files,
      DateTime? accepted_at,
      DateTime? consulted_at,
      DateTime? closed_at});
}

/// @nodoc
class __$$ReferralImplCopyWithImpl<$Res>
    extends _$ReferralCopyWithImpl<$Res, _$ReferralImpl>
    implements _$$ReferralImplCopyWith<$Res> {
  __$$ReferralImplCopyWithImpl(
      _$ReferralImpl _value, $Res Function(_$ReferralImpl) _then)
      : super(_value, _then);

  /// Create a copy of Referral
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? lead_code = freezed,
    Object? referral_type = freezed,
    Object? status = freezed,
    Object? gp_id = freezed,
    Object? specialist_id = freezed,
    Object? hospital_id = freezed,
    Object? specialist_name = freezed,
    Object? hospital_name = freezed,
    Object? diagnostic_center_id = freezed,
    Object? diagnostic_center_name = freezed,
    Object? department = freezed,
    Object? visit_type = freezed,
    Object? priority = freezed,
    Object? patient_name = freezed,
    Object? patient_mobile = freezed,
    Object? patient_age = freezed,
    Object? patient_gender = freezed,
    Object? notes = freezed,
    Object? created_at = freezed,
    Object? specialist_speciality = freezed,
    Object? specialist_address = freezed,
    Object? specialist_experience = freezed,
    Object? hospital_address = freezed,
    Object? hospital_city = freezed,
    Object? specialist_details = freezed,
    Object? hospital_details = freezed,
    Object? files = freezed,
    Object? accepted_at = freezed,
    Object? consulted_at = freezed,
    Object? closed_at = freezed,
  }) {
    return _then(_$ReferralImpl(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int,
      lead_code: freezed == lead_code
          ? _value.lead_code
          : lead_code // ignore: cast_nullable_to_non_nullable
              as String?,
      referral_type: freezed == referral_type
          ? _value.referral_type
          : referral_type // ignore: cast_nullable_to_non_nullable
              as String?,
      status: freezed == status
          ? _value.status
          : status // ignore: cast_nullable_to_non_nullable
              as String?,
      gp_id: freezed == gp_id
          ? _value.gp_id
          : gp_id // ignore: cast_nullable_to_non_nullable
              as int?,
      specialist_id: freezed == specialist_id
          ? _value.specialist_id
          : specialist_id // ignore: cast_nullable_to_non_nullable
              as int?,
      hospital_id: freezed == hospital_id
          ? _value.hospital_id
          : hospital_id // ignore: cast_nullable_to_non_nullable
              as int?,
      specialist_name: freezed == specialist_name
          ? _value.specialist_name
          : specialist_name // ignore: cast_nullable_to_non_nullable
              as String?,
      hospital_name: freezed == hospital_name
          ? _value.hospital_name
          : hospital_name // ignore: cast_nullable_to_non_nullable
              as String?,
      diagnostic_center_id: freezed == diagnostic_center_id
          ? _value.diagnostic_center_id
          : diagnostic_center_id // ignore: cast_nullable_to_non_nullable
              as int?,
      diagnostic_center_name: freezed == diagnostic_center_name
          ? _value.diagnostic_center_name
          : diagnostic_center_name // ignore: cast_nullable_to_non_nullable
              as String?,
      department: freezed == department
          ? _value.department
          : department // ignore: cast_nullable_to_non_nullable
              as String?,
      visit_type: freezed == visit_type
          ? _value.visit_type
          : visit_type // ignore: cast_nullable_to_non_nullable
              as String?,
      priority: freezed == priority
          ? _value.priority
          : priority // ignore: cast_nullable_to_non_nullable
              as String?,
      patient_name: freezed == patient_name
          ? _value.patient_name
          : patient_name // ignore: cast_nullable_to_non_nullable
              as String?,
      patient_mobile: freezed == patient_mobile
          ? _value.patient_mobile
          : patient_mobile // ignore: cast_nullable_to_non_nullable
              as String?,
      patient_age: freezed == patient_age
          ? _value.patient_age
          : patient_age // ignore: cast_nullable_to_non_nullable
              as int?,
      patient_gender: freezed == patient_gender
          ? _value.patient_gender
          : patient_gender // ignore: cast_nullable_to_non_nullable
              as String?,
      notes: freezed == notes
          ? _value.notes
          : notes // ignore: cast_nullable_to_non_nullable
              as String?,
      created_at: freezed == created_at
          ? _value.created_at
          : created_at // ignore: cast_nullable_to_non_nullable
              as DateTime?,
      specialist_speciality: freezed == specialist_speciality
          ? _value.specialist_speciality
          : specialist_speciality // ignore: cast_nullable_to_non_nullable
              as String?,
      specialist_address: freezed == specialist_address
          ? _value.specialist_address
          : specialist_address // ignore: cast_nullable_to_non_nullable
              as String?,
      specialist_experience: freezed == specialist_experience
          ? _value.specialist_experience
          : specialist_experience // ignore: cast_nullable_to_non_nullable
              as int?,
      hospital_address: freezed == hospital_address
          ? _value.hospital_address
          : hospital_address // ignore: cast_nullable_to_non_nullable
              as String?,
      hospital_city: freezed == hospital_city
          ? _value.hospital_city
          : hospital_city // ignore: cast_nullable_to_non_nullable
              as String?,
      specialist_details: freezed == specialist_details
          ? _value._specialist_details
          : specialist_details // ignore: cast_nullable_to_non_nullable
              as Map<String, dynamic>?,
      hospital_details: freezed == hospital_details
          ? _value._hospital_details
          : hospital_details // ignore: cast_nullable_to_non_nullable
              as Map<String, dynamic>?,
      files: freezed == files
          ? _value._files
          : files // ignore: cast_nullable_to_non_nullable
              as List<Map<String, dynamic>>?,
      accepted_at: freezed == accepted_at
          ? _value.accepted_at
          : accepted_at // ignore: cast_nullable_to_non_nullable
              as DateTime?,
      consulted_at: freezed == consulted_at
          ? _value.consulted_at
          : consulted_at // ignore: cast_nullable_to_non_nullable
              as DateTime?,
      closed_at: freezed == closed_at
          ? _value.closed_at
          : closed_at // ignore: cast_nullable_to_non_nullable
              as DateTime?,
    ));
  }
}

/// @nodoc
@JsonSerializable()
class _$ReferralImpl implements _Referral {
  const _$ReferralImpl(
      {required this.id,
      this.lead_code,
      this.referral_type,
      this.status,
      this.gp_id,
      @JsonKey(readValue: _readSpecialistId) this.specialist_id,
      @JsonKey(readValue: _readHospitalId) this.hospital_id,
      @JsonKey(readValue: _readSpecialistName) this.specialist_name,
      @JsonKey(readValue: _readHospitalName) this.hospital_name,
      @JsonKey(readValue: _readDiagnosticCenterId) this.diagnostic_center_id,
      @JsonKey(readValue: _readDiagnosticCenterName)
      this.diagnostic_center_name,
      this.department,
      @JsonKey(name: 'appointment_type') this.visit_type,
      this.priority,
      this.patient_name,
      this.patient_mobile,
      @JsonKey(readValue: _readPatientAge) this.patient_age,
      this.patient_gender,
      @JsonKey(name: 'case_summary') this.notes,
      this.created_at,
      @JsonKey(readValue: _readSpecialistSpeciality) this.specialist_speciality,
      @JsonKey(readValue: _readSpecialistAddress) this.specialist_address,
      @JsonKey(readValue: _readSpecialistExperience) this.specialist_experience,
      @JsonKey(readValue: _readHospitalAddress) this.hospital_address,
      @JsonKey(readValue: _readHospitalCity) this.hospital_city,
      @JsonKey(name: 'specialist')
      final Map<String, dynamic>? specialist_details,
      @JsonKey(name: 'hospital') final Map<String, dynamic>? hospital_details,
      @JsonKey(name: 'files') final List<Map<String, dynamic>>? files,
      this.accepted_at,
      this.consulted_at,
      this.closed_at})
      : _specialist_details = specialist_details,
        _hospital_details = hospital_details,
        _files = files;

  factory _$ReferralImpl.fromJson(Map<String, dynamic> json) =>
      _$$ReferralImplFromJson(json);

  @override
  final int id;
  @override
  final String? lead_code;
  @override
  final String? referral_type;
  @override
  final String? status;
  @override
  final int? gp_id;
  @override
  @JsonKey(readValue: _readSpecialistId)
  final int? specialist_id;
  @override
  @JsonKey(readValue: _readHospitalId)
  final int? hospital_id;
  @override
  @JsonKey(readValue: _readSpecialistName)
  final String? specialist_name;
  @override
  @JsonKey(readValue: _readHospitalName)
  final String? hospital_name;
  @override
  @JsonKey(readValue: _readDiagnosticCenterId)
  final int? diagnostic_center_id;
  @override
  @JsonKey(readValue: _readDiagnosticCenterName)
  final String? diagnostic_center_name;
  @override
  final String? department;
  @override
  @JsonKey(name: 'appointment_type')
  final String? visit_type;
  @override
  final String? priority;
  @override
  final String? patient_name;
  @override
  final String? patient_mobile;
  @override
  @JsonKey(readValue: _readPatientAge)
  final int? patient_age;
  @override
  final String? patient_gender;
  @override
  @JsonKey(name: 'case_summary')
  final String? notes;
  @override
  final DateTime? created_at;
// Extended specialist details from ReferralResource
  @override
  @JsonKey(readValue: _readSpecialistSpeciality)
  final String? specialist_speciality;
  @override
  @JsonKey(readValue: _readSpecialistAddress)
  final String? specialist_address;
  @override
  @JsonKey(readValue: _readSpecialistExperience)
  final int? specialist_experience;
  @override
  @JsonKey(readValue: _readHospitalAddress)
  final String? hospital_address;
  @override
  @JsonKey(readValue: _readHospitalCity)
  final String? hospital_city;
// Specialist nested object (full details)
  final Map<String, dynamic>? _specialist_details;
// Specialist nested object (full details)
  @override
  @JsonKey(name: 'specialist')
  Map<String, dynamic>? get specialist_details {
    final value = _specialist_details;
    if (value == null) return null;
    if (_specialist_details is EqualUnmodifiableMapView)
      return _specialist_details;
    // ignore: implicit_dynamic_type
    return EqualUnmodifiableMapView(value);
  }

  final Map<String, dynamic>? _hospital_details;
  @override
  @JsonKey(name: 'hospital')
  Map<String, dynamic>? get hospital_details {
    final value = _hospital_details;
    if (value == null) return null;
    if (_hospital_details is EqualUnmodifiableMapView) return _hospital_details;
    // ignore: implicit_dynamic_type
    return EqualUnmodifiableMapView(value);
  }

// Files
  final List<Map<String, dynamic>>? _files;
// Files
  @override
  @JsonKey(name: 'files')
  List<Map<String, dynamic>>? get files {
    final value = _files;
    if (value == null) return null;
    if (_files is EqualUnmodifiableListView) return _files;
    // ignore: implicit_dynamic_type
    return EqualUnmodifiableListView(value);
  }

// Timeline
  @override
  final DateTime? accepted_at;
  @override
  final DateTime? consulted_at;
  @override
  final DateTime? closed_at;

  @override
  String toString() {
    return 'Referral(id: $id, lead_code: $lead_code, referral_type: $referral_type, status: $status, gp_id: $gp_id, specialist_id: $specialist_id, hospital_id: $hospital_id, specialist_name: $specialist_name, hospital_name: $hospital_name, diagnostic_center_id: $diagnostic_center_id, diagnostic_center_name: $diagnostic_center_name, department: $department, visit_type: $visit_type, priority: $priority, patient_name: $patient_name, patient_mobile: $patient_mobile, patient_age: $patient_age, patient_gender: $patient_gender, notes: $notes, created_at: $created_at, specialist_speciality: $specialist_speciality, specialist_address: $specialist_address, specialist_experience: $specialist_experience, hospital_address: $hospital_address, hospital_city: $hospital_city, specialist_details: $specialist_details, hospital_details: $hospital_details, files: $files, accepted_at: $accepted_at, consulted_at: $consulted_at, closed_at: $closed_at)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$ReferralImpl &&
            (identical(other.id, id) || other.id == id) &&
            (identical(other.lead_code, lead_code) ||
                other.lead_code == lead_code) &&
            (identical(other.referral_type, referral_type) ||
                other.referral_type == referral_type) &&
            (identical(other.status, status) || other.status == status) &&
            (identical(other.gp_id, gp_id) || other.gp_id == gp_id) &&
            (identical(other.specialist_id, specialist_id) ||
                other.specialist_id == specialist_id) &&
            (identical(other.hospital_id, hospital_id) ||
                other.hospital_id == hospital_id) &&
            (identical(other.specialist_name, specialist_name) ||
                other.specialist_name == specialist_name) &&
            (identical(other.hospital_name, hospital_name) ||
                other.hospital_name == hospital_name) &&
            (identical(other.diagnostic_center_id, diagnostic_center_id) ||
                other.diagnostic_center_id == diagnostic_center_id) &&
            (identical(other.diagnostic_center_name, diagnostic_center_name) ||
                other.diagnostic_center_name == diagnostic_center_name) &&
            (identical(other.department, department) ||
                other.department == department) &&
            (identical(other.visit_type, visit_type) ||
                other.visit_type == visit_type) &&
            (identical(other.priority, priority) ||
                other.priority == priority) &&
            (identical(other.patient_name, patient_name) ||
                other.patient_name == patient_name) &&
            (identical(other.patient_mobile, patient_mobile) ||
                other.patient_mobile == patient_mobile) &&
            (identical(other.patient_age, patient_age) ||
                other.patient_age == patient_age) &&
            (identical(other.patient_gender, patient_gender) ||
                other.patient_gender == patient_gender) &&
            (identical(other.notes, notes) || other.notes == notes) &&
            (identical(other.created_at, created_at) ||
                other.created_at == created_at) &&
            (identical(other.specialist_speciality, specialist_speciality) ||
                other.specialist_speciality == specialist_speciality) &&
            (identical(other.specialist_address, specialist_address) ||
                other.specialist_address == specialist_address) &&
            (identical(other.specialist_experience, specialist_experience) ||
                other.specialist_experience == specialist_experience) &&
            (identical(other.hospital_address, hospital_address) ||
                other.hospital_address == hospital_address) &&
            (identical(other.hospital_city, hospital_city) ||
                other.hospital_city == hospital_city) &&
            const DeepCollectionEquality()
                .equals(other._specialist_details, _specialist_details) &&
            const DeepCollectionEquality()
                .equals(other._hospital_details, _hospital_details) &&
            const DeepCollectionEquality().equals(other._files, _files) &&
            (identical(other.accepted_at, accepted_at) ||
                other.accepted_at == accepted_at) &&
            (identical(other.consulted_at, consulted_at) ||
                other.consulted_at == consulted_at) &&
            (identical(other.closed_at, closed_at) ||
                other.closed_at == closed_at));
  }

  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  int get hashCode => Object.hashAll([
        runtimeType,
        id,
        lead_code,
        referral_type,
        status,
        gp_id,
        specialist_id,
        hospital_id,
        specialist_name,
        hospital_name,
        diagnostic_center_id,
        diagnostic_center_name,
        department,
        visit_type,
        priority,
        patient_name,
        patient_mobile,
        patient_age,
        patient_gender,
        notes,
        created_at,
        specialist_speciality,
        specialist_address,
        specialist_experience,
        hospital_address,
        hospital_city,
        const DeepCollectionEquality().hash(_specialist_details),
        const DeepCollectionEquality().hash(_hospital_details),
        const DeepCollectionEquality().hash(_files),
        accepted_at,
        consulted_at,
        closed_at
      ]);

  /// Create a copy of Referral
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$ReferralImplCopyWith<_$ReferralImpl> get copyWith =>
      __$$ReferralImplCopyWithImpl<_$ReferralImpl>(this, _$identity);

  @override
  Map<String, dynamic> toJson() {
    return _$$ReferralImplToJson(
      this,
    );
  }
}

abstract class _Referral implements Referral {
  const factory _Referral(
      {required final int id,
      final String? lead_code,
      final String? referral_type,
      final String? status,
      final int? gp_id,
      @JsonKey(readValue: _readSpecialistId) final int? specialist_id,
      @JsonKey(readValue: _readHospitalId) final int? hospital_id,
      @JsonKey(readValue: _readSpecialistName) final String? specialist_name,
      @JsonKey(readValue: _readHospitalName) final String? hospital_name,
      @JsonKey(readValue: _readDiagnosticCenterId)
      final int? diagnostic_center_id,
      @JsonKey(readValue: _readDiagnosticCenterName)
      final String? diagnostic_center_name,
      final String? department,
      @JsonKey(name: 'appointment_type') final String? visit_type,
      final String? priority,
      final String? patient_name,
      final String? patient_mobile,
      @JsonKey(readValue: _readPatientAge) final int? patient_age,
      final String? patient_gender,
      @JsonKey(name: 'case_summary') final String? notes,
      final DateTime? created_at,
      @JsonKey(readValue: _readSpecialistSpeciality)
      final String? specialist_speciality,
      @JsonKey(readValue: _readSpecialistAddress)
      final String? specialist_address,
      @JsonKey(readValue: _readSpecialistExperience)
      final int? specialist_experience,
      @JsonKey(readValue: _readHospitalAddress) final String? hospital_address,
      @JsonKey(readValue: _readHospitalCity) final String? hospital_city,
      @JsonKey(name: 'specialist')
      final Map<String, dynamic>? specialist_details,
      @JsonKey(name: 'hospital') final Map<String, dynamic>? hospital_details,
      @JsonKey(name: 'files') final List<Map<String, dynamic>>? files,
      final DateTime? accepted_at,
      final DateTime? consulted_at,
      final DateTime? closed_at}) = _$ReferralImpl;

  factory _Referral.fromJson(Map<String, dynamic> json) =
      _$ReferralImpl.fromJson;

  @override
  int get id;
  @override
  String? get lead_code;
  @override
  String? get referral_type;
  @override
  String? get status;
  @override
  int? get gp_id;
  @override
  @JsonKey(readValue: _readSpecialistId)
  int? get specialist_id;
  @override
  @JsonKey(readValue: _readHospitalId)
  int? get hospital_id;
  @override
  @JsonKey(readValue: _readSpecialistName)
  String? get specialist_name;
  @override
  @JsonKey(readValue: _readHospitalName)
  String? get hospital_name;
  @override
  @JsonKey(readValue: _readDiagnosticCenterId)
  int? get diagnostic_center_id;
  @override
  @JsonKey(readValue: _readDiagnosticCenterName)
  String? get diagnostic_center_name;
  @override
  String? get department;
  @override
  @JsonKey(name: 'appointment_type')
  String? get visit_type;
  @override
  String? get priority;
  @override
  String? get patient_name;
  @override
  String? get patient_mobile;
  @override
  @JsonKey(readValue: _readPatientAge)
  int? get patient_age;
  @override
  String? get patient_gender;
  @override
  @JsonKey(name: 'case_summary')
  String? get notes;
  @override
  DateTime? get created_at; // Extended specialist details from ReferralResource
  @override
  @JsonKey(readValue: _readSpecialistSpeciality)
  String? get specialist_speciality;
  @override
  @JsonKey(readValue: _readSpecialistAddress)
  String? get specialist_address;
  @override
  @JsonKey(readValue: _readSpecialistExperience)
  int? get specialist_experience;
  @override
  @JsonKey(readValue: _readHospitalAddress)
  String? get hospital_address;
  @override
  @JsonKey(readValue: _readHospitalCity)
  String? get hospital_city; // Specialist nested object (full details)
  @override
  @JsonKey(name: 'specialist')
  Map<String, dynamic>? get specialist_details;
  @override
  @JsonKey(name: 'hospital')
  Map<String, dynamic>? get hospital_details; // Files
  @override
  @JsonKey(name: 'files')
  List<Map<String, dynamic>>? get files; // Timeline
  @override
  DateTime? get accepted_at;
  @override
  DateTime? get consulted_at;
  @override
  DateTime? get closed_at;

  /// Create a copy of Referral
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$ReferralImplCopyWith<_$ReferralImpl> get copyWith =>
      throw _privateConstructorUsedError;
}
