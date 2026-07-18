import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_hero_header.dart';
import 'package:gp_app/ui/widgets/app_notification_card.dart';
import 'package:gp_app/ui/widgets/app_card.dart';
import 'package:gp_app/ui/widgets/states.dart';
import 'package:gp_app/ui/widgets/home_skeletons.dart';
import 'package:gp_app/features/notifications/state/notification_list_state.dart';
import 'package:gp_app/features/notifications/state/unread_count_controller.dart';
import 'package:gp_app/app/role.dart';
import 'package:intl/intl.dart';

class NotificationsPage extends ConsumerStatefulWidget {
  const NotificationsPage({super.key});

  @override
  ConsumerState<NotificationsPage> createState() => _NotificationsPageState();
}

class _NotificationsPageState extends ConsumerState<NotificationsPage> {
  final _scroll = ScrollController();

  @override
  void initState() {
    super.initState();
    Future.microtask(() {
      ref.read(notificationListControllerProvider.notifier).refresh();
      ref.read(unreadCountProvider.notifier).refresh();
    });
    _scroll.addListener(() {
      if (_scroll.position.pixels >= _scroll.position.maxScrollExtent - 100) {
        ref.read(notificationListControllerProvider.notifier).loadMore();
      }
    });
  }

  @override
  void dispose() {
    _scroll.dispose();
    super.dispose();
  }

  void _handleTap(dynamic notification) {
    if (!notification.isRead) {
      ref.read(notificationListControllerProvider.notifier).markAsRead(notification.id);
      ref.read(unreadCountProvider.notifier).decrement();
    }
    final targetId = notification.targetId;
    if (targetId != null) {
      final dataType = notification.data['type']?.toString();
      if (dataType == 'support_ticket_reply' || dataType == 'support_ticket_closed') {
        context.push('/support/$targetId');
        return;
      }
      final role = ref.read(selectedRoleProvider);
      if (role == AppRole.gp) {
        context.push('/gp/referrals/$targetId');
      } else if (role == AppRole.specialist) {
        context.push('/sp/leads/$targetId');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(notificationListControllerProvider);
    final isInitialLoading = state.refreshing && state.items.isEmpty;
    final viewKey = isInitialLoading
        ? 'loading'
        : (state.error != null && state.items.isEmpty)
            ? 'error'
            : state.items.isEmpty
                ? 'empty'
                : 'list';

    return Scaffold(
      backgroundColor: AppColors.background,
      body: Column(
        children: [
          const AppHeroHeader(
            title: 'Notifications',
            subtitle: 'Updates and activity',
          ),
          Expanded(
            child: RefreshIndicator(
              color: AppColors.primaryBlue,
              onRefresh: () => ref.read(notificationListControllerProvider.notifier).refresh(),
              child: AnimatedSwitcher(
                duration: const Duration(milliseconds: 220),
                switchInCurve: Curves.easeOut,
                switchOutCurve: Curves.easeIn,
                child: KeyedSubtree(
                  key: ValueKey(viewKey),
                  child: _buildBody(state),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBody(NotificationState state) {
    if (state.refreshing && state.items.isEmpty) {
      return const NotificationsListSkeleton(count: 6);
    }
    if (state.error != null && state.items.isEmpty) {
      return Padding(
        padding: const EdgeInsets.all(AppSpacing.md),
        child: AppCard(
          child: ErrorState(
            message: state.error!,
            title: 'We couldn’t load your notifications.',
            subtitle: 'Check your connection and try again.',
            onRetry: () => ref.read(notificationListControllerProvider.notifier).refresh(),
          ),
        ),
      );
    }
    if (state.items.isEmpty) {
      return const Padding(
        padding: EdgeInsets.all(AppSpacing.md),
        child: AppCard(
          child: EmptyState(
            message: 'You’re all caught up.',
            subtitle: 'New updates will appear here.',
            icon: Icons.notifications_none_rounded,
          ),
        ),
      );
    }

    return ListView.separated(
      controller: _scroll,
      padding: const EdgeInsets.all(AppSpacing.md),
      itemCount: state.items.length + (state.hasMore ? 1 : 0),
      separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.md),
      itemBuilder: (context, i) {
        if (i == state.items.length) {
          return const Padding(
            padding: EdgeInsets.symmetric(vertical: AppSpacing.md),
            child: Center(child: CircularProgressIndicator(color: AppColors.primaryBlue)),
          );
        }
        final n = state.items[i];
        final dateStr =
            n.created_at != null ? DateFormat('MMM dd, yyyy h:mm a').format(n.created_at!) : '';

        return AppNotificationCard(
          title: n.title,
          body: n.body,
          dateStr: dateStr,
          isRead: n.isRead,
          onTap: () => _handleTap(n),
        );
      },
    );
  }
}
