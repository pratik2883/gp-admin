import 'package:freezed_annotation/freezed_annotation.dart';
import 'package:gp_app/features/gp/models/referral.dart';

part 'referral_list_response.freezed.dart';
part 'referral_list_response.g.dart';

@freezed
class ReferralListResponse with _$ReferralListResponse {
  const factory ReferralListResponse({
    required List<Referral> data,
    int? current_page,
    int? last_page,
    int? total,
  }) = _ReferralListResponse;

  factory ReferralListResponse.fromJson(Map<String, dynamic> json) => _$ReferralListResponseFromJson(json);
}
