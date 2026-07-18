import 'package:freezed_annotation/freezed_annotation.dart';

part 'specialist_category.freezed.dart';
part 'specialist_category.g.dart';

@freezed
class SpecialistCategory with _$SpecialistCategory {
  const factory SpecialistCategory({
    required int id,
    required String name,
    @JsonKey(name: 'is_premium') @Default(false) bool isPremium,
    String? code,
  }) = _SpecialistCategory;

  factory SpecialistCategory.fromJson(Map<String, dynamic> json) => _$SpecialistCategoryFromJson(json);
}
