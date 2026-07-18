// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'lead_list_response.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
    'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models');

LeadListResponse _$LeadListResponseFromJson(Map<String, dynamic> json) {
  return _LeadListResponse.fromJson(json);
}

/// @nodoc
mixin _$LeadListResponse {
  List<Lead> get data => throw _privateConstructorUsedError;
  int? get current_page => throw _privateConstructorUsedError;
  int? get last_page => throw _privateConstructorUsedError;
  int? get total => throw _privateConstructorUsedError;

  /// Serializes this LeadListResponse to a JSON map.
  Map<String, dynamic> toJson() => throw _privateConstructorUsedError;

  /// Create a copy of LeadListResponse
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $LeadListResponseCopyWith<LeadListResponse> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $LeadListResponseCopyWith<$Res> {
  factory $LeadListResponseCopyWith(
          LeadListResponse value, $Res Function(LeadListResponse) then) =
      _$LeadListResponseCopyWithImpl<$Res, LeadListResponse>;
  @useResult
  $Res call({List<Lead> data, int? current_page, int? last_page, int? total});
}

/// @nodoc
class _$LeadListResponseCopyWithImpl<$Res, $Val extends LeadListResponse>
    implements $LeadListResponseCopyWith<$Res> {
  _$LeadListResponseCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of LeadListResponse
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
              as List<Lead>,
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
abstract class _$$LeadListResponseImplCopyWith<$Res>
    implements $LeadListResponseCopyWith<$Res> {
  factory _$$LeadListResponseImplCopyWith(_$LeadListResponseImpl value,
          $Res Function(_$LeadListResponseImpl) then) =
      __$$LeadListResponseImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call({List<Lead> data, int? current_page, int? last_page, int? total});
}

/// @nodoc
class __$$LeadListResponseImplCopyWithImpl<$Res>
    extends _$LeadListResponseCopyWithImpl<$Res, _$LeadListResponseImpl>
    implements _$$LeadListResponseImplCopyWith<$Res> {
  __$$LeadListResponseImplCopyWithImpl(_$LeadListResponseImpl _value,
      $Res Function(_$LeadListResponseImpl) _then)
      : super(_value, _then);

  /// Create a copy of LeadListResponse
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? data = null,
    Object? current_page = freezed,
    Object? last_page = freezed,
    Object? total = freezed,
  }) {
    return _then(_$LeadListResponseImpl(
      data: null == data
          ? _value._data
          : data // ignore: cast_nullable_to_non_nullable
              as List<Lead>,
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
class _$LeadListResponseImpl implements _LeadListResponse {
  const _$LeadListResponseImpl(
      {required final List<Lead> data,
      this.current_page,
      this.last_page,
      this.total})
      : _data = data;

  factory _$LeadListResponseImpl.fromJson(Map<String, dynamic> json) =>
      _$$LeadListResponseImplFromJson(json);

  final List<Lead> _data;
  @override
  List<Lead> get data {
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
    return 'LeadListResponse(data: $data, current_page: $current_page, last_page: $last_page, total: $total)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$LeadListResponseImpl &&
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

  /// Create a copy of LeadListResponse
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$LeadListResponseImplCopyWith<_$LeadListResponseImpl> get copyWith =>
      __$$LeadListResponseImplCopyWithImpl<_$LeadListResponseImpl>(
          this, _$identity);

  @override
  Map<String, dynamic> toJson() {
    return _$$LeadListResponseImplToJson(
      this,
    );
  }
}

abstract class _LeadListResponse implements LeadListResponse {
  const factory _LeadListResponse(
      {required final List<Lead> data,
      final int? current_page,
      final int? last_page,
      final int? total}) = _$LeadListResponseImpl;

  factory _LeadListResponse.fromJson(Map<String, dynamic> json) =
      _$LeadListResponseImpl.fromJson;

  @override
  List<Lead> get data;
  @override
  int? get current_page;
  @override
  int? get last_page;
  @override
  int? get total;

  /// Create a copy of LeadListResponse
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$LeadListResponseImplCopyWith<_$LeadListResponseImpl> get copyWith =>
      throw _privateConstructorUsedError;
}
