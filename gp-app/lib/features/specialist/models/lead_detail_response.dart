import 'package:freezed_annotation/freezed_annotation.dart';
import 'package:gp_app/features/specialist/models/lead.dart';

part 'lead_detail_response.freezed.dart';
part 'lead_detail_response.g.dart';

@freezed
class LeadDetailResponse with _$LeadDetailResponse {
  const factory LeadDetailResponse({
    required Lead lead,
  }) = _LeadDetailResponse;

  factory LeadDetailResponse.fromJson(Map<String, dynamic> json) => _$LeadDetailResponseFromJson(json);
}
