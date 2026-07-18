// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'gp_dashboard_response.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
    'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models');

GpDashboardResponse _$GpDashboardResponseFromJson(Map<String, dynamic> json) {
  return _GpDashboardResponse.fromJson(json);
}

/// @nodoc
mixin _$GpDashboardResponse {
  @JsonKey(name: 'total_referrals')
  int get totalReferrals => throw _privateConstructorUsedError;
  @JsonKey(name: 'pending_count')
  int get pendingCount => throw _privateConstructorUsedError;
  @JsonKey(name: 'accepted_count')
  int get acceptedCount => throw _privateConstructorUsedError;
  @JsonKey(name: 'consulted_count')
  int get consultedCount => throw _privateConstructorUsedError;
  @JsonKey(name: 'closed_count')
  int get closedCount => throw _privateConstructorUsedError;
  @JsonKey(name: 'recent_referrals')
  List<Referral> get recentReferrals => throw _privateConstructorUsedError;

  /// Serializes this GpDashboardResponse to a JSON map.
  Map<String, dynamic> toJson() => throw _privateConstructorUsedError;

  /// Create a copy of GpDashboardResponse
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $GpDashboardResponseCopyWith<GpDashboardResponse> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $GpDashboardResponseCopyWith<$Res> {
  factory $GpDashboardResponseCopyWith(
          GpDashboardResponse value, $Res Function(GpDashboardResponse) then) =
      _$GpDashboardResponseCopyWithImpl<$Res, GpDashboardResponse>;
  @useResult
  $Res call(
      {@JsonKey(name: 'total_referrals') int totalReferrals,
      @JsonKey(name: 'pending_count') int pendingCount,
      @JsonKey(name: 'accepted_count') int acceptedCount,
      @JsonKey(name: 'consulted_count') int consultedCount,
      @JsonKey(name: 'closed_count') int closedCount,
      @JsonKey(name: 'recent_referrals') List<Referral> recentReferrals});
}

/// @nodoc
class _$GpDashboardResponseCopyWithImpl<$Res, $Val extends GpDashboardResponse>
    implements $GpDashboardResponseCopyWith<$Res> {
  _$GpDashboardResponseCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of GpDashboardResponse
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? totalReferrals = null,
    Object? pendingCount = null,
    Object? acceptedCount = null,
    Object? consultedCount = null,
    Object? closedCount = null,
    Object? recentReferrals = null,
  }) {
    return _then(_value.copyWith(
      totalReferrals: null == totalReferrals
          ? _value.totalReferrals
          : totalReferrals // ignore: cast_nullable_to_non_nullable
              as int,
      pendingCount: null == pendingCount
          ? _value.pendingCount
          : pendingCount // ignore: cast_nullable_to_non_nullable
              as int,
      acceptedCount: null == acceptedCount
          ? _value.acceptedCount
          : acceptedCount // ignore: cast_nullable_to_non_nullable
              as int,
      consultedCount: null == consultedCount
          ? _value.consultedCount
          : consultedCount // ignore: cast_nullable_to_non_nullable
              as int,
      closedCount: null == closedCount
          ? _value.closedCount
          : closedCount // ignore: cast_nullable_to_non_nullable
              as int,
      recentReferrals: null == recentReferrals
          ? _value.recentReferrals
          : recentReferrals // ignore: cast_nullable_to_non_nullable
              as List<Referral>,
    ) as $Val);
  }
}

/// @nodoc
abstract class _$$GpDashboardResponseImplCopyWith<$Res>
    implements $GpDashboardResponseCopyWith<$Res> {
  factory _$$GpDashboardResponseImplCopyWith(_$GpDashboardResponseImpl value,
          $Res Function(_$GpDashboardResponseImpl) then) =
      __$$GpDashboardResponseImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call(
      {@JsonKey(name: 'total_referrals') int totalReferrals,
      @JsonKey(name: 'pending_count') int pendingCount,
      @JsonKey(name: 'accepted_count') int acceptedCount,
      @JsonKey(name: 'consulted_count') int consultedCount,
      @JsonKey(name: 'closed_count') int closedCount,
      @JsonKey(name: 'recent_referrals') List<Referral> recentReferrals});
}

/// @nodoc
class __$$GpDashboardResponseImplCopyWithImpl<$Res>
    extends _$GpDashboardResponseCopyWithImpl<$Res, _$GpDashboardResponseImpl>
    implements _$$GpDashboardResponseImplCopyWith<$Res> {
  __$$GpDashboardResponseImplCopyWithImpl(_$GpDashboardResponseImpl _value,
      $Res Function(_$GpDashboardResponseImpl) _then)
      : super(_value, _then);

  /// Create a copy of GpDashboardResponse
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? totalReferrals = null,
    Object? pendingCount = null,
    Object? acceptedCount = null,
    Object? consultedCount = null,
    Object? closedCount = null,
    Object? recentReferrals = null,
  }) {
    return _then(_$GpDashboardResponseImpl(
      totalReferrals: null == totalReferrals
          ? _value.totalReferrals
          : totalReferrals // ignore: cast_nullable_to_non_nullable
              as int,
      pendingCount: null == pendingCount
          ? _value.pendingCount
          : pendingCount // ignore: cast_nullable_to_non_nullable
              as int,
      acceptedCount: null == acceptedCount
          ? _value.acceptedCount
          : acceptedCount // ignore: cast_nullable_to_non_nullable
              as int,
      consultedCount: null == consultedCount
          ? _value.consultedCount
          : consultedCount // ignore: cast_nullable_to_non_nullable
              as int,
      closedCount: null == closedCount
          ? _value.closedCount
          : closedCount // ignore: cast_nullable_to_non_nullable
              as int,
      recentReferrals: null == recentReferrals
          ? _value._recentReferrals
          : recentReferrals // ignore: cast_nullable_to_non_nullable
              as List<Referral>,
    ));
  }
}

/// @nodoc
@JsonSerializable()
class _$GpDashboardResponseImpl implements _GpDashboardResponse {
  const _$GpDashboardResponseImpl(
      {@JsonKey(name: 'total_referrals') this.totalReferrals = 0,
      @JsonKey(name: 'pending_count') this.pendingCount = 0,
      @JsonKey(name: 'accepted_count') this.acceptedCount = 0,
      @JsonKey(name: 'consulted_count') this.consultedCount = 0,
      @JsonKey(name: 'closed_count') this.closedCount = 0,
      @JsonKey(name: 'recent_referrals')
      final List<Referral> recentReferrals = const []})
      : _recentReferrals = recentReferrals;

  factory _$GpDashboardResponseImpl.fromJson(Map<String, dynamic> json) =>
      _$$GpDashboardResponseImplFromJson(json);

  @override
  @JsonKey(name: 'total_referrals')
  final int totalReferrals;
  @override
  @JsonKey(name: 'pending_count')
  final int pendingCount;
  @override
  @JsonKey(name: 'accepted_count')
  final int acceptedCount;
  @override
  @JsonKey(name: 'consulted_count')
  final int consultedCount;
  @override
  @JsonKey(name: 'closed_count')
  final int closedCount;
  final List<Referral> _recentReferrals;
  @override
  @JsonKey(name: 'recent_referrals')
  List<Referral> get recentReferrals {
    if (_recentReferrals is EqualUnmodifiableListView) return _recentReferrals;
    // ignore: implicit_dynamic_type
    return EqualUnmodifiableListView(_recentReferrals);
  }

  @override
  String toString() {
    return 'GpDashboardResponse(totalReferrals: $totalReferrals, pendingCount: $pendingCount, acceptedCount: $acceptedCount, consultedCount: $consultedCount, closedCount: $closedCount, recentReferrals: $recentReferrals)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$GpDashboardResponseImpl &&
            (identical(other.totalReferrals, totalReferrals) ||
                other.totalReferrals == totalReferrals) &&
            (identical(other.pendingCount, pendingCount) ||
                other.pendingCount == pendingCount) &&
            (identical(other.acceptedCount, acceptedCount) ||
                other.acceptedCount == acceptedCount) &&
            (identical(other.consultedCount, consultedCount) ||
                other.consultedCount == consultedCount) &&
            (identical(other.closedCount, closedCount) ||
                other.closedCount == closedCount) &&
            const DeepCollectionEquality()
                .equals(other._recentReferrals, _recentReferrals));
  }

  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  int get hashCode => Object.hash(
      runtimeType,
      totalReferrals,
      pendingCount,
      acceptedCount,
      consultedCount,
      closedCount,
      const DeepCollectionEquality().hash(_recentReferrals));

  /// Create a copy of GpDashboardResponse
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$GpDashboardResponseImplCopyWith<_$GpDashboardResponseImpl> get copyWith =>
      __$$GpDashboardResponseImplCopyWithImpl<_$GpDashboardResponseImpl>(
          this, _$identity);

  @override
  Map<String, dynamic> toJson() {
    return _$$GpDashboardResponseImplToJson(
      this,
    );
  }
}

abstract class _GpDashboardResponse implements GpDashboardResponse {
  const factory _GpDashboardResponse(
      {@JsonKey(name: 'total_referrals') final int totalReferrals,
      @JsonKey(name: 'pending_count') final int pendingCount,
      @JsonKey(name: 'accepted_count') final int acceptedCount,
      @JsonKey(name: 'consulted_count') final int consultedCount,
      @JsonKey(name: 'closed_count') final int closedCount,
      @JsonKey(name: 'recent_referrals')
      final List<Referral> recentReferrals}) = _$GpDashboardResponseImpl;

  factory _GpDashboardResponse.fromJson(Map<String, dynamic> json) =
      _$GpDashboardResponseImpl.fromJson;

  @override
  @JsonKey(name: 'total_referrals')
  int get totalReferrals;
  @override
  @JsonKey(name: 'pending_count')
  int get pendingCount;
  @override
  @JsonKey(name: 'accepted_count')
  int get acceptedCount;
  @override
  @JsonKey(name: 'consulted_count')
  int get consultedCount;
  @override
  @JsonKey(name: 'closed_count')
  int get closedCount;
  @override
  @JsonKey(name: 'recent_referrals')
  List<Referral> get recentReferrals;

  /// Create a copy of GpDashboardResponse
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$GpDashboardResponseImplCopyWith<_$GpDashboardResponseImpl> get copyWith =>
      throw _privateConstructorUsedError;
}
