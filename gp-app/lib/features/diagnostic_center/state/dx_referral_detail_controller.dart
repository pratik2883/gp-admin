import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:gp_app/features/diagnostic_center/data/diagnostic_center_repository.dart';
import 'package:gp_app/features/diagnostic_center/state/dx_referral_detail_state.dart';
import 'package:gp_app/features/specialist/models/lead.dart';

class DxReferralDetailController extends StateNotifier<DxReferralDetailState> {
  final DiagnosticCenterRepository _repo;
  DxReferralDetailController(this._repo) : super(const DxReferralDetailLoading());

  Future<void> load(String id) async {
    state = const DxReferralDetailLoading();
    try {
      final map = await _repo.fetchReferral(id);
      final services = (map['diagnostic_services'] as List? ?? const [])
          .whereType<Map>()
          .map((m) => m.cast<String, dynamic>())
          .toList();
      state = DxReferralDetailLoaded(lead: Lead.fromJson(map), services: services);
    } catch (e) {
      state = const DxReferralDetailError('Failed to load referral');
    }
  }

  Future<void> updateStatus(String id, String status) async {
    final current = state;
    try {
      final map = await _repo.updateReferralStatus(id, status);
      final services = (map['diagnostic_services'] as List? ?? const [])
          .whereType<Map>()
          .map((m) => m.cast<String, dynamic>())
          .toList();
      state = DxReferralDetailLoaded(lead: Lead.fromJson(map), services: services);
    } catch (e) {
      if (identical(state, current)) return;
    }
  }
}