import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:gp_app/features/specialist/data/specialist_repository.dart';
import 'package:gp_app/features/specialist/state/lead_detail_state.dart';

class LeadDetailController extends StateNotifier<LeadDetailState> {
  final SpecialistRepository _repo;
  LeadDetailController(this._repo) : super(const LeadDetailState.loading());

  Future<void> load(String id) async {
    state = const LeadDetailState.loading();
    try {
      final res = await _repo.fetchLeadDetail(id);
      state = LeadDetailState.loaded(res.lead);
    } catch (e) {
      state = const LeadDetailState.error('Failed to load lead');
    }
  }

  Future<void> updateStatus(String id, String status) async {
    final current = state;
    try {
      final lead = await _repo.updateLeadStatus(id, status);
      state = LeadDetailState.loaded(lead);
    } catch (e) {
      if (state == current) state = current;
    }
  }
}
