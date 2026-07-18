import 'package:freezed_annotation/freezed_annotation.dart';
import 'package:gp_app/features/gp/models/referral.dart';

part 'gp_dashboard_response.freezed.dart';
part 'gp_dashboard_response.g.dart';

@freezed
class GpDashboardResponse with _$GpDashboardResponse {
  const factory GpDashboardResponse({
    @JsonKey(name: 'total_referrals') @Default(0) int totalReferrals,
    @JsonKey(name: 'pending_count') @Default(0) int pendingCount,
    @JsonKey(name: 'accepted_count') @Default(0) int acceptedCount,
    @JsonKey(name: 'consulted_count') @Default(0) int consultedCount,
    @JsonKey(name: 'closed_count') @Default(0) int closedCount,
    @JsonKey(name: 'recent_referrals') @Default([]) List<Referral> recentReferrals,
  }) = _GpDashboardResponse;

  factory GpDashboardResponse.fromJson(Map<String, dynamic> json) => _$GpDashboardResponseFromJson(json);
}
