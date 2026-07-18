// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'lead_list_state.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
    'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models');

/// @nodoc
mixin _$LeadListState {
  bool get loading => throw _privateConstructorUsedError;
  bool get refreshing => throw _privateConstructorUsedError;
  String get query => throw _privateConstructorUsedError;
  String get status => throw _privateConstructorUsedError;
  int get page => throw _privateConstructorUsedError;
  bool get hasMore => throw _privateConstructorUsedError;
  List<Lead> get items => throw _privateConstructorUsedError;
  String? get error => throw _privateConstructorUsedError;

  /// Create a copy of LeadListState
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $LeadListStateCopyWith<LeadListState> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $LeadListStateCopyWith<$Res> {
  factory $LeadListStateCopyWith(
          LeadListState value, $Res Function(LeadListState) then) =
      _$LeadListStateCopyWithImpl<$Res, LeadListState>;
  @useResult
  $Res call(
      {bool loading,
      bool refreshing,
      String query,
      String status,
      int page,
      bool hasMore,
      List<Lead> items,
      String? error});
}

/// @nodoc
class _$LeadListStateCopyWithImpl<$Res, $Val extends LeadListState>
    implements $LeadListStateCopyWith<$Res> {
  _$LeadListStateCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of LeadListState
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? loading = null,
    Object? refreshing = null,
    Object? query = null,
    Object? status = null,
    Object? page = null,
    Object? hasMore = null,
    Object? items = null,
    Object? error = freezed,
  }) {
    return _then(_value.copyWith(
      loading: null == loading
          ? _value.loading
          : loading // ignore: cast_nullable_to_non_nullable
              as bool,
      refreshing: null == refreshing
          ? _value.refreshing
          : refreshing // ignore: cast_nullable_to_non_nullable
              as bool,
      query: null == query
          ? _value.query
          : query // ignore: cast_nullable_to_non_nullable
              as String,
      status: null == status
          ? _value.status
          : status // ignore: cast_nullable_to_non_nullable
              as String,
      page: null == page
          ? _value.page
          : page // ignore: cast_nullable_to_non_nullable
              as int,
      hasMore: null == hasMore
          ? _value.hasMore
          : hasMore // ignore: cast_nullable_to_non_nullable
              as bool,
      items: null == items
          ? _value.items
          : items // ignore: cast_nullable_to_non_nullable
              as List<Lead>,
      error: freezed == error
          ? _value.error
          : error // ignore: cast_nullable_to_non_nullable
              as String?,
    ) as $Val);
  }
}

/// @nodoc
abstract class _$$LeadListStateImplCopyWith<$Res>
    implements $LeadListStateCopyWith<$Res> {
  factory _$$LeadListStateImplCopyWith(
          _$LeadListStateImpl value, $Res Function(_$LeadListStateImpl) then) =
      __$$LeadListStateImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call(
      {bool loading,
      bool refreshing,
      String query,
      String status,
      int page,
      bool hasMore,
      List<Lead> items,
      String? error});
}

/// @nodoc
class __$$LeadListStateImplCopyWithImpl<$Res>
    extends _$LeadListStateCopyWithImpl<$Res, _$LeadListStateImpl>
    implements _$$LeadListStateImplCopyWith<$Res> {
  __$$LeadListStateImplCopyWithImpl(
      _$LeadListStateImpl _value, $Res Function(_$LeadListStateImpl) _then)
      : super(_value, _then);

  /// Create a copy of LeadListState
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? loading = null,
    Object? refreshing = null,
    Object? query = null,
    Object? status = null,
    Object? page = null,
    Object? hasMore = null,
    Object? items = null,
    Object? error = freezed,
  }) {
    return _then(_$LeadListStateImpl(
      loading: null == loading
          ? _value.loading
          : loading // ignore: cast_nullable_to_non_nullable
              as bool,
      refreshing: null == refreshing
          ? _value.refreshing
          : refreshing // ignore: cast_nullable_to_non_nullable
              as bool,
      query: null == query
          ? _value.query
          : query // ignore: cast_nullable_to_non_nullable
              as String,
      status: null == status
          ? _value.status
          : status // ignore: cast_nullable_to_non_nullable
              as String,
      page: null == page
          ? _value.page
          : page // ignore: cast_nullable_to_non_nullable
              as int,
      hasMore: null == hasMore
          ? _value.hasMore
          : hasMore // ignore: cast_nullable_to_non_nullable
              as bool,
      items: null == items
          ? _value._items
          : items // ignore: cast_nullable_to_non_nullable
              as List<Lead>,
      error: freezed == error
          ? _value.error
          : error // ignore: cast_nullable_to_non_nullable
              as String?,
    ));
  }
}

/// @nodoc

class _$LeadListStateImpl implements _LeadListState {
  const _$LeadListStateImpl(
      {this.loading = false,
      this.refreshing = false,
      this.query = '',
      this.status = '',
      this.page = 1,
      this.hasMore = false,
      final List<Lead> items = const <Lead>[],
      this.error})
      : _items = items;

  @override
  @JsonKey()
  final bool loading;
  @override
  @JsonKey()
  final bool refreshing;
  @override
  @JsonKey()
  final String query;
  @override
  @JsonKey()
  final String status;
  @override
  @JsonKey()
  final int page;
  @override
  @JsonKey()
  final bool hasMore;
  final List<Lead> _items;
  @override
  @JsonKey()
  List<Lead> get items {
    if (_items is EqualUnmodifiableListView) return _items;
    // ignore: implicit_dynamic_type
    return EqualUnmodifiableListView(_items);
  }

  @override
  final String? error;

  @override
  String toString() {
    return 'LeadListState(loading: $loading, refreshing: $refreshing, query: $query, status: $status, page: $page, hasMore: $hasMore, items: $items, error: $error)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$LeadListStateImpl &&
            (identical(other.loading, loading) || other.loading == loading) &&
            (identical(other.refreshing, refreshing) ||
                other.refreshing == refreshing) &&
            (identical(other.query, query) || other.query == query) &&
            (identical(other.status, status) || other.status == status) &&
            (identical(other.page, page) || other.page == page) &&
            (identical(other.hasMore, hasMore) || other.hasMore == hasMore) &&
            const DeepCollectionEquality().equals(other._items, _items) &&
            (identical(other.error, error) || other.error == error));
  }

  @override
  int get hashCode => Object.hash(
      runtimeType,
      loading,
      refreshing,
      query,
      status,
      page,
      hasMore,
      const DeepCollectionEquality().hash(_items),
      error);

  /// Create a copy of LeadListState
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$LeadListStateImplCopyWith<_$LeadListStateImpl> get copyWith =>
      __$$LeadListStateImplCopyWithImpl<_$LeadListStateImpl>(this, _$identity);
}

abstract class _LeadListState implements LeadListState {
  const factory _LeadListState(
      {final bool loading,
      final bool refreshing,
      final String query,
      final String status,
      final int page,
      final bool hasMore,
      final List<Lead> items,
      final String? error}) = _$LeadListStateImpl;

  @override
  bool get loading;
  @override
  bool get refreshing;
  @override
  String get query;
  @override
  String get status;
  @override
  int get page;
  @override
  bool get hasMore;
  @override
  List<Lead> get items;
  @override
  String? get error;

  /// Create a copy of LeadListState
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$LeadListStateImplCopyWith<_$LeadListStateImpl> get copyWith =>
      throw _privateConstructorUsedError;
}
