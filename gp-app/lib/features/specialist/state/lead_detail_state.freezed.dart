// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'lead_detail_state.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
    'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models');

/// @nodoc
mixin _$LeadDetailState {
  @optionalTypeArgs
  TResult when<TResult extends Object?>({
    required TResult Function() loading,
    required TResult Function(Lead lead) loaded,
    required TResult Function(String message) error,
  }) =>
      throw _privateConstructorUsedError;
  @optionalTypeArgs
  TResult? whenOrNull<TResult extends Object?>({
    TResult? Function()? loading,
    TResult? Function(Lead lead)? loaded,
    TResult? Function(String message)? error,
  }) =>
      throw _privateConstructorUsedError;
  @optionalTypeArgs
  TResult maybeWhen<TResult extends Object?>({
    TResult Function()? loading,
    TResult Function(Lead lead)? loaded,
    TResult Function(String message)? error,
    required TResult orElse(),
  }) =>
      throw _privateConstructorUsedError;
  @optionalTypeArgs
  TResult map<TResult extends Object?>({
    required TResult Function(LeadDetailLoading value) loading,
    required TResult Function(LeadDetailLoaded value) loaded,
    required TResult Function(LeadDetailError value) error,
  }) =>
      throw _privateConstructorUsedError;
  @optionalTypeArgs
  TResult? mapOrNull<TResult extends Object?>({
    TResult? Function(LeadDetailLoading value)? loading,
    TResult? Function(LeadDetailLoaded value)? loaded,
    TResult? Function(LeadDetailError value)? error,
  }) =>
      throw _privateConstructorUsedError;
  @optionalTypeArgs
  TResult maybeMap<TResult extends Object?>({
    TResult Function(LeadDetailLoading value)? loading,
    TResult Function(LeadDetailLoaded value)? loaded,
    TResult Function(LeadDetailError value)? error,
    required TResult orElse(),
  }) =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $LeadDetailStateCopyWith<$Res> {
  factory $LeadDetailStateCopyWith(
          LeadDetailState value, $Res Function(LeadDetailState) then) =
      _$LeadDetailStateCopyWithImpl<$Res, LeadDetailState>;
}

/// @nodoc
class _$LeadDetailStateCopyWithImpl<$Res, $Val extends LeadDetailState>
    implements $LeadDetailStateCopyWith<$Res> {
  _$LeadDetailStateCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of LeadDetailState
  /// with the given fields replaced by the non-null parameter values.
}

/// @nodoc
abstract class _$$LeadDetailLoadingImplCopyWith<$Res> {
  factory _$$LeadDetailLoadingImplCopyWith(_$LeadDetailLoadingImpl value,
          $Res Function(_$LeadDetailLoadingImpl) then) =
      __$$LeadDetailLoadingImplCopyWithImpl<$Res>;
}

/// @nodoc
class __$$LeadDetailLoadingImplCopyWithImpl<$Res>
    extends _$LeadDetailStateCopyWithImpl<$Res, _$LeadDetailLoadingImpl>
    implements _$$LeadDetailLoadingImplCopyWith<$Res> {
  __$$LeadDetailLoadingImplCopyWithImpl(_$LeadDetailLoadingImpl _value,
      $Res Function(_$LeadDetailLoadingImpl) _then)
      : super(_value, _then);

  /// Create a copy of LeadDetailState
  /// with the given fields replaced by the non-null parameter values.
}

/// @nodoc

class _$LeadDetailLoadingImpl implements LeadDetailLoading {
  const _$LeadDetailLoadingImpl();

  @override
  String toString() {
    return 'LeadDetailState.loading()';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType && other is _$LeadDetailLoadingImpl);
  }

  @override
  int get hashCode => runtimeType.hashCode;

  @override
  @optionalTypeArgs
  TResult when<TResult extends Object?>({
    required TResult Function() loading,
    required TResult Function(Lead lead) loaded,
    required TResult Function(String message) error,
  }) {
    return loading();
  }

  @override
  @optionalTypeArgs
  TResult? whenOrNull<TResult extends Object?>({
    TResult? Function()? loading,
    TResult? Function(Lead lead)? loaded,
    TResult? Function(String message)? error,
  }) {
    return loading?.call();
  }

  @override
  @optionalTypeArgs
  TResult maybeWhen<TResult extends Object?>({
    TResult Function()? loading,
    TResult Function(Lead lead)? loaded,
    TResult Function(String message)? error,
    required TResult orElse(),
  }) {
    if (loading != null) {
      return loading();
    }
    return orElse();
  }

  @override
  @optionalTypeArgs
  TResult map<TResult extends Object?>({
    required TResult Function(LeadDetailLoading value) loading,
    required TResult Function(LeadDetailLoaded value) loaded,
    required TResult Function(LeadDetailError value) error,
  }) {
    return loading(this);
  }

  @override
  @optionalTypeArgs
  TResult? mapOrNull<TResult extends Object?>({
    TResult? Function(LeadDetailLoading value)? loading,
    TResult? Function(LeadDetailLoaded value)? loaded,
    TResult? Function(LeadDetailError value)? error,
  }) {
    return loading?.call(this);
  }

  @override
  @optionalTypeArgs
  TResult maybeMap<TResult extends Object?>({
    TResult Function(LeadDetailLoading value)? loading,
    TResult Function(LeadDetailLoaded value)? loaded,
    TResult Function(LeadDetailError value)? error,
    required TResult orElse(),
  }) {
    if (loading != null) {
      return loading(this);
    }
    return orElse();
  }
}

abstract class LeadDetailLoading implements LeadDetailState {
  const factory LeadDetailLoading() = _$LeadDetailLoadingImpl;
}

/// @nodoc
abstract class _$$LeadDetailLoadedImplCopyWith<$Res> {
  factory _$$LeadDetailLoadedImplCopyWith(_$LeadDetailLoadedImpl value,
          $Res Function(_$LeadDetailLoadedImpl) then) =
      __$$LeadDetailLoadedImplCopyWithImpl<$Res>;
  @useResult
  $Res call({Lead lead});

  $LeadCopyWith<$Res> get lead;
}

/// @nodoc
class __$$LeadDetailLoadedImplCopyWithImpl<$Res>
    extends _$LeadDetailStateCopyWithImpl<$Res, _$LeadDetailLoadedImpl>
    implements _$$LeadDetailLoadedImplCopyWith<$Res> {
  __$$LeadDetailLoadedImplCopyWithImpl(_$LeadDetailLoadedImpl _value,
      $Res Function(_$LeadDetailLoadedImpl) _then)
      : super(_value, _then);

  /// Create a copy of LeadDetailState
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? lead = null,
  }) {
    return _then(_$LeadDetailLoadedImpl(
      null == lead
          ? _value.lead
          : lead // ignore: cast_nullable_to_non_nullable
              as Lead,
    ));
  }

  /// Create a copy of LeadDetailState
  /// with the given fields replaced by the non-null parameter values.
  @override
  @pragma('vm:prefer-inline')
  $LeadCopyWith<$Res> get lead {
    return $LeadCopyWith<$Res>(_value.lead, (value) {
      return _then(_value.copyWith(lead: value));
    });
  }
}

/// @nodoc

class _$LeadDetailLoadedImpl implements LeadDetailLoaded {
  const _$LeadDetailLoadedImpl(this.lead);

  @override
  final Lead lead;

  @override
  String toString() {
    return 'LeadDetailState.loaded(lead: $lead)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$LeadDetailLoadedImpl &&
            (identical(other.lead, lead) || other.lead == lead));
  }

  @override
  int get hashCode => Object.hash(runtimeType, lead);

  /// Create a copy of LeadDetailState
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$LeadDetailLoadedImplCopyWith<_$LeadDetailLoadedImpl> get copyWith =>
      __$$LeadDetailLoadedImplCopyWithImpl<_$LeadDetailLoadedImpl>(
          this, _$identity);

  @override
  @optionalTypeArgs
  TResult when<TResult extends Object?>({
    required TResult Function() loading,
    required TResult Function(Lead lead) loaded,
    required TResult Function(String message) error,
  }) {
    return loaded(lead);
  }

  @override
  @optionalTypeArgs
  TResult? whenOrNull<TResult extends Object?>({
    TResult? Function()? loading,
    TResult? Function(Lead lead)? loaded,
    TResult? Function(String message)? error,
  }) {
    return loaded?.call(lead);
  }

  @override
  @optionalTypeArgs
  TResult maybeWhen<TResult extends Object?>({
    TResult Function()? loading,
    TResult Function(Lead lead)? loaded,
    TResult Function(String message)? error,
    required TResult orElse(),
  }) {
    if (loaded != null) {
      return loaded(lead);
    }
    return orElse();
  }

  @override
  @optionalTypeArgs
  TResult map<TResult extends Object?>({
    required TResult Function(LeadDetailLoading value) loading,
    required TResult Function(LeadDetailLoaded value) loaded,
    required TResult Function(LeadDetailError value) error,
  }) {
    return loaded(this);
  }

  @override
  @optionalTypeArgs
  TResult? mapOrNull<TResult extends Object?>({
    TResult? Function(LeadDetailLoading value)? loading,
    TResult? Function(LeadDetailLoaded value)? loaded,
    TResult? Function(LeadDetailError value)? error,
  }) {
    return loaded?.call(this);
  }

  @override
  @optionalTypeArgs
  TResult maybeMap<TResult extends Object?>({
    TResult Function(LeadDetailLoading value)? loading,
    TResult Function(LeadDetailLoaded value)? loaded,
    TResult Function(LeadDetailError value)? error,
    required TResult orElse(),
  }) {
    if (loaded != null) {
      return loaded(this);
    }
    return orElse();
  }
}

abstract class LeadDetailLoaded implements LeadDetailState {
  const factory LeadDetailLoaded(final Lead lead) = _$LeadDetailLoadedImpl;

  Lead get lead;

  /// Create a copy of LeadDetailState
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$LeadDetailLoadedImplCopyWith<_$LeadDetailLoadedImpl> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class _$$LeadDetailErrorImplCopyWith<$Res> {
  factory _$$LeadDetailErrorImplCopyWith(_$LeadDetailErrorImpl value,
          $Res Function(_$LeadDetailErrorImpl) then) =
      __$$LeadDetailErrorImplCopyWithImpl<$Res>;
  @useResult
  $Res call({String message});
}

/// @nodoc
class __$$LeadDetailErrorImplCopyWithImpl<$Res>
    extends _$LeadDetailStateCopyWithImpl<$Res, _$LeadDetailErrorImpl>
    implements _$$LeadDetailErrorImplCopyWith<$Res> {
  __$$LeadDetailErrorImplCopyWithImpl(
      _$LeadDetailErrorImpl _value, $Res Function(_$LeadDetailErrorImpl) _then)
      : super(_value, _then);

  /// Create a copy of LeadDetailState
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? message = null,
  }) {
    return _then(_$LeadDetailErrorImpl(
      null == message
          ? _value.message
          : message // ignore: cast_nullable_to_non_nullable
              as String,
    ));
  }
}

/// @nodoc

class _$LeadDetailErrorImpl implements LeadDetailError {
  const _$LeadDetailErrorImpl(this.message);

  @override
  final String message;

  @override
  String toString() {
    return 'LeadDetailState.error(message: $message)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$LeadDetailErrorImpl &&
            (identical(other.message, message) || other.message == message));
  }

  @override
  int get hashCode => Object.hash(runtimeType, message);

  /// Create a copy of LeadDetailState
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$LeadDetailErrorImplCopyWith<_$LeadDetailErrorImpl> get copyWith =>
      __$$LeadDetailErrorImplCopyWithImpl<_$LeadDetailErrorImpl>(
          this, _$identity);

  @override
  @optionalTypeArgs
  TResult when<TResult extends Object?>({
    required TResult Function() loading,
    required TResult Function(Lead lead) loaded,
    required TResult Function(String message) error,
  }) {
    return error(message);
  }

  @override
  @optionalTypeArgs
  TResult? whenOrNull<TResult extends Object?>({
    TResult? Function()? loading,
    TResult? Function(Lead lead)? loaded,
    TResult? Function(String message)? error,
  }) {
    return error?.call(message);
  }

  @override
  @optionalTypeArgs
  TResult maybeWhen<TResult extends Object?>({
    TResult Function()? loading,
    TResult Function(Lead lead)? loaded,
    TResult Function(String message)? error,
    required TResult orElse(),
  }) {
    if (error != null) {
      return error(message);
    }
    return orElse();
  }

  @override
  @optionalTypeArgs
  TResult map<TResult extends Object?>({
    required TResult Function(LeadDetailLoading value) loading,
    required TResult Function(LeadDetailLoaded value) loaded,
    required TResult Function(LeadDetailError value) error,
  }) {
    return error(this);
  }

  @override
  @optionalTypeArgs
  TResult? mapOrNull<TResult extends Object?>({
    TResult? Function(LeadDetailLoading value)? loading,
    TResult? Function(LeadDetailLoaded value)? loaded,
    TResult? Function(LeadDetailError value)? error,
  }) {
    return error?.call(this);
  }

  @override
  @optionalTypeArgs
  TResult maybeMap<TResult extends Object?>({
    TResult Function(LeadDetailLoading value)? loading,
    TResult Function(LeadDetailLoaded value)? loaded,
    TResult Function(LeadDetailError value)? error,
    required TResult orElse(),
  }) {
    if (error != null) {
      return error(this);
    }
    return orElse();
  }
}

abstract class LeadDetailError implements LeadDetailState {
  const factory LeadDetailError(final String message) = _$LeadDetailErrorImpl;

  String get message;

  /// Create a copy of LeadDetailState
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$LeadDetailErrorImplCopyWith<_$LeadDetailErrorImpl> get copyWith =>
      throw _privateConstructorUsedError;
}
