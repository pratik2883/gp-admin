import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:gp_app/core/http/dio_client.dart';
import 'package:gp_app/features/support/data/support_repository.dart';
import 'package:gp_app/features/support/state/support_ticket_list_state.dart';

class SupportTicketListController extends StateNotifier<SupportTicketListState> {
  final SupportRepository _repo;

  SupportTicketListController(this._repo) : super(const SupportTicketListState());

  Future<void> refresh() async {
    state = state.copyWith(
      loading: state.items.isEmpty,
      refreshing: state.items.isNotEmpty,
      clearError: true,
    );

    try {
      final items = await _repo.fetchTickets();
      state = state.copyWith(
        loading: false,
        refreshing: false,
        items: items,
        clearError: true,
      );
    } catch (e) {
      state = state.copyWith(
        loading: false,
        refreshing: false,
        error: extractApiErrorMessage(e, fallback: 'Failed to load support tickets'),
      );
    }
  }
}
