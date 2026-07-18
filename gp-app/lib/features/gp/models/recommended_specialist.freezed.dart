// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'recommended_specialist.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
    'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models');

RecommendedSpecialist _$RecommendedSpecialistFromJson(
    Map<String, dynamic> json) {
  return _RecommendedSpecialist.fromJson(json);
}

/// @nodoc
mixin _$RecommendedSpecialist {
  int get id => throw _privateConstructorUsedError;
  String get name => throw _privateConstructorUsedError;
  String get speciality => throw _privateConstructorUsedError;
  @JsonKey(name: 'area_name')
  String get areaName => throw _privateConstructorUsedError;
  @JsonKey(name: 'is_premium')
  bool get isPremium => throw _privateConstructorUsedError;
  @JsonKey(name: 'match_type')
  String? get matchType => throw _privateConstructorUsedError;
  @JsonKey(name: 'location_id')
  int? get locationId => throw _privateConstructorUsedError;
  @JsonKey(name: 'category_code')
  String? get categoryCode => throw _privateConstructorUsedError;
  @JsonKey(name: 'hospital_id')
  int? get hospitalId => throw _privateConstructorUsedError;
  @JsonKey(name: 'hospital_name')
  String? get hospitalName => throw _privateConstructorUsedError;
  @JsonKey(name: 'clinic_address')
  String? get clinicAddress => throw _privateConstructorUsedError;
  @JsonKey(name: 'years_of_experience')
  int? get yearsOfExperience => throw _privateConstructorUsedError;
  List<String>? get languages => throw _privateConstructorUsedError;
  @JsonKey(name: 'consultation_flags')
  Map<String, dynamic>? get consultationFlags =>
      throw _privateConstructorUsedError;
  @JsonKey(name: 'is_super_specialist')
  bool get isSuperSpecialist => throw _privateConstructorUsedError;
  String? get department => throw _privateConstructorUsedError;
  String? get role => throw _privateConstructorUsedError;

  /// Serializes this RecommendedSpecialist to a JSON map.
  Map<String, dynamic> toJson() => throw _privateConstructorUsedError;

  /// Create a copy of RecommendedSpecialist
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $RecommendedSpecialistCopyWith<RecommendedSpecialist> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $RecommendedSpecialistCopyWith<$Res> {
  factory $RecommendedSpecialistCopyWith(RecommendedSpecialist value,
          $Res Function(RecommendedSpecialist) then) =
      _$RecommendedSpecialistCopyWithImpl<$Res, RecommendedSpecialist>;
  @useResult
  $Res call(
      {int id,
      String name,
      String speciality,
      @JsonKey(name: 'area_name') String areaName,
      @JsonKey(name: 'is_premium') bool isPremium,
      @JsonKey(name: 'match_type') String? matchType,
      @JsonKey(name: 'location_id') int? locationId,
      @JsonKey(name: 'category_code') String? categoryCode,
      @JsonKey(name: 'hospital_id') int? hospitalId,
      @JsonKey(name: 'hospital_name') String? hospitalName,
      @JsonKey(name: 'clinic_address') String? clinicAddress,
      @JsonKey(name: 'years_of_experience') int? yearsOfExperience,
      List<String>? languages,
      @JsonKey(name: 'consultation_flags')
      Map<String, dynamic>? consultationFlags,
      @JsonKey(name: 'is_super_specialist') bool isSuperSpecialist,
      String? department,
      String? role});
}

/// @nodoc
class _$RecommendedSpecialistCopyWithImpl<$Res,
        $Val extends RecommendedSpecialist>
    implements $RecommendedSpecialistCopyWith<$Res> {
  _$RecommendedSpecialistCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of RecommendedSpecialist
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? name = null,
    Object? speciality = null,
    Object? areaName = null,
    Object? isPremium = null,
    Object? matchType = freezed,
    Object? locationId = freezed,
    Object? categoryCode = freezed,
    Object? hospitalId = freezed,
    Object? hospitalName = freezed,
    Object? clinicAddress = freezed,
    Object? yearsOfExperience = freezed,
    Object? languages = freezed,
    Object? consultationFlags = freezed,
    Object? isSuperSpecialist = null,
    Object? department = freezed,
    Object? role = freezed,
  }) {
    return _then(_value.copyWith(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int,
      name: null == name
          ? _value.name
          : name // ignore: cast_nullable_to_non_nullable
              as String,
      speciality: null == speciality
          ? _value.speciality
          : speciality // ignore: cast_nullable_to_non_nullable
              as String,
      areaName: null == areaName
          ? _value.areaName
          : areaName // ignore: cast_nullable_to_non_nullable
              as String,
      isPremium: null == isPremium
          ? _value.isPremium
          : isPremium // ignore: cast_nullable_to_non_nullable
              as bool,
      matchType: freezed == matchType
          ? _value.matchType
          : matchType // ignore: cast_nullable_to_non_nullable
              as String?,
      locationId: freezed == locationId
          ? _value.locationId
          : locationId // ignore: cast_nullable_to_non_nullable
              as int?,
      categoryCode: freezed == categoryCode
          ? _value.categoryCode
          : categoryCode // ignore: cast_nullable_to_non_nullable
              as String?,
      hospitalId: freezed == hospitalId
          ? _value.hospitalId
          : hospitalId // ignore: cast_nullable_to_non_nullable
              as int?,
      hospitalName: freezed == hospitalName
          ? _value.hospitalName
          : hospitalName // ignore: cast_nullable_to_non_nullable
              as String?,
      clinicAddress: freezed == clinicAddress
          ? _value.clinicAddress
          : clinicAddress // ignore: cast_nullable_to_non_nullable
              as String?,
      yearsOfExperience: freezed == yearsOfExperience
          ? _value.yearsOfExperience
          : yearsOfExperience // ignore: cast_nullable_to_non_nullable
              as int?,
      languages: freezed == languages
          ? _value.languages
          : languages // ignore: cast_nullable_to_non_nullable
              as List<String>?,
      consultationFlags: freezed == consultationFlags
          ? _value.consultationFlags
          : consultationFlags // ignore: cast_nullable_to_non_nullable
              as Map<String, dynamic>?,
      isSuperSpecialist: null == isSuperSpecialist
          ? _value.isSuperSpecialist
          : isSuperSpecialist // ignore: cast_nullable_to_non_nullable
              as bool,
      department: freezed == department
          ? _value.department
          : department // ignore: cast_nullable_to_non_nullable
              as String?,
      role: freezed == role
          ? _value.role
          : role // ignore: cast_nullable_to_non_nullable
              as String?,
    ) as $Val);
  }
}

/// @nodoc
abstract class _$$RecommendedSpecialistImplCopyWith<$Res>
    implements $RecommendedSpecialistCopyWith<$Res> {
  factory _$$RecommendedSpecialistImplCopyWith(
          _$RecommendedSpecialistImpl value,
          $Res Function(_$RecommendedSpecialistImpl) then) =
      __$$RecommendedSpecialistImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call(
      {int id,
      String name,
      String speciality,
      @JsonKey(name: 'area_name') String areaName,
      @JsonKey(name: 'is_premium') bool isPremium,
      @JsonKey(name: 'match_type') String? matchType,
      @JsonKey(name: 'location_id') int? locationId,
      @JsonKey(name: 'category_code') String? categoryCode,
      @JsonKey(name: 'hospital_id') int? hospitalId,
      @JsonKey(name: 'hospital_name') String? hospitalName,
      @JsonKey(name: 'clinic_address') String? clinicAddress,
      @JsonKey(name: 'years_of_experience') int? yearsOfExperience,
      List<String>? languages,
      @JsonKey(name: 'consultation_flags')
      Map<String, dynamic>? consultationFlags,
      @JsonKey(name: 'is_super_specialist') bool isSuperSpecialist,
      String? department,
      String? role});
}

/// @nodoc
class __$$RecommendedSpecialistImplCopyWithImpl<$Res>
    extends _$RecommendedSpecialistCopyWithImpl<$Res,
        _$RecommendedSpecialistImpl>
    implements _$$RecommendedSpecialistImplCopyWith<$Res> {
  __$$RecommendedSpecialistImplCopyWithImpl(_$RecommendedSpecialistImpl _value,
      $Res Function(_$RecommendedSpecialistImpl) _then)
      : super(_value, _then);

  /// Create a copy of RecommendedSpecialist
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? name = null,
    Object? speciality = null,
    Object? areaName = null,
    Object? isPremium = null,
    Object? matchType = freezed,
    Object? locationId = freezed,
    Object? categoryCode = freezed,
    Object? hospitalId = freezed,
    Object? hospitalName = freezed,
    Object? clinicAddress = freezed,
    Object? yearsOfExperience = freezed,
    Object? languages = freezed,
    Object? consultationFlags = freezed,
    Object? isSuperSpecialist = null,
    Object? department = freezed,
    Object? role = freezed,
  }) {
    return _then(_$RecommendedSpecialistImpl(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int,
      name: null == name
          ? _value.name
          : name // ignore: cast_nullable_to_non_nullable
              as String,
      speciality: null == speciality
          ? _value.speciality
          : speciality // ignore: cast_nullable_to_non_nullable
              as String,
      areaName: null == areaName
          ? _value.areaName
          : areaName // ignore: cast_nullable_to_non_nullable
              as String,
      isPremium: null == isPremium
          ? _value.isPremium
          : isPremium // ignore: cast_nullable_to_non_nullable
              as bool,
      matchType: freezed == matchType
          ? _value.matchType
          : matchType // ignore: cast_nullable_to_non_nullable
              as String?,
      locationId: freezed == locationId
          ? _value.locationId
          : locationId // ignore: cast_nullable_to_non_nullable
              as int?,
      categoryCode: freezed == categoryCode
          ? _value.categoryCode
          : categoryCode // ignore: cast_nullable_to_non_nullable
              as String?,
      hospitalId: freezed == hospitalId
          ? _value.hospitalId
          : hospitalId // ignore: cast_nullable_to_non_nullable
              as int?,
      hospitalName: freezed == hospitalName
          ? _value.hospitalName
          : hospitalName // ignore: cast_nullable_to_non_nullable
              as String?,
      clinicAddress: freezed == clinicAddress
          ? _value.clinicAddress
          : clinicAddress // ignore: cast_nullable_to_non_nullable
              as String?,
      yearsOfExperience: freezed == yearsOfExperience
          ? _value.yearsOfExperience
          : yearsOfExperience // ignore: cast_nullable_to_non_nullable
              as int?,
      languages: freezed == languages
          ? _value._languages
          : languages // ignore: cast_nullable_to_non_nullable
              as List<String>?,
      consultationFlags: freezed == consultationFlags
          ? _value._consultationFlags
          : consultationFlags // ignore: cast_nullable_to_non_nullable
              as Map<String, dynamic>?,
      isSuperSpecialist: null == isSuperSpecialist
          ? _value.isSuperSpecialist
          : isSuperSpecialist // ignore: cast_nullable_to_non_nullable
              as bool,
      department: freezed == department
          ? _value.department
          : department // ignore: cast_nullable_to_non_nullable
              as String?,
      role: freezed == role
          ? _value.role
          : role // ignore: cast_nullable_to_non_nullable
              as String?,
    ));
  }
}

/// @nodoc
@JsonSerializable()
class _$RecommendedSpecialistImpl implements _RecommendedSpecialist {
  const _$RecommendedSpecialistImpl(
      {required this.id,
      required this.name,
      required this.speciality,
      @JsonKey(name: 'area_name') required this.areaName,
      @JsonKey(name: 'is_premium') this.isPremium = false,
      @JsonKey(name: 'match_type') this.matchType,
      @JsonKey(name: 'location_id') this.locationId,
      @JsonKey(name: 'category_code') this.categoryCode,
      @JsonKey(name: 'hospital_id') this.hospitalId,
      @JsonKey(name: 'hospital_name') this.hospitalName,
      @JsonKey(name: 'clinic_address') this.clinicAddress,
      @JsonKey(name: 'years_of_experience') this.yearsOfExperience,
      final List<String>? languages,
      @JsonKey(name: 'consultation_flags')
      final Map<String, dynamic>? consultationFlags,
      @JsonKey(name: 'is_super_specialist') this.isSuperSpecialist = false,
      this.department,
      this.role})
      : _languages = languages,
        _consultationFlags = consultationFlags;

  factory _$RecommendedSpecialistImpl.fromJson(Map<String, dynamic> json) =>
      _$$RecommendedSpecialistImplFromJson(json);

  @override
  final int id;
  @override
  final String name;
  @override
  final String speciality;
  @override
  @JsonKey(name: 'area_name')
  final String areaName;
  @override
  @JsonKey(name: 'is_premium')
  final bool isPremium;
  @override
  @JsonKey(name: 'match_type')
  final String? matchType;
  @override
  @JsonKey(name: 'location_id')
  final int? locationId;
  @override
  @JsonKey(name: 'category_code')
  final String? categoryCode;
  @override
  @JsonKey(name: 'hospital_id')
  final int? hospitalId;
  @override
  @JsonKey(name: 'hospital_name')
  final String? hospitalName;
  @override
  @JsonKey(name: 'clinic_address')
  final String? clinicAddress;
  @override
  @JsonKey(name: 'years_of_experience')
  final int? yearsOfExperience;
  final List<String>? _languages;
  @override
  List<String>? get languages {
    final value = _languages;
    if (value == null) return null;
    if (_languages is EqualUnmodifiableListView) return _languages;
    // ignore: implicit_dynamic_type
    return EqualUnmodifiableListView(value);
  }

  final Map<String, dynamic>? _consultationFlags;
  @override
  @JsonKey(name: 'consultation_flags')
  Map<String, dynamic>? get consultationFlags {
    final value = _consultationFlags;
    if (value == null) return null;
    if (_consultationFlags is EqualUnmodifiableMapView)
      return _consultationFlags;
    // ignore: implicit_dynamic_type
    return EqualUnmodifiableMapView(value);
  }

  @override
  @JsonKey(name: 'is_super_specialist')
  final bool isSuperSpecialist;
  @override
  final String? department;
  @override
  final String? role;

  @override
  String toString() {
    return 'RecommendedSpecialist(id: $id, name: $name, speciality: $speciality, areaName: $areaName, isPremium: $isPremium, matchType: $matchType, locationId: $locationId, categoryCode: $categoryCode, hospitalId: $hospitalId, hospitalName: $hospitalName, clinicAddress: $clinicAddress, yearsOfExperience: $yearsOfExperience, languages: $languages, consultationFlags: $consultationFlags, isSuperSpecialist: $isSuperSpecialist, department: $department, role: $role)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$RecommendedSpecialistImpl &&
            (identical(other.id, id) || other.id == id) &&
            (identical(other.name, name) || other.name == name) &&
            (identical(other.speciality, speciality) ||
                other.speciality == speciality) &&
            (identical(other.areaName, areaName) ||
                other.areaName == areaName) &&
            (identical(other.isPremium, isPremium) ||
                other.isPremium == isPremium) &&
            (identical(other.matchType, matchType) ||
                other.matchType == matchType) &&
            (identical(other.locationId, locationId) ||
                other.locationId == locationId) &&
            (identical(other.categoryCode, categoryCode) ||
                other.categoryCode == categoryCode) &&
            (identical(other.hospitalId, hospitalId) ||
                other.hospitalId == hospitalId) &&
            (identical(other.hospitalName, hospitalName) ||
                other.hospitalName == hospitalName) &&
            (identical(other.clinicAddress, clinicAddress) ||
                other.clinicAddress == clinicAddress) &&
            (identical(other.yearsOfExperience, yearsOfExperience) ||
                other.yearsOfExperience == yearsOfExperience) &&
            const DeepCollectionEquality()
                .equals(other._languages, _languages) &&
            const DeepCollectionEquality()
                .equals(other._consultationFlags, _consultationFlags) &&
            (identical(other.isSuperSpecialist, isSuperSpecialist) ||
                other.isSuperSpecialist == isSuperSpecialist) &&
            (identical(other.department, department) ||
                other.department == department) &&
            (identical(other.role, role) || other.role == role));
  }

  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  int get hashCode => Object.hash(
      runtimeType,
      id,
      name,
      speciality,
      areaName,
      isPremium,
      matchType,
      locationId,
      categoryCode,
      hospitalId,
      hospitalName,
      clinicAddress,
      yearsOfExperience,
      const DeepCollectionEquality().hash(_languages),
      const DeepCollectionEquality().hash(_consultationFlags),
      isSuperSpecialist,
      department,
      role);

  /// Create a copy of RecommendedSpecialist
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$RecommendedSpecialistImplCopyWith<_$RecommendedSpecialistImpl>
      get copyWith => __$$RecommendedSpecialistImplCopyWithImpl<
          _$RecommendedSpecialistImpl>(this, _$identity);

  @override
  Map<String, dynamic> toJson() {
    return _$$RecommendedSpecialistImplToJson(
      this,
    );
  }
}

abstract class _RecommendedSpecialist implements RecommendedSpecialist {
  const factory _RecommendedSpecialist(
      {required final int id,
      required final String name,
      required final String speciality,
      @JsonKey(name: 'area_name') required final String areaName,
      @JsonKey(name: 'is_premium') final bool isPremium,
      @JsonKey(name: 'match_type') final String? matchType,
      @JsonKey(name: 'location_id') final int? locationId,
      @JsonKey(name: 'category_code') final String? categoryCode,
      @JsonKey(name: 'hospital_id') final int? hospitalId,
      @JsonKey(name: 'hospital_name') final String? hospitalName,
      @JsonKey(name: 'clinic_address') final String? clinicAddress,
      @JsonKey(name: 'years_of_experience') final int? yearsOfExperience,
      final List<String>? languages,
      @JsonKey(name: 'consultation_flags')
      final Map<String, dynamic>? consultationFlags,
      @JsonKey(name: 'is_super_specialist') final bool isSuperSpecialist,
      final String? department,
      final String? role}) = _$RecommendedSpecialistImpl;

  factory _RecommendedSpecialist.fromJson(Map<String, dynamic> json) =
      _$RecommendedSpecialistImpl.fromJson;

  @override
  int get id;
  @override
  String get name;
  @override
  String get speciality;
  @override
  @JsonKey(name: 'area_name')
  String get areaName;
  @override
  @JsonKey(name: 'is_premium')
  bool get isPremium;
  @override
  @JsonKey(name: 'match_type')
  String? get matchType;
  @override
  @JsonKey(name: 'location_id')
  int? get locationId;
  @override
  @JsonKey(name: 'category_code')
  String? get categoryCode;
  @override
  @JsonKey(name: 'hospital_id')
  int? get hospitalId;
  @override
  @JsonKey(name: 'hospital_name')
  String? get hospitalName;
  @override
  @JsonKey(name: 'clinic_address')
  String? get clinicAddress;
  @override
  @JsonKey(name: 'years_of_experience')
  int? get yearsOfExperience;
  @override
  List<String>? get languages;
  @override
  @JsonKey(name: 'consultation_flags')
  Map<String, dynamic>? get consultationFlags;
  @override
  @JsonKey(name: 'is_super_specialist')
  bool get isSuperSpecialist;
  @override
  String? get department;
  @override
  String? get role;

  /// Create a copy of RecommendedSpecialist
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$RecommendedSpecialistImplCopyWith<_$RecommendedSpecialistImpl>
      get copyWith => throw _privateConstructorUsedError;
}
