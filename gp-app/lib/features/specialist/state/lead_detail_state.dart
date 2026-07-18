import 'package:freezed_annotation/freezed_annotation.dart';
import 'package:gp_app/features/specialist/models/lead.dart';

part 'lead_detail_state.freezed.dart';

@freezed
class LeadDetailState with _$LeadDetailState {
  const factory LeadDetailState.loading() = LeadDetailLoading;
  const factory LeadDetailState.loaded(Lead lead) = LeadDetailLoaded;
  const factory LeadDetailState.error(String message) = LeadDetailError;
}
