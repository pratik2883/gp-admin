// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'referral_list_response.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
    'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models');

ReferralListResponse _$ReferralListResponseFromJson(Map<String, dynamic> json) {
  return _ReferralListResponse.fromJson(json);
}

/// @nodoc
mixin _$ReferralListResponse {
  List<Referral> get data => throw _privateConstructorUsedError;
  int? get current_page => throw _privateConstructorUsedError;
  int? get last_page => throw _privateConstructorUsedError;
  int? get total => throw _privateConstructorUsedError;

  /// Serializes this ReferralListResponse to a JSON map.
  Map<String, dynamic> toJson() => throw _privateConstructorUsedError;

  /// Create a copy of ReferralListResponse
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $ReferralListResponseCopyWith<ReferralListResponse> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $ReferralListResponseCopyWith<$Res> {
  factory $ReferralListResponseCopyWith(ReferralListResponse value,
          $Res Function(ReferralListResponse) then) =
      _$ReferralListResponseCopyWithImpl<$Res, ReferralListResponse>;
  @useResult
  $Res call(
      {List<Referral> data, int? current_page, int? last_page, int? total});
}

/// @nodoc
class _$ReferralListResponseCopyWithImpl<$Res,
        $Val extends ReferralListResponse>
    implements $ReferralListResponseCopyWith<$Res> {
  _$ReferralListResponseCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of ReferralListResponse
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? data = null,
    Object? current_page = freezed,
    Object? last_page = freezed,
    Object? total = freezed,
  }) {
    return _then(_value.copyWith(
      data: null == data
          ? _value.data
          : data // ignore: cast_nullable_to_non_nullable
              as List<Referral>,
      current_page: freezed == current_page
          ? _value.current_page
          : current_page // ignore: cast_nullable_to_non_nullable
              as int?,
      last_page: freezed == last_page
          ? _value.last_page
          : last_page // ignore: cast_nullable_to_non_nullable
              as int?,
      total: freezed == total
          ? _value.total
          : total // ignore: cast_nullable_to_non_nullable
              as int?,
    ) as $Val);
  }
}

/// @nodoc
abstract class _$$ReferralListResponseImplCopyWith<$Res>
    implements $ReferralListResponseCopyWith<$Res> {
  factory _$$ReferralListResponseImplCopyWith(_$ReferralListResponseImpl value,
          $Res Function(_$ReferralListResponseImpl) then) =
      __$$ReferralListResponseImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call(
      {List<Referral> data, int? current_page, int? last_page, int? total});
}

/// @nodoc
class __$$ReferralListResponseImplCopyWithImpl<$Res>
    extends _$ReferralListResponseCopyWithImpl<$Res, _$ReferralListResponseImpl>
    implements _$$ReferralListResponseImplCopyWith<$Res> {
  __$$ReferralListResponseImplCopyWithImpl(_$ReferralListResponseImpl _value,
      $Res Function(_$ReferralListResponseImpl) _then)
      : super(_value, _then);

  /// Create a copy of ReferralListResponse
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? data = null,
    Object? current_page = freezed,
    Object? last_page = freezed,
    Object? total = freezed,
  }) {
    return _then(_$ReferralListResponseImpl(
      data: null == data
          ? _value._data
          : data // ignore: cast_nullable_to_non_nullable
              as List<Referral>,
      current_page: freezed == current_page
          ? _value.current_page
          : current_page // ignore: cast_nullable_to_non_nullable
              as int?,
      last_page: freezed == last_page
          ? _value.last_page
          : last_page // ignore: cast_nullable_to_non_nullable
              as int?,
      total: freezed == total
          ? _value.total
          : total // ignore: cast_nullable_to_non_nullable
              as int?,
    ));
  }
}

/// @nodoc
@JsonSerializable()
class _$ReferralListResponseImpl implements _ReferralListResponse {
  const _$ReferralListResponseImpl(
      {required final List<Referral> data,
      this.current_page,
      this.last_page,
      this.total})
      : _data = data;

  factory _$ReferralListResponseImpl.fromJson(Map<String, dynamic> json) =>
      _$$ReferralListResponseImplFromJson(json);

  final List<Referral> _data;
  @override
  List<Referral> get data {
    if (_data is EqualUnmodifiableListView) return _data;
    // ignore: implicit_dynamic_type
    return EqualUnmodifiableListView(_data);
  }

  @override
  final int? current_page;
  @override
  final int? last_page;
  @override
  final int? total;

  @override
  String toString() {
    return 'ReferralListResponse(data: $data, current_page: $current_page, last_page: $last_page, total: $total)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$ReferralListResponseImpl &&
            const DeepCollectionEquality().equals(other._data, _data) &&
            (identical(other.current_page, current_page) ||
                other.current_page == current_page) &&
            (identical(other.last_page, last_page) ||
                other.last_page == last_page) &&
            (identical(other.total, total) || other.total == total));
  }

  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  int get hashCode => Object.hash(
      runtimeType,
      const DeepCollectionEquality().hash(_data),
      current_page,
      last_page,
      total);

  /// Create a copy of ReferralListResponse
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$ReferralListResponseImplCopyWith<_$ReferralListResponseImpl>
      get copyWith =>
          __$$ReferralListResponseImplCopyWithImpl<_$ReferralListResponseImpl>(
              this, _$identity);

  @override
  Map<String, dynamic> toJson() {
    return _$$ReferralListResponseImplToJson(
      this,
    );
  }
}

abstract class _ReferralListResponse implements ReferralListResponse {
  const factory _ReferralListResponse(
      {required final List<Referral> data,
      final int? current_page,
      final int? last_page,
      final int? total}) = _$ReferralListResponseImpl;

  factory _ReferralListResponse.fromJson(Map<String, dynamic> json) =
      _$ReferralListResponseImpl.fromJson;

  @override
  List<Referral> get data;
  @override
  int? get current_page;
  @override
  int? get last_page;
  @override
  int? get total;

  /// Create a copy of ReferralListResponse
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$ReferralListResponseImplCopyWith<_$ReferralListResponseImpl>
      get copyWith => throw _privateConstructorUsedError;
}
