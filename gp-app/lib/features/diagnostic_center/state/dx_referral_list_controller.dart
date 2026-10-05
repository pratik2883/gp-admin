import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:gp_app/features/diagnostic_center/data/diagnostic_center_repository.dart';
import 'package:gp_app/features/specialist/state/lead_list_refreshable.dart';
import 'package:gp_app/features/specialist/state/lead_list_state.dart';

class DxReferralListController extends StateNotifier<LeadListState> implements LeadListRefreshable {
  final DiagnosticCenterRepository _repo;
  DxReferralListController(this._repo) : super(const LeadListState());

  @override
  Future<void> refresh({String? q, String? status}) async {
    state = state.copyWith(
      refreshing: true,
      query: q ?? state.query,
      status: status ?? state.status,
      page: 1,
      error: null,
    );
    try {
      final statusParam = state.status.isEmpty ? null : state.status.toLowerCase();
      final res = await _repo.fetchReferrals(status: statusParam, q: state.query, page: 1);
      final hasMore = (res.current_page ?? 1) < (res.last_page ?? 1);
      state = state.copyWith(refreshing: false, items: res.data, hasMore: hasMore, page: 1);
    } catch (e) {
      state = state.copyWith(refreshing: false, error: 'Failed to load referrals');
    }
  }

  @override
  Future<void> loadMore() async {
    if (state.loading || !state.hasMore) return;
    state = state.copyWith(loading: true, error: null);
    try {
      final nextPage = state.page + 1;
      final statusParam = state.status.isEmpty ? null : state.status.toLowerCase();
      final res = await _repo.fetchReferrals(status: statusParam, q: state.query, page: nextPage);
      final hasMore = (res.current_page ?? nextPage) < (res.last_page ?? nextPage);
      state = state.copyWith(
        loading: false,
        items: [...state.items, ...res.data],
        hasMore: hasMore,
        page: nextPage,
      );
    } catch (e) {
      state = state.copyWith(loading: false, error: 'Failed to load more');
    }
  }
}