import 'package:freezed_annotation/freezed_annotation.dart';
import 'package:gp_app/features/gp/models/gp_dashboard_response.dart';

part 'gp_home_state.freezed.dart';

@freezed
class GpHomeState with _$GpHomeState {
  const factory GpHomeState.loading() = GpHomeLoading;
  const factory GpHomeState.loaded(GpDashboardResponse data) = GpHomeLoaded;
  const factory GpHomeState.error(String message) = GpHomeError;
  const factory GpHomeState.notApproved(String message) = GpHomeNotApproved;
}
