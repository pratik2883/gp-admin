import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/core/http/dio_client.dart';
import 'package:gp_app/features/support/models/support_ticket.dart';
import 'package:gp_app/features/support/models/support_ticket_attachment.dart';
import 'package:gp_app/features/support/models/support_ticket_message.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_card.dart';
import 'package:gp_app/ui/widgets/app_status_chip.dart';
import 'package:gp_app/ui/widgets/primary_button.dart';
import 'package:gp_app/ui/widgets/states.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';

class SupportTicketDetailPage extends ConsumerStatefulWidget {
  final int ticketId;

  const SupportTicketDetailPage({
    super.key,
    required this.ticketId,
  });

  @override
  ConsumerState<SupportTicketDetailPage> createState() => _SupportTicketDetailPageState();
}

class _SupportTicketDetailPageState extends ConsumerState<SupportTicketDetailPage> {
  final _reply = TextEditingController();
  final List<PlatformFile> _attachments = [];
  bool _sending = false;

  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(supportTicketDetailControllerProvider(widget.ticketId).notifier).load(widget.ticketId));
  }

  @override
  void dispose() {
    _reply.dispose();
    super.dispose();
  }

  Future<void> _pickAttachments() async {
    final res = await FilePicker.platform.pickFiles(
      allowMultiple: true,
      type: FileType.custom,
      allowedExtensions: const ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'txt'],
    );

    if (!mounted || res == null) return;

    setState(() {
      _attachments
        ..clear()
        ..addAll(res.files.where((f) => f.path != null && f.path!.isNotEmpty).take(5));
    });
  }

  Future<void> _sendReply(SupportTicket ticket) async {
    final message = _reply.text.trim();
    if (message.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Reply message is required')),
      );
      return;
    }

    setState(() => _sending = true);

    try {
      await ref.read(supportTicketDetailControllerProvider(widget.ticketId).notifier).reply(
            ticketId: widget.ticketId,
            message: message,
            filePaths: _attachments.map((e) => e.path!).toList(),
          );
      await ref.read(supportTicketListControllerProvider.notifier).refresh();
      if (!mounted) return;
      _reply.clear();
      setState(() {
        _attachments.clear();
        _sending = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _sending = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(extractApiErrorMessage(e, fallback: 'Failed to send reply'))),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(supportTicketDetailControllerProvider(widget.ticketId));

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Ticket Detail', style: TextStyle(fontWeight: FontWeight.w600)),
        centerTitle: true,
      ),
      body: state.when(
        loading: () => const LoadingState(message: 'Loading ticket...'),
        error: (error, _) => Padding(
          padding: const EdgeInsets.all(AppSpacing.md),
          child: ErrorState(
            message: extractApiErrorMessage(error, fallback: 'Failed to load ticket'),
            onRetry: () => ref.read(supportTicketDetailControllerProvider(widget.ticketId).notifier).load(widget.ticketId),
          ),
        ),
        data: (ticket) {
          if (ticket == null) {
            return const EmptyState(
              message: 'Ticket not found.',
              subtitle: 'The ticket may no longer be available.',
            );
          }

          return Column(
            children: [
              Expanded(
                child: RefreshIndicator(
                  color: AppColors.primaryBlue,
                  onRefresh: () => ref.read(supportTicketDetailControllerProvider(widget.ticketId).notifier).load(widget.ticketId),
                  child: ListView(
                    padding: const EdgeInsets.all(AppSpacing.md),
                    children: [
                      _TicketSummaryCard(ticket: ticket),
                      const SizedBox(height: 12),
                      ...ticket.messages.map((message) => _MessageBubble(message: message)),
                      if (ticket.messages.isEmpty)
                        const AppCard(
                          child: EmptyState(
                            message: 'No replies yet.',
                            subtitle: 'Updates from admin will appear here.',
                            icon: Icons.chat_bubble_outline_rounded,
                          ),
                        ),
                    ],
                  ),
                ),
              ),
              if (ticket.canReply) _ReplyComposer(
                controller: _reply,
                attachments: _attachments,
                sending: _sending,
                onPickAttachments: _pickAttachments,
                onSend: () => _sendReply(ticket),
              ) else
                const Padding(
                  padding: EdgeInsets.fromLTRB(16, 0, 16, 16),
                  child: AppCard(
                    child: Text(
                      'This ticket is closed. You can create a new ticket if you need more help.',
                      style: TextStyle(color: AppColors.textGrey),
                    ),
                  ),
                ),
            ],
          );
        },
      ),
    );
  }
}

class _TicketSummaryCard extends StatelessWidget {
  final SupportTicket ticket;

  const _TicketSummaryCard({required this.ticket});

  @override
  Widget build(BuildContext context) {
    final createdText = ticket.createdAt == null ? '-' : DateFormat('dd MMM yyyy, h:mm a').format(ticket.createdAt!);
    final updatedText = (ticket.lastReplyAt ?? ticket.updatedAt) == null
        ? '-'
        : DateFormat('dd MMM yyyy, h:mm a').format((ticket.lastReplyAt ?? ticket.updatedAt)!);

    return AppCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(child: Text(ticket.subject, style: AppStyles.heading2)),
              const SizedBox(width: 10),
              AppStatusChip(status: ticket.status),
            ],
          ),
          const SizedBox(height: 8),
          Text(ticket.ticketNo, style: AppStyles.bodyLarge.copyWith(color: AppColors.primaryBlue)),
          const SizedBox(height: 10),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _InfoChip(label: _labelize(ticket.category ?? 'general')),
              _InfoChip(label: _labelize(ticket.priority)),
              if (ticket.assignedAdminName != null && ticket.assignedAdminName!.trim().isNotEmpty)
                _InfoChip(label: 'Admin: ${ticket.assignedAdminName!}'),
            ],
          ),
          const SizedBox(height: 12),
          Text('Created: $createdText', style: AppStyles.bodySmall),
          const SizedBox(height: 4),
          Text('Last update: $updatedText', style: AppStyles.bodySmall),
        ],
      ),
    );
  }

  static String _labelize(String value) {
    final clean = value.replaceAll('_', ' ').trim();
    if (clean.isEmpty) return value;
    return clean[0].toUpperCase() + clean.substring(1);
  }
}

class _InfoChip extends StatelessWidget {
  final String label;

  const _InfoChip({required this.label});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: AppColors.surfaceMuted,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: AppColors.border),
      ),
      child: Text(label, style: AppStyles.chipText),
    );
  }
}

class _MessageBubble extends StatelessWidget {
  final SupportTicketMessage message;

  const _MessageBubble({required this.message});

  @override
  Widget build(BuildContext context) {
    final isAdmin = message.senderType == 'admin';
    final createdText = message.createdAt == null ? '' : DateFormat('dd MMM, h:mm a').format(message.createdAt!);

    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Align(
        alignment: isAdmin ? Alignment.centerLeft : Alignment.centerRight,
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 340),
          child: Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: isAdmin ? AppColors.cardWhite : AppColors.primaryBlue.withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: isAdmin ? AppColors.borderLight : AppColors.primaryBlue.withValues(alpha: 0.14)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  isAdmin ? (message.senderName?.trim().isNotEmpty == true ? message.senderName! : 'Admin') : 'You',
                  style: AppStyles.bodyLarge.copyWith(
                    color: isAdmin ? AppColors.textPrimary : AppColors.primaryBlue,
                  ),
                ),
                if (createdText.isNotEmpty) ...[
                  const SizedBox(height: 2),
                  Text(createdText, style: AppStyles.caption),
                ],
                const SizedBox(height: 10),
                Text(message.message, style: AppStyles.bodyMedium.copyWith(height: 1.5)),
                if (message.attachments.isNotEmpty) ...[
                  const SizedBox(height: 10),
                  ...message.attachments.map((attachment) => _AttachmentRow(attachment: attachment)),
                ],
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _AttachmentRow extends StatelessWidget {
  final SupportTicketAttachment attachment;

  const _AttachmentRow({required this.attachment});

  Future<void> _open(BuildContext context) async {
    final url = attachment.url;
    if (url == null || url.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Attachment URL is not available')),
      );
      return;
    }

    final uri = Uri.tryParse(url);
    if (uri == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Invalid attachment URL')),
      );
      return;
    }

    try {
      final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
      if (!ok && context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Unable to open attachment')),
        );
      }
    } catch (e) {
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(extractApiErrorMessage(e, fallback: 'Unable to open attachment'))),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 6),
      child: InkWell(
        borderRadius: BorderRadius.circular(10),
        onTap: () => _open(context),
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 6, horizontal: 8),
          child: Row(
            children: [
              const Icon(Icons.attach_file_rounded, size: 16, color: AppColors.textGrey),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  attachment.originalName,
                  style: AppStyles.bodySmall.copyWith(
                    color: AppColors.primaryBlue,
                    decoration: TextDecoration.underline,
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              const SizedBox(width: 8),
              const Icon(Icons.open_in_new_rounded, size: 16, color: AppColors.textGrey),
            ],
          ),
        ),
      ),
    );
  }
}

class _ReplyComposer extends StatelessWidget {
  final TextEditingController controller;
  final List<PlatformFile> attachments;
  final bool sending;
  final VoidCallback onPickAttachments;
  final VoidCallback onSend;

  const _ReplyComposer({
    required this.controller,
    required this.attachments,
    required this.sending,
    required this.onPickAttachments,
    required this.onSend,
  });

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      top: false,
      child: Container(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
        child: AppCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              TextField(
                controller: controller,
                minLines: 3,
                maxLines: 5,
                decoration: const InputDecoration(
                  labelText: 'Reply',
                  hintText: 'Write your reply to the admin team',
                ),
              ),
              const SizedBox(height: 10),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  OutlinedButton.icon(
                    onPressed: sending ? null : onPickAttachments,
                    icon: const Icon(Icons.attach_file_rounded),
                    label: Text(attachments.isEmpty ? 'Add Files' : '${attachments.length} file(s)'),
                  ),
                  ...attachments.map((file) => Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                        decoration: BoxDecoration(
                          color: AppColors.surfaceMuted,
                          borderRadius: BorderRadius.circular(999),
                        ),
                        child: Text(
                          file.name,
                          style: AppStyles.caption.copyWith(color: AppColors.textPrimary),
                        ),
                      )),
                ],
              ),
              const SizedBox(height: 12),
              PrimaryButton(
                label: sending ? 'Sending...' : 'Send Reply',
                onPressed: sending ? null : onSend,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
