import 'package:freezed_annotation/freezed_annotation.dart';
import 'package:gp_app/features/gp/models/referral.dart';

part 'referral_list_state.freezed.dart';

@freezed
class ReferralListState with _$ReferralListState {
  const factory ReferralListState({
    @Default(false) bool loading,
    @Default(false) bool refreshing,
    @Default('specialist') String referralType,
    @Default('') String query,
    @Default(1) int page,
    @Default(false) bool hasMore,
    @Default(<Referral>[]) List<Referral> items,
    @Default(0) int totalSubmitted,
    @Default(0.0) double conversionRate,
    String? error,
  }) = _ReferralListState;
}
