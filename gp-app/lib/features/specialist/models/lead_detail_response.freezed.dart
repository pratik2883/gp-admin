// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'lead_detail_response.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
    'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models');

LeadDetailResponse _$LeadDetailResponseFromJson(Map<String, dynamic> json) {
  return _LeadDetailResponse.fromJson(json);
}

/// @nodoc
mixin _$LeadDetailResponse {
  Lead get lead => throw _privateConstructorUsedError;

  /// Serializes this LeadDetailResponse to a JSON map.
  Map<String, dynamic> toJson() => throw _privateConstructorUsedError;

  /// Create a copy of LeadDetailResponse
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $LeadDetailResponseCopyWith<LeadDetailResponse> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $LeadDetailResponseCopyWith<$Res> {
  factory $LeadDetailResponseCopyWith(
          LeadDetailResponse value, $Res Function(LeadDetailResponse) then) =
      _$LeadDetailResponseCopyWithImpl<$Res, LeadDetailResponse>;
  @useResult
  $Res call({Lead lead});

  $LeadCopyWith<$Res> get lead;
}

/// @nodoc
class _$LeadDetailResponseCopyWithImpl<$Res, $Val extends LeadDetailResponse>
    implements $LeadDetailResponseCopyWith<$Res> {
  _$LeadDetailResponseCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of LeadDetailResponse
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? lead = null,
  }) {
    return _then(_value.copyWith(
      lead: null == lead
          ? _value.lead
          : lead // ignore: cast_nullable_to_non_nullable
              as Lead,
    ) as $Val);
  }

  /// Create a copy of LeadDetailResponse
  /// with the given fields replaced by the non-null parameter values.
  @override
  @pragma('vm:prefer-inline')
  $LeadCopyWith<$Res> get lead {
    return $LeadCopyWith<$Res>(_value.lead, (value) {
      return _then(_value.copyWith(lead: value) as $Val);
    });
  }
}

/// @nodoc
abstract class _$$LeadDetailResponseImplCopyWith<$Res>
    implements $LeadDetailResponseCopyWith<$Res> {
  factory _$$LeadDetailResponseImplCopyWith(_$LeadDetailResponseImpl value,
          $Res Function(_$LeadDetailResponseImpl) then) =
      __$$LeadDetailResponseImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call({Lead lead});

  @override
  $LeadCopyWith<$Res> get lead;
}

/// @nodoc
class __$$LeadDetailResponseImplCopyWithImpl<$Res>
    extends _$LeadDetailResponseCopyWithImpl<$Res, _$LeadDetailResponseImpl>
    implements _$$LeadDetailResponseImplCopyWith<$Res> {
  __$$LeadDetailResponseImplCopyWithImpl(_$LeadDetailResponseImpl _value,
      $Res Function(_$LeadDetailResponseImpl) _then)
      : super(_value, _then);

  /// Create a copy of LeadDetailResponse
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? lead = null,
  }) {
    return _then(_$LeadDetailResponseImpl(
      lead: null == lead
          ? _value.lead
          : lead // ignore: cast_nullable_to_non_nullable
              as Lead,
    ));
  }
}

/// @nodoc
@JsonSerializable()
class _$LeadDetailResponseImpl implements _LeadDetailResponse {
  const _$LeadDetailResponseImpl({required this.lead});

  factory _$LeadDetailResponseImpl.fromJson(Map<String, dynamic> json) =>
      _$$LeadDetailResponseImplFromJson(json);

  @override
  final Lead lead;

  @override
  String toString() {
    return 'LeadDetailResponse(lead: $lead)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$LeadDetailResponseImpl &&
            (identical(other.lead, lead) || other.lead == lead));
  }

  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  int get hashCode => Object.hash(runtimeType, lead);

  /// Create a copy of LeadDetailResponse
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$LeadDetailResponseImplCopyWith<_$LeadDetailResponseImpl> get copyWith =>
      __$$LeadDetailResponseImplCopyWithImpl<_$LeadDetailResponseImpl>(
          this, _$identity);

  @override
  Map<String, dynamic> toJson() {
    return _$$LeadDetailResponseImplToJson(
      this,
    );
  }
}

abstract class _LeadDetailResponse implements LeadDetailResponse {
  const factory _LeadDetailResponse({required final Lead lead}) =
      _$LeadDetailResponseImpl;

  factory _LeadDetailResponse.fromJson(Map<String, dynamic> json) =
      _$LeadDetailResponseImpl.fromJson;

  @override
  Lead get lead;

  /// Create a copy of LeadDetailResponse
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$LeadDetailResponseImplCopyWith<_$LeadDetailResponseImpl> get copyWith =>
      throw _privateConstructorUsedError;
}
