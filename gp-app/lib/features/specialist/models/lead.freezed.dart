// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'lead.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
    'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models');

Lead _$LeadFromJson(Map<String, dynamic> json) {
  return _Lead.fromJson(json);
}

/// @nodoc
mixin _$Lead {
  int get id => throw _privateConstructorUsedError;
  String? get lead_code => throw _privateConstructorUsedError;
  String? get status => throw _privateConstructorUsedError;
  String? get gp_name => throw _privateConstructorUsedError;
  String? get patient_name => throw _privateConstructorUsedError;
  String? get patient_mobile => throw _privateConstructorUsedError;
  int? get patient_age => throw _privateConstructorUsedError;
  String? get patient_gender => throw _privateConstructorUsedError;
  String? get notes => throw _privateConstructorUsedError;
  String? get hospital_name => throw _privateConstructorUsedError;
  List<LeadAttachment> get attachments => throw _privateConstructorUsedError;
  DateTime? get created_at => throw _privateConstructorUsedError;

  /// Serializes this Lead to a JSON map.
  Map<String, dynamic> toJson() => throw _privateConstructorUsedError;

  /// Create a copy of Lead
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $LeadCopyWith<Lead> get copyWith => throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $LeadCopyWith<$Res> {
  factory $LeadCopyWith(Lead value, $Res Function(Lead) then) =
      _$LeadCopyWithImpl<$Res, Lead>;
  @useResult
  $Res call(
      {int id,
      String? lead_code,
      String? status,
      String? gp_name,
      String? patient_name,
      String? patient_mobile,
      int? patient_age,
      String? patient_gender,
      String? notes,
      String? hospital_name,
      List<LeadAttachment> attachments,
      DateTime? created_at});
}

/// @nodoc
class _$LeadCopyWithImpl<$Res, $Val extends Lead>
    implements $LeadCopyWith<$Res> {
  _$LeadCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of Lead
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? lead_code = freezed,
    Object? status = freezed,
    Object? gp_name = freezed,
    Object? patient_name = freezed,
    Object? patient_mobile = freezed,
    Object? patient_age = freezed,
    Object? patient_gender = freezed,
    Object? notes = freezed,
    Object? hospital_name = freezed,
    Object? attachments = null,
    Object? created_at = freezed,
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
      status: freezed == status
          ? _value.status
          : status // ignore: cast_nullable_to_non_nullable
              as String?,
      gp_name: freezed == gp_name
          ? _value.gp_name
          : gp_name // ignore: cast_nullable_to_non_nullable
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
      hospital_name: freezed == hospital_name
          ? _value.hospital_name
          : hospital_name // ignore: cast_nullable_to_non_nullable
              as String?,
      attachments: null == attachments
          ? _value.attachments
          : attachments // ignore: cast_nullable_to_non_nullable
              as List<LeadAttachment>,
      created_at: freezed == created_at
          ? _value.created_at
          : created_at // ignore: cast_nullable_to_non_nullable
              as DateTime?,
    ) as $Val);
  }
}

/// @nodoc
abstract class _$$LeadImplCopyWith<$Res> implements $LeadCopyWith<$Res> {
  factory _$$LeadImplCopyWith(
          _$LeadImpl value, $Res Function(_$LeadImpl) then) =
      __$$LeadImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call(
      {int id,
      String? lead_code,
      String? status,
      String? gp_name,
      String? patient_name,
      String? patient_mobile,
      int? patient_age,
      String? patient_gender,
      String? notes,
      String? hospital_name,
      List<LeadAttachment> attachments,
      DateTime? created_at});
}

/// @nodoc
class __$$LeadImplCopyWithImpl<$Res>
    extends _$LeadCopyWithImpl<$Res, _$LeadImpl>
    implements _$$LeadImplCopyWith<$Res> {
  __$$LeadImplCopyWithImpl(_$LeadImpl _value, $Res Function(_$LeadImpl) _then)
      : super(_value, _then);

  /// Create a copy of Lead
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? lead_code = freezed,
    Object? status = freezed,
    Object? gp_name = freezed,
    Object? patient_name = freezed,
    Object? patient_mobile = freezed,
    Object? patient_age = freezed,
    Object? patient_gender = freezed,
    Object? notes = freezed,
    Object? hospital_name = freezed,
    Object? attachments = null,
    Object? created_at = freezed,
  }) {
    return _then(_$LeadImpl(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int,
      lead_code: freezed == lead_code
          ? _value.lead_code
          : lead_code // ignore: cast_nullable_to_non_nullable
              as String?,
      status: freezed == status
          ? _value.status
          : status // ignore: cast_nullable_to_non_nullable
              as String?,
      gp_name: freezed == gp_name
          ? _value.gp_name
          : gp_name // ignore: cast_nullable_to_non_nullable
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
      hospital_name: freezed == hospital_name
          ? _value.hospital_name
          : hospital_name // ignore: cast_nullable_to_non_nullable
              as String?,
      attachments: null == attachments
          ? _value._attachments
          : attachments // ignore: cast_nullable_to_non_nullable
              as List<LeadAttachment>,
      created_at: freezed == created_at
          ? _value.created_at
          : created_at // ignore: cast_nullable_to_non_nullable
              as DateTime?,
    ));
  }
}

/// @nodoc
@JsonSerializable()
class _$LeadImpl implements _Lead {
  const _$LeadImpl(
      {required this.id,
      this.lead_code,
      this.status,
      this.gp_name,
      this.patient_name,
      this.patient_mobile,
      this.patient_age,
      this.patient_gender,
      this.notes,
      this.hospital_name,
      final List<LeadAttachment> attachments = const [],
      this.created_at})
      : _attachments = attachments;

  factory _$LeadImpl.fromJson(Map<String, dynamic> json) =>
      _$$LeadImplFromJson(json);

  @override
  final int id;
  @override
  final String? lead_code;
  @override
  final String? status;
  @override
  final String? gp_name;
  @override
  final String? patient_name;
  @override
  final String? patient_mobile;
  @override
  final int? patient_age;
  @override
  final String? patient_gender;
  @override
  final String? notes;
  @override
  final String? hospital_name;
  final List<LeadAttachment> _attachments;
  @override
  @JsonKey()
  List<LeadAttachment> get attachments {
    if (_attachments is EqualUnmodifiableListView) return _attachments;
    // ignore: implicit_dynamic_type
    return EqualUnmodifiableListView(_attachments);
  }

  @override
  final DateTime? created_at;

  @override
  String toString() {
    return 'Lead(id: $id, lead_code: $lead_code, status: $status, gp_name: $gp_name, patient_name: $patient_name, patient_mobile: $patient_mobile, patient_age: $patient_age, patient_gender: $patient_gender, notes: $notes, hospital_name: $hospital_name, attachments: $attachments, created_at: $created_at)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$LeadImpl &&
            (identical(other.id, id) || other.id == id) &&
            (identical(other.lead_code, lead_code) ||
                other.lead_code == lead_code) &&
            (identical(other.status, status) || other.status == status) &&
            (identical(other.gp_name, gp_name) || other.gp_name == gp_name) &&
            (identical(other.patient_name, patient_name) ||
                other.patient_name == patient_name) &&
            (identical(other.patient_mobile, patient_mobile) ||
                other.patient_mobile == patient_mobile) &&
            (identical(other.patient_age, patient_age) ||
                other.patient_age == patient_age) &&
            (identical(other.patient_gender, patient_gender) ||
                other.patient_gender == patient_gender) &&
            (identical(other.notes, notes) || other.notes == notes) &&
            (identical(other.hospital_name, hospital_name) ||
                other.hospital_name == hospital_name) &&
            const DeepCollectionEquality()
                .equals(other._attachments, _attachments) &&
            (identical(other.created_at, created_at) ||
                other.created_at == created_at));
  }

  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  int get hashCode => Object.hash(
      runtimeType,
      id,
      lead_code,
      status,
      gp_name,
      patient_name,
      patient_mobile,
      patient_age,
      patient_gender,
      notes,
      hospital_name,
      const DeepCollectionEquality().hash(_attachments),
      created_at);

  /// Create a copy of Lead
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$LeadImplCopyWith<_$LeadImpl> get copyWith =>
      __$$LeadImplCopyWithImpl<_$LeadImpl>(this, _$identity);

  @override
  Map<String, dynamic> toJson() {
    return _$$LeadImplToJson(
      this,
    );
  }
}

abstract class _Lead implements Lead {
  const factory _Lead(
      {required final int id,
      final String? lead_code,
      final String? status,
      final String? gp_name,
      final String? patient_name,
      final String? patient_mobile,
      final int? patient_age,
      final String? patient_gender,
      final String? notes,
      final String? hospital_name,
      final List<LeadAttachment> attachments,
      final DateTime? created_at}) = _$LeadImpl;

  factory _Lead.fromJson(Map<String, dynamic> json) = _$LeadImpl.fromJson;

  @override
  int get id;
  @override
  String? get lead_code;
  @override
  String? get status;
  @override
  String? get gp_name;
  @override
  String? get patient_name;
  @override
  String? get patient_mobile;
  @override
  int? get patient_age;
  @override
  String? get patient_gender;
  @override
  String? get notes;
  @override
  String? get hospital_name;
  @override
  List<LeadAttachment> get attachments;
  @override
  DateTime? get created_at;

  /// Create a copy of Lead
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$LeadImplCopyWith<_$LeadImpl> get copyWith =>
      throw _privateConstructorUsedError;
}

LeadAttachment _$LeadAttachmentFromJson(Map<String, dynamic> json) {
  return _LeadAttachment.fromJson(json);
}

/// @nodoc
mixin _$LeadAttachment {
  int get id => throw _privateConstructorUsedError;
  String? get name => throw _privateConstructorUsedError;
  String? get url => throw _privateConstructorUsedError;

  /// Serializes this LeadAttachment to a JSON map.
  Map<String, dynamic> toJson() => throw _privateConstructorUsedError;

  /// Create a copy of LeadAttachment
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $LeadAttachmentCopyWith<LeadAttachment> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $LeadAttachmentCopyWith<$Res> {
  factory $LeadAttachmentCopyWith(
          LeadAttachment value, $Res Function(LeadAttachment) then) =
      _$LeadAttachmentCopyWithImpl<$Res, LeadAttachment>;
  @useResult
  $Res call({int id, String? name, String? url});
}

/// @nodoc
class _$LeadAttachmentCopyWithImpl<$Res, $Val extends LeadAttachment>
    implements $LeadAttachmentCopyWith<$Res> {
  _$LeadAttachmentCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of LeadAttachment
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? name = freezed,
    Object? url = freezed,
  }) {
    return _then(_value.copyWith(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int,
      name: freezed == name
          ? _value.name
          : name // ignore: cast_nullable_to_non_nullable
              as String?,
      url: freezed == url
          ? _value.url
          : url // ignore: cast_nullable_to_non_nullable
              as String?,
    ) as $Val);
  }
}

/// @nodoc
abstract class _$$LeadAttachmentImplCopyWith<$Res>
    implements $LeadAttachmentCopyWith<$Res> {
  factory _$$LeadAttachmentImplCopyWith(_$LeadAttachmentImpl value,
          $Res Function(_$LeadAttachmentImpl) then) =
      __$$LeadAttachmentImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call({int id, String? name, String? url});
}

/// @nodoc
class __$$LeadAttachmentImplCopyWithImpl<$Res>
    extends _$LeadAttachmentCopyWithImpl<$Res, _$LeadAttachmentImpl>
    implements _$$LeadAttachmentImplCopyWith<$Res> {
  __$$LeadAttachmentImplCopyWithImpl(
      _$LeadAttachmentImpl _value, $Res Function(_$LeadAttachmentImpl) _then)
      : super(_value, _then);

  /// Create a copy of LeadAttachment
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? name = freezed,
    Object? url = freezed,
  }) {
    return _then(_$LeadAttachmentImpl(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int,
      name: freezed == name
          ? _value.name
          : name // ignore: cast_nullable_to_non_nullable
              as String?,
      url: freezed == url
          ? _value.url
          : url // ignore: cast_nullable_to_non_nullable
              as String?,
    ));
  }
}

/// @nodoc
@JsonSerializable()
class _$LeadAttachmentImpl implements _LeadAttachment {
  const _$LeadAttachmentImpl({required this.id, this.name, this.url});

  factory _$LeadAttachmentImpl.fromJson(Map<String, dynamic> json) =>
      _$$LeadAttachmentImplFromJson(json);

  @override
  final int id;
  @override
  final String? name;
  @override
  final String? url;

  @override
  String toString() {
    return 'LeadAttachment(id: $id, name: $name, url: $url)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$LeadAttachmentImpl &&
            (identical(other.id, id) || other.id == id) &&
            (identical(other.name, name) || other.name == name) &&
            (identical(other.url, url) || other.url == url));
  }

  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  int get hashCode => Object.hash(runtimeType, id, name, url);

  /// Create a copy of LeadAttachment
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$LeadAttachmentImplCopyWith<_$LeadAttachmentImpl> get copyWith =>
      __$$LeadAttachmentImplCopyWithImpl<_$LeadAttachmentImpl>(
          this, _$identity);

  @override
  Map<String, dynamic> toJson() {
    return _$$LeadAttachmentImplToJson(
      this,
    );
  }
}

abstract class _LeadAttachment implements LeadAttachment {
  const factory _LeadAttachment(
      {required final int id,
      final String? name,
      final String? url}) = _$LeadAttachmentImpl;

  factory _LeadAttachment.fromJson(Map<String, dynamic> json) =
      _$LeadAttachmentImpl.fromJson;

  @override
  int get id;
  @override
  String? get name;
  @override
  String? get url;

  /// Create a copy of LeadAttachment
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$LeadAttachmentImplCopyWith<_$LeadAttachmentImpl> get copyWith =>
      throw _privateConstructorUsedError;
}
