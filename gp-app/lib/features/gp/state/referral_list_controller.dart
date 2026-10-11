import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:gp_app/features/gp/data/gp_repository.dart';
import 'package:gp_app/features/gp/state/referral_list_state.dart';

class ReferralListController extends StateNotifier<ReferralListState> {
  final GpRepository _repo;
  ReferralListController(this._repo) : super(const ReferralListState());

  /// Monotonic id for the newest list request. Any response whose captured id no
  /// longer matches is stale and must not mutate state (M4-16).
  int _requestId = 0;

  /// Cancel token of the in-flight first-page request, cancelled whenever a newer
  /// one starts so slow responses cannot land out of order (M4-09/M4-16).
  CancelToken? _refreshToken;

  Future<void> setReferralType(String type) async {
    final v = type.toLowerCase().trim();
    final normalized = (v == 'diagnostic' || v == 'hospital') ? v : 'specialist';
    if (normalized == state.referralType) return;
    state = state.copyWith(referralType: normalized);
    await refresh();
  }

  Future<void> refresh({String? q}) async {
    final requestId = ++_requestId;
    _refreshToken?.cancel('superseded by a newer query');
    final token = CancelToken();
    _refreshToken = token;

    state = state.copyWith(
      refreshing: true,
      query: q ?? state.query,
      page: 1,
      error: null,
    );
    try {
      final res = await _repo.fetchReferrals(
        referralType: state.referralType,
        q: state.query,
        page: 1,
        cancelToken: token,
      );
      if (requestId != _requestId) return; // superseded
      final hasMore = (res.current_page ?? 1) < (res.last_page ?? 1);
      state = state.copyWith(
        refreshing: false,
        loading: false,
        items: res.data,
        hasMore: hasMore,
        page: 1,
        totalSubmitted: res.total ?? 0,
        conversionRate: 0.0,
      );
    } on DioException catch (e) {
      if (requestId != _requestId || CancelToken.isCancel(e)) return;
      state = state.copyWith(
        refreshing: false,
        loading: false,
        error: 'Failed to load referrals',
      );
    } catch (_) {
      if (requestId != _requestId) return;
      state = state.copyWith(
        refreshing: false,
        loading: false,
        error: 'Failed to load referrals',
      );
    }
  }

  Future<void> loadMore() async {
    if (state.loading || !state.hasMore) return;
    final requestId = _requestId; // a refresh() bumps this to invalidate the append
    state = state.copyWith(loading: true, error: null);
    try {
      final nextPage = state.page + 1;
      final res = await _repo.fetchReferrals(
        referralType: state.referralType,
        q: state.query,
        page: nextPage,
      );
      if (requestId != _requestId) return; // a refresh() superseded this page
      final hasMore = (res.current_page ?? nextPage) < (res.last_page ?? nextPage);
      state = state.copyWith(
        loading: false,
        items: [...state.items, ...res.data],
        hasMore: hasMore,
        page: nextPage,
      );
    } catch (e) {
      if (requestId != _requestId) return;
      state = state.copyWith(loading: false, error: 'Failed to load more');
    }
  }

  @override
  void dispose() {
    _refreshToken?.cancel('controller disposed');
    super.dispose();
  }
}
