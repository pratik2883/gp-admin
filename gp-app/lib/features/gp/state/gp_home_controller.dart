import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:gp_app/core/http/dio_client.dart';
import 'package:gp_app/features/gp/data/gp_repository.dart';
import 'package:gp_app/features/gp/state/gp_home_state.dart';

class GpHomeController extends StateNotifier<GpHomeState> {
  final GpRepository _repo;
  GpHomeController(this._repo) : super(const GpHomeState.loading());

  Future<void> load() async {
    state = const GpHomeState.loading();
    try {
      final data = await _repo.fetchDashboard();
      state = GpHomeState.loaded(data);
    } catch (e, stack) {
      print('Dashboard Error: $e');
      print('Stack: $stack');
      final msg = extractApiErrorMessage(e, fallback: 'Failed to load dashboard');
      state = GpHomeState.error(msg);
      if (e is DioException && e.response?.statusCode == 403) {
        state = GpHomeState.notApproved(msg);
      }
    }
  }
}
