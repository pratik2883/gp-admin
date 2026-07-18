import 'package:gp_app/features/support/models/support_ticket.dart';

class SupportTicketListState {
  final bool loading;
  final bool refreshing;
  final List<SupportTicket> items;
  final String? error;

  const SupportTicketListState({
    this.loading = false,
    this.refreshing = false,
    this.items = const [],
    this.error,
  });

  SupportTicketListState copyWith({
    bool? loading,
    bool? refreshing,
    List<SupportTicket>? items,
    String? error,
    bool clearError = false,
  }) {
    return SupportTicketListState(
      loading: loading ?? this.loading,
      refreshing: refreshing ?? this.refreshing,
      items: items ?? this.items,
      error: clearError ? null : (error ?? this.error),
    );
  }
}
