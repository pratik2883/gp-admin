import 'package:freezed_annotation/freezed_annotation.dart';

part 'user.freezed.dart';
part 'user.g.dart';

@freezed
class User with _$User {
  const factory User({
    required int id,
    required String name,
    String? email,
    required String mobile,
    required String role,
    int? gp_id,
    @JsonKey(name: 'gp_status') String? gpStatus,
    int? specialist_id,
    @JsonKey(name: 'registration_number') String? registrationNumber,
    String? speciality,
    String? designation,
    String? address,
    String? city,
    @JsonKey(name: 'clinic_name') String? clinicName,
    @JsonKey(name: 'default_location_id') int? defaultLocationId,
    @JsonKey(name: 'default_location_name') String? defaultLocationName,
  }) = _User;

  factory User.fromJson(Map<String, dynamic> json) => _$UserFromJson(json);
}
