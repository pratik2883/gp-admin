import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:gp_app/features/notifications/data/notification_repository.dart';

final unreadCountProvider = StateNotifierProvider<UnreadCountController, int>((ref) {
  return UnreadCountController(ref.read(notificationRepositoryProvider));
});

class UnreadCountController extends StateNotifier<int> {
  final NotificationRepository _repo;
  UnreadCountController(this._repo) : super(0) {
    refresh();
  }

  Future<void> refresh() async {
    try {
      final count = await _repo.fetchUnreadCount();
      state = count;
    } catch (_) {}
  }
  
  void decrement() {
      if (state > 0) state--;
  }

  void increment() {
      state++;
  }
}
