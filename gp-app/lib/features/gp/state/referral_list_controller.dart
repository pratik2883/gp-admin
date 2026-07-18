import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:gp_app/features/gp/data/gp_repository.dart';
import 'package:gp_app/features/gp/state/referral_list_state.dart';

class ReferralListController extends StateNotifier<ReferralListState> {
  final GpRepository _repo;
  ReferralListController(this._repo) : super(const ReferralListState());

  Future<void> setReferralType(String type) async {
    final v = type.toLowerCase().trim();
    final normalized = (v == 'diagnostic' || v == 'hospital') ? v : 'specialist';
    if (normalized == state.referralType) return;
    state = state.copyWith(referralType: normalized);
    await refresh();
  }

  Future<void> refresh({String? q}) async {
    state = state.copyWith(refreshing: true, query: q ?? state.query, page: 1, error: null);
    try {
      final res = await _repo.fetchReferrals(referralType: state.referralType, q: state.query, page: 1);
      final hasMore = (res.current_page ?? 1) < (res.last_page ?? 1);
      state = state.copyWith(
        refreshing: false, 
        items: res.data, 
        hasMore: hasMore, 
        page: 1, 
        totalSubmitted: res.total ?? 0,
        conversionRate: 0.0,
      );
    } catch (e) {
      state = state.copyWith(refreshing: false, error: 'Failed to load referrals');
    }
  }

  Future<void> loadMore() async {
    if (state.loading || !state.hasMore) return;
    state = state.copyWith(loading: true, error: null);
    try {
      final nextPage = state.page + 1;
      final res = await _repo.fetchReferrals(referralType: state.referralType, q: state.query, page: nextPage);
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
