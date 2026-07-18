import 'package:freezed_annotation/freezed_annotation.dart';

part 'new_referral_state.freezed.dart';

@freezed
class NewReferralState with _$NewReferralState {
  const factory NewReferralState.idle() = NewReferralIdle;
  const factory NewReferralState.submitting() = NewReferralSubmitting;
  const factory NewReferralState.success() = NewReferralSuccess;
  const factory NewReferralState.error(String message) = NewReferralError;
}
