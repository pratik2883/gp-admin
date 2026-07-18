import 'package:freezed_annotation/freezed_annotation.dart';
import 'package:gp_app/features/specialist/models/lead.dart';

part 'lead_list_state.freezed.dart';

@freezed
class LeadListState with _$LeadListState {
  const factory LeadListState({
    @Default(false) bool loading,
    @Default(false) bool refreshing,
    @Default('') String query,
    @Default('') String status,
    @Default(1) int page,
    @Default(false) bool hasMore,
    @Default(<Lead>[]) List<Lead> items,
    String? error,
  }) = _LeadListState;
}
