// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'specialist_category.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
    'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models');

SpecialistCategory _$SpecialistCategoryFromJson(Map<String, dynamic> json) {
  return _SpecialistCategory.fromJson(json);
}

/// @nodoc
mixin _$SpecialistCategory {
  int get id => throw _privateConstructorUsedError;
  String get name => throw _privateConstructorUsedError;
  @JsonKey(name: 'is_premium')
  bool get isPremium => throw _privateConstructorUsedError;
  String? get code => throw _privateConstructorUsedError;

  /// Serializes this SpecialistCategory to a JSON map.
  Map<String, dynamic> toJson() => throw _privateConstructorUsedError;

  /// Create a copy of SpecialistCategory
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $SpecialistCategoryCopyWith<SpecialistCategory> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $SpecialistCategoryCopyWith<$Res> {
  factory $SpecialistCategoryCopyWith(
          SpecialistCategory value, $Res Function(SpecialistCategory) then) =
      _$SpecialistCategoryCopyWithImpl<$Res, SpecialistCategory>;
  @useResult
  $Res call(
      {int id,
      String name,
      @JsonKey(name: 'is_premium') bool isPremium,
      String? code});
}

/// @nodoc
class _$SpecialistCategoryCopyWithImpl<$Res, $Val extends SpecialistCategory>
    implements $SpecialistCategoryCopyWith<$Res> {
  _$SpecialistCategoryCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of SpecialistCategory
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? name = null,
    Object? isPremium = null,
    Object? code = freezed,
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
      isPremium: null == isPremium
          ? _value.isPremium
          : isPremium // ignore: cast_nullable_to_non_nullable
              as bool,
      code: freezed == code
          ? _value.code
          : code // ignore: cast_nullable_to_non_nullable
              as String?,
    ) as $Val);
  }
}

/// @nodoc
abstract class _$$SpecialistCategoryImplCopyWith<$Res>
    implements $SpecialistCategoryCopyWith<$Res> {
  factory _$$SpecialistCategoryImplCopyWith(_$SpecialistCategoryImpl value,
          $Res Function(_$SpecialistCategoryImpl) then) =
      __$$SpecialistCategoryImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call(
      {int id,
      String name,
      @JsonKey(name: 'is_premium') bool isPremium,
      String? code});
}

/// @nodoc
class __$$SpecialistCategoryImplCopyWithImpl<$Res>
    extends _$SpecialistCategoryCopyWithImpl<$Res, _$SpecialistCategoryImpl>
    implements _$$SpecialistCategoryImplCopyWith<$Res> {
  __$$SpecialistCategoryImplCopyWithImpl(_$SpecialistCategoryImpl _value,
      $Res Function(_$SpecialistCategoryImpl) _then)
      : super(_value, _then);

  /// Create a copy of SpecialistCategory
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? name = null,
    Object? isPremium = null,
    Object? code = freezed,
  }) {
    return _then(_$SpecialistCategoryImpl(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int,
      name: null == name
          ? _value.name
          : name // ignore: cast_nullable_to_non_nullable
              as String,
      isPremium: null == isPremium
          ? _value.isPremium
          : isPremium // ignore: cast_nullable_to_non_nullable
              as bool,
      code: freezed == code
          ? _value.code
          : code // ignore: cast_nullable_to_non_nullable
              as String?,
    ));
  }
}

/// @nodoc
@JsonSerializable()
class _$SpecialistCategoryImpl implements _SpecialistCategory {
  const _$SpecialistCategoryImpl(
      {required this.id,
      required this.name,
      @JsonKey(name: 'is_premium') this.isPremium = false,
      this.code});

  factory _$SpecialistCategoryImpl.fromJson(Map<String, dynamic> json) =>
      _$$SpecialistCategoryImplFromJson(json);

  @override
  final int id;
  @override
  final String name;
  @override
  @JsonKey(name: 'is_premium')
  final bool isPremium;
  @override
  final String? code;

  @override
  String toString() {
    return 'SpecialistCategory(id: $id, name: $name, isPremium: $isPremium, code: $code)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$SpecialistCategoryImpl &&
            (identical(other.id, id) || other.id == id) &&
            (identical(other.name, name) || other.name == name) &&
            (identical(other.isPremium, isPremium) ||
                other.isPremium == isPremium) &&
            (identical(other.code, code) || other.code == code));
  }

  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  int get hashCode => Object.hash(runtimeType, id, name, isPremium, code);

  /// Create a copy of SpecialistCategory
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$SpecialistCategoryImplCopyWith<_$SpecialistCategoryImpl> get copyWith =>
      __$$SpecialistCategoryImplCopyWithImpl<_$SpecialistCategoryImpl>(
          this, _$identity);

  @override
  Map<String, dynamic> toJson() {
    return _$$SpecialistCategoryImplToJson(
      this,
    );
  }
}

abstract class _SpecialistCategory implements SpecialistCategory {
  const factory _SpecialistCategory(
      {required final int id,
      required final String name,
      @JsonKey(name: 'is_premium') final bool isPremium,
      final String? code}) = _$SpecialistCategoryImpl;

  factory _SpecialistCategory.fromJson(Map<String, dynamic> json) =
      _$SpecialistCategoryImpl.fromJson;

  @override
  int get id;
  @override
  String get name;
  @override
  @JsonKey(name: 'is_premium')
  bool get isPremium;
  @override
  String? get code;

  /// Create a copy of SpecialistCategory
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$SpecialistCategoryImplCopyWith<_$SpecialistCategoryImpl> get copyWith =>
      throw _privateConstructorUsedError;
}
