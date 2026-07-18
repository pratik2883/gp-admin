// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'user.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
    'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models');

User _$UserFromJson(Map<String, dynamic> json) {
  return _User.fromJson(json);
}

/// @nodoc
mixin _$User {
  int get id => throw _privateConstructorUsedError;
  String get name => throw _privateConstructorUsedError;
  String? get email => throw _privateConstructorUsedError;
  String get mobile => throw _privateConstructorUsedError;
  String get role => throw _privateConstructorUsedError;
  int? get gp_id => throw _privateConstructorUsedError;
  int? get specialist_id => throw _privateConstructorUsedError;
  @JsonKey(name: 'registration_number')
  String? get registrationNumber => throw _privateConstructorUsedError;
  String? get speciality => throw _privateConstructorUsedError;
  String? get designation => throw _privateConstructorUsedError;
  String? get address => throw _privateConstructorUsedError;
  String? get city => throw _privateConstructorUsedError;
  @JsonKey(name: 'clinic_name')
  String? get clinicName => throw _privateConstructorUsedError;
  @JsonKey(name: 'default_location_id')
  int? get defaultLocationId => throw _privateConstructorUsedError;
  @JsonKey(name: 'default_location_name')
  String? get defaultLocationName => throw _privateConstructorUsedError;

  /// Serializes this User to a JSON map.
  Map<String, dynamic> toJson() => throw _privateConstructorUsedError;

  /// Create a copy of User
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $UserCopyWith<User> get copyWith => throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $UserCopyWith<$Res> {
  factory $UserCopyWith(User value, $Res Function(User) then) =
      _$UserCopyWithImpl<$Res, User>;
  @useResult
  $Res call(
      {int id,
      String name,
      String? email,
      String mobile,
      String role,
      int? gp_id,
      int? specialist_id,
      @JsonKey(name: 'registration_number') String? registrationNumber,
      String? speciality,
      String? designation,
      String? address,
      String? city,
      @JsonKey(name: 'clinic_name') String? clinicName,
      @JsonKey(name: 'default_location_id') int? defaultLocationId,
      @JsonKey(name: 'default_location_name') String? defaultLocationName});
}

/// @nodoc
class _$UserCopyWithImpl<$Res, $Val extends User>
    implements $UserCopyWith<$Res> {
  _$UserCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of User
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? name = null,
    Object? email = freezed,
    Object? mobile = null,
    Object? role = null,
    Object? gp_id = freezed,
    Object? specialist_id = freezed,
    Object? registrationNumber = freezed,
    Object? speciality = freezed,
    Object? designation = freezed,
    Object? address = freezed,
    Object? city = freezed,
    Object? clinicName = freezed,
    Object? defaultLocationId = freezed,
    Object? defaultLocationName = freezed,
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
      email: freezed == email
          ? _value.email
          : email // ignore: cast_nullable_to_non_nullable
              as String?,
      mobile: null == mobile
          ? _value.mobile
          : mobile // ignore: cast_nullable_to_non_nullable
              as String,
      role: null == role
          ? _value.role
          : role // ignore: cast_nullable_to_non_nullable
              as String,
      gp_id: freezed == gp_id
          ? _value.gp_id
          : gp_id // ignore: cast_nullable_to_non_nullable
              as int?,
      specialist_id: freezed == specialist_id
          ? _value.specialist_id
          : specialist_id // ignore: cast_nullable_to_non_nullable
              as int?,
      registrationNumber: freezed == registrationNumber
          ? _value.registrationNumber
          : registrationNumber // ignore: cast_nullable_to_non_nullable
              as String?,
      speciality: freezed == speciality
          ? _value.speciality
          : speciality // ignore: cast_nullable_to_non_nullable
              as String?,
      designation: freezed == designation
          ? _value.designation
          : designation // ignore: cast_nullable_to_non_nullable
              as String?,
      address: freezed == address
          ? _value.address
          : address // ignore: cast_nullable_to_non_nullable
              as String?,
      city: freezed == city
          ? _value.city
          : city // ignore: cast_nullable_to_non_nullable
              as String?,
      clinicName: freezed == clinicName
          ? _value.clinicName
          : clinicName // ignore: cast_nullable_to_non_nullable
              as String?,
      defaultLocationId: freezed == defaultLocationId
          ? _value.defaultLocationId
          : defaultLocationId // ignore: cast_nullable_to_non_nullable
              as int?,
      defaultLocationName: freezed == defaultLocationName
          ? _value.defaultLocationName
          : defaultLocationName // ignore: cast_nullable_to_non_nullable
              as String?,
    ) as $Val);
  }
}

/// @nodoc
abstract class _$$UserImplCopyWith<$Res> implements $UserCopyWith<$Res> {
  factory _$$UserImplCopyWith(
          _$UserImpl value, $Res Function(_$UserImpl) then) =
      __$$UserImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call(
      {int id,
      String name,
      String? email,
      String mobile,
      String role,
      int? gp_id,
      int? specialist_id,
      @JsonKey(name: 'registration_number') String? registrationNumber,
      String? speciality,
      String? designation,
      String? address,
      String? city,
      @JsonKey(name: 'clinic_name') String? clinicName,
      @JsonKey(name: 'default_location_id') int? defaultLocationId,
      @JsonKey(name: 'default_location_name') String? defaultLocationName});
}

/// @nodoc
class __$$UserImplCopyWithImpl<$Res>
    extends _$UserCopyWithImpl<$Res, _$UserImpl>
    implements _$$UserImplCopyWith<$Res> {
  __$$UserImplCopyWithImpl(_$UserImpl _value, $Res Function(_$UserImpl) _then)
      : super(_value, _then);

  /// Create a copy of User
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? name = null,
    Object? email = freezed,
    Object? mobile = null,
    Object? role = null,
    Object? gp_id = freezed,
    Object? specialist_id = freezed,
    Object? registrationNumber = freezed,
    Object? speciality = freezed,
    Object? designation = freezed,
    Object? address = freezed,
    Object? city = freezed,
    Object? clinicName = freezed,
    Object? defaultLocationId = freezed,
    Object? defaultLocationName = freezed,
  }) {
    return _then(_$UserImpl(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int,
      name: null == name
          ? _value.name
          : name // ignore: cast_nullable_to_non_nullable
              as String,
      email: freezed == email
          ? _value.email
          : email // ignore: cast_nullable_to_non_nullable
              as String?,
      mobile: null == mobile
          ? _value.mobile
          : mobile // ignore: cast_nullable_to_non_nullable
              as String,
      role: null == role
          ? _value.role
          : role // ignore: cast_nullable_to_non_nullable
              as String,
      gp_id: freezed == gp_id
          ? _value.gp_id
          : gp_id // ignore: cast_nullable_to_non_nullable
              as int?,
      specialist_id: freezed == specialist_id
          ? _value.specialist_id
          : specialist_id // ignore: cast_nullable_to_non_nullable
              as int?,
      registrationNumber: freezed == registrationNumber
          ? _value.registrationNumber
          : registrationNumber // ignore: cast_nullable_to_non_nullable
              as String?,
      speciality: freezed == speciality
          ? _value.speciality
          : speciality // ignore: cast_nullable_to_non_nullable
              as String?,
      designation: freezed == designation
          ? _value.designation
          : designation // ignore: cast_nullable_to_non_nullable
              as String?,
      address: freezed == address
          ? _value.address
          : address // ignore: cast_nullable_to_non_nullable
              as String?,
      city: freezed == city
          ? _value.city
          : city // ignore: cast_nullable_to_non_nullable
              as String?,
      clinicName: freezed == clinicName
          ? _value.clinicName
          : clinicName // ignore: cast_nullable_to_non_nullable
              as String?,
      defaultLocationId: freezed == defaultLocationId
          ? _value.defaultLocationId
          : defaultLocationId // ignore: cast_nullable_to_non_nullable
              as int?,
      defaultLocationName: freezed == defaultLocationName
          ? _value.defaultLocationName
          : defaultLocationName // ignore: cast_nullable_to_non_nullable
              as String?,
    ));
  }
}

/// @nodoc
@JsonSerializable()
class _$UserImpl implements _User {
  const _$UserImpl(
      {required this.id,
      required this.name,
      this.email,
      required this.mobile,
      required this.role,
      this.gp_id,
      this.specialist_id,
      @JsonKey(name: 'registration_number') this.registrationNumber,
      this.speciality,
      this.designation,
      this.address,
      this.city,
      @JsonKey(name: 'clinic_name') this.clinicName,
      @JsonKey(name: 'default_location_id') this.defaultLocationId,
      @JsonKey(name: 'default_location_name') this.defaultLocationName});

  factory _$UserImpl.fromJson(Map<String, dynamic> json) =>
      _$$UserImplFromJson(json);

  @override
  final int id;
  @override
  final String name;
  @override
  final String? email;
  @override
  final String mobile;
  @override
  final String role;
  @override
  final int? gp_id;
  @override
  final int? specialist_id;
  @override
  @JsonKey(name: 'registration_number')
  final String? registrationNumber;
  @override
  final String? speciality;
  @override
  final String? designation;
  @override
  final String? address;
  @override
  final String? city;
  @override
  @JsonKey(name: 'clinic_name')
  final String? clinicName;
  @override
  @JsonKey(name: 'default_location_id')
  final int? defaultLocationId;
  @override
  @JsonKey(name: 'default_location_name')
  final String? defaultLocationName;

  @override
  String toString() {
    return 'User(id: $id, name: $name, email: $email, mobile: $mobile, role: $role, gp_id: $gp_id, specialist_id: $specialist_id, registrationNumber: $registrationNumber, speciality: $speciality, designation: $designation, address: $address, city: $city, clinicName: $clinicName, defaultLocationId: $defaultLocationId, defaultLocationName: $defaultLocationName)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$UserImpl &&
            (identical(other.id, id) || other.id == id) &&
            (identical(other.name, name) || other.name == name) &&
            (identical(other.email, email) || other.email == email) &&
            (identical(other.mobile, mobile) || other.mobile == mobile) &&
            (identical(other.role, role) || other.role == role) &&
            (identical(other.gp_id, gp_id) || other.gp_id == gp_id) &&
            (identical(other.specialist_id, specialist_id) ||
                other.specialist_id == specialist_id) &&
            (identical(other.registrationNumber, registrationNumber) ||
                other.registrationNumber == registrationNumber) &&
            (identical(other.speciality, speciality) ||
                other.speciality == speciality) &&
            (identical(other.designation, designation) ||
                other.designation == designation) &&
            (identical(other.address, address) || other.address == address) &&
            (identical(other.city, city) || other.city == city) &&
            (identical(other.clinicName, clinicName) ||
                other.clinicName == clinicName) &&
            (identical(other.defaultLocationId, defaultLocationId) ||
                other.defaultLocationId == defaultLocationId) &&
            (identical(other.defaultLocationName, defaultLocationName) ||
                other.defaultLocationName == defaultLocationName));
  }

  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  int get hashCode => Object.hash(
      runtimeType,
      id,
      name,
      email,
      mobile,
      role,
      gp_id,
      specialist_id,
      registrationNumber,
      speciality,
      designation,
      address,
      city,
      clinicName,
      defaultLocationId,
      defaultLocationName);

  /// Create a copy of User
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$UserImplCopyWith<_$UserImpl> get copyWith =>
      __$$UserImplCopyWithImpl<_$UserImpl>(this, _$identity);

  @override
  Map<String, dynamic> toJson() {
    return _$$UserImplToJson(
      this,
    );
  }
}

abstract class _User implements User {
  const factory _User(
      {required final int id,
      required final String name,
      final String? email,
      required final String mobile,
      required final String role,
      final int? gp_id,
      final int? specialist_id,
      @JsonKey(name: 'registration_number') final String? registrationNumber,
      final String? speciality,
      final String? designation,
      final String? address,
      final String? city,
      @JsonKey(name: 'clinic_name') final String? clinicName,
      @JsonKey(name: 'default_location_id') final int? defaultLocationId,
      @JsonKey(name: 'default_location_name')
      final String? defaultLocationName}) = _$UserImpl;

  factory _User.fromJson(Map<String, dynamic> json) = _$UserImpl.fromJson;

  @override
  int get id;
  @override
  String get name;
  @override
  String? get email;
  @override
  String get mobile;
  @override
  String get role;
  @override
  int? get gp_id;
  @override
  int? get specialist_id;
  @override
  @JsonKey(name: 'registration_number')
  String? get registrationNumber;
  @override
  String? get speciality;
  @override
  String? get designation;
  @override
  String? get address;
  @override
  String? get city;
  @override
  @JsonKey(name: 'clinic_name')
  String? get clinicName;
  @override
  @JsonKey(name: 'default_location_id')
  int? get defaultLocationId;
  @override
  @JsonKey(name: 'default_location_name')
  String? get defaultLocationName;

  /// Create a copy of User
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$UserImplCopyWith<_$UserImpl> get copyWith =>
      throw _privateConstructorUsedError;
}
