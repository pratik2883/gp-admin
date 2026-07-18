import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/features/support/models/support_ticket.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_card.dart';
import 'package:gp_app/ui/widgets/app_status_chip.dart';
import 'package:gp_app/ui/widgets/primary_button.dart';
import 'package:gp_app/ui/widgets/states.dart';
import 'package:intl/intl.dart';

class SupportPage extends ConsumerStatefulWidget {
  const SupportPage({super.key});

  @override
  ConsumerState<SupportPage> createState() => _SupportPageState();
}

class _SupportPageState extends ConsumerState<SupportPage> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(supportTicketListControllerProvider.notifier).refresh());
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(supportTicketListControllerProvider);

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Support Tickets', style: TextStyle(fontWeight: FontWeight.w600)),
        centerTitle: true,
        actions: [
          IconButton(
            onPressed: () async {
              await context.push('/support/new');
              if (mounted) {
                ref.read(supportTicketListControllerProvider.notifier).refresh();
              }
            },
            icon: const Icon(Icons.add_comment_outlined),
          ),
        ],
      ),
      body: RefreshIndicator(
        color: AppColors.primaryBlue,
        onRefresh: () => ref.read(supportTicketListControllerProvider.notifier).refresh(),
        child: Builder(
          builder: (context) {
            if (state.loading && state.items.isEmpty) {
              return const LoadingState(message: 'Loading support tickets...');
            }

            if (state.error != null && state.items.isEmpty) {
              return Padding(
                padding: const EdgeInsets.all(AppSpacing.md),
                child: ErrorState(
                  message: state.error!,
                  title: 'We couldn’t load your tickets.',
                  onRetry: () => ref.read(supportTicketListControllerProvider.notifier).refresh(),
                ),
              );
            }

            if (state.items.isEmpty) {
              return ListView(
                padding: const EdgeInsets.all(AppSpacing.md),
                children: [
                  const SizedBox(height: 32),
                  const AppCard(
                    child: EmptyState(
                      message: 'No support tickets yet.',
                      subtitle: 'Create a ticket whenever you need help from the admin team.',
                      icon: Icons.support_agent_rounded,
                    ),
                  ),
                  const SizedBox(height: 20),
                  PrimaryButton(
                    label: 'Create Support Ticket',
                    onPressed: () async {
                      await context.push('/support/new');
                      if (mounted) {
                        ref.read(supportTicketListControllerProvider.notifier).refresh();
                      }
                    },
                  ),
                ],
              );
            }

            return ListView.separated(
              padding: const EdgeInsets.all(AppSpacing.md),
              itemCount: state.items.length + 1,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                if (index == 0) {
                  return PrimaryButton(
                    label: 'Create New Ticket',
                    onPressed: () async {
                      await context.push('/support/new');
                      if (mounted) {
                        ref.read(supportTicketListControllerProvider.notifier).refresh();
                      }
                    },
                  );
                }

                final ticket = state.items[index - 1];
                return _TicketCard(ticket: ticket, onTap: () => context.push('/support/${ticket.id}'));
              },
            );
          },
        ),
      ),
    );
  }
}

class _TicketCard extends StatelessWidget {
  final SupportTicket ticket;
  final VoidCallback onTap;

  const _TicketCard({
    required this.ticket,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final date = ticket.lastReplyAt ?? ticket.createdAt;
    final dateText = date == null ? 'No replies yet' : DateFormat('dd MMM, h:mm a').format(date);

    return AppCard(
      onTap: onTap,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  ticket.subject,
                  style: AppStyles.heading2,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              const SizedBox(width: 10),
              AppStatusChip(status: ticket.status),
            ],
          ),
          const SizedBox(height: 8),
          Text(ticket.ticketNo, style: AppStyles.bodyLarge.copyWith(color: AppColors.primaryBlue)),
          const SizedBox(height: 6),
          Text(
            '${_labelize(ticket.category ?? 'general')} · ${_labelize(ticket.priority)}',
            style: AppStyles.bodySmall,
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              const Icon(Icons.schedule_outlined, size: 16, color: AppColors.textGrey),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  dateText,
                  style: AppStyles.bodySmall,
                ),
              ),
              const Icon(Icons.chevron_right_rounded, color: AppColors.textGrey),
            ],
          ),
        ],
      ),
    );
  }

  String _labelize(String value) {
    final clean = value.replaceAll('_', ' ').trim();
    if (clean.isEmpty) return value;
    return clean[0].toUpperCase() + clean.substring(1);
  }
}
