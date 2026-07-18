import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:gp_app/features/notifications/data/notification_repository.dart';
import 'package:gp_app/features/notifications/models/app_notification.dart';

class NotificationState {
  final List<AppNotification> items;
  final bool isLoading;
  final bool hasMore;
  final int page;
  final String? error;
  final bool refreshing;

  NotificationState({
    this.items = const [],
    this.isLoading = false,
    this.hasMore = true,
    this.page = 1,
    this.error,
    this.refreshing = false,
  });

  NotificationState copyWith({
    List<AppNotification>? items,
    bool? isLoading,
    bool? hasMore,
    int? page,
    String? error,
    bool? refreshing,
  }) {
    return NotificationState(
      items: items ?? this.items,
      isLoading: isLoading ?? this.isLoading,
      hasMore: hasMore ?? this.hasMore,
      page: page ?? this.page,
      error: error,
      refreshing: refreshing ?? this.refreshing,
    );
  }
}

final notificationListControllerProvider = StateNotifierProvider<NotificationListController, NotificationState>((ref) {
  return NotificationListController(ref.read(notificationRepositoryProvider));
});

class NotificationListController extends StateNotifier<NotificationState> {
  final NotificationRepository _repo;
  NotificationListController(this._repo) : super(NotificationState()) {
    refresh();
  }

  Future<void> refresh() async {
    state = state.copyWith(refreshing: true, error: null);
    try {
      final res = await _repo.fetchNotifications(page: 1);
      state = state.copyWith(
        items: res.data,
        page: 1,
        hasMore: res.currentPage < res.lastPage,
        refreshing: false,
      );
    } catch (e) {
      state = state.copyWith(error: e.toString(), refreshing: false);
    }
  }

  Future<void> loadMore() async {
    if (state.isLoading || state.refreshing || !state.hasMore) return;
    state = state.copyWith(isLoading: true, error: null);
    try {
      final nextPage = state.page + 1;
      final res = await _repo.fetchNotifications(page: nextPage);
      state = state.copyWith(
        items: [...state.items, ...res.data],
        page: nextPage,
        hasMore: res.currentPage < res.lastPage,
        isLoading: false,
      );
    } catch (e) {
      state = state.copyWith(error: e.toString(), isLoading: false);
    }
  }
  
  Future<void> markAsRead(String id) async {
      await _repo.markAsRead(id);
      final idx = state.items.indexWhere((e) => e.id == id);
      if (idx != -1) {
          final updated = List<AppNotification>.from(state.items);
          updated[idx] = updated[idx].copyWith(read_at: DateTime.now());
          state = state.copyWith(items: updated);
      }
  }
}
