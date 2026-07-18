import 'package:freezed_annotation/freezed_annotation.dart';
import 'package:gp_app/features/specialist/models/lead.dart';

part 'lead_list_response.freezed.dart';
part 'lead_list_response.g.dart';

@freezed
class LeadListResponse with _$LeadListResponse {
  const factory LeadListResponse({
    required List<Lead> data,
    int? current_page,
    int? last_page,
    int? total,
  }) = _LeadListResponse;

  factory LeadListResponse.fromJson(Map<String, dynamic> json) => _$LeadListResponseFromJson(json);
}
