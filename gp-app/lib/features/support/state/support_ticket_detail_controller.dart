import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:gp_app/features/support/data/support_repository.dart';
import 'package:gp_app/features/support/models/support_ticket.dart';

class SupportTicketDetailController extends StateNotifier<AsyncValue<SupportTicket?>> {
  final SupportRepository _repo;

  SupportTicketDetailController(this._repo) : super(const AsyncValue.loading());

  Future<void> load(int ticketId) async {
    try {
      final ticket = await _repo.fetchTicket(ticketId);
      state = AsyncValue.data(ticket);
    } catch (e, stack) {
      state = AsyncValue.error(e, stack);
    }
  }

  Future<SupportTicket> reply({
    required int ticketId,
    required String message,
    List<String> filePaths = const [],
  }) async {
    final ticket = await _repo.replyToTicket(
      ticketId: ticketId,
      message: message,
      filePaths: filePaths,
    );
    state = AsyncValue.data(ticket);
    return ticket;
  }
}
