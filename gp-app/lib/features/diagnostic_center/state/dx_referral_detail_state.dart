import 'package:flutter/foundation.dart';
import 'package:gp_app/features/specialist/models/lead.dart';

@immutable
sealed class DxReferralDetailState {
  const DxReferralDetailState();
}

class DxReferralDetailLoading extends DxReferralDetailState {
  const DxReferralDetailLoading();
}

class DxReferralDetailError extends DxReferralDetailState {
  const DxReferralDetailError(this.message);
  final String message;
}

class DxReferralDetailLoaded extends DxReferralDetailState {
  const DxReferralDetailLoaded({
    required this.lead,
    this.services = const [],
  });

  final Lead lead;
  final List<Map<String, dynamic>> services;
}