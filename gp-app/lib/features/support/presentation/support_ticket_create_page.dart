import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/core/http/dio_client.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_card.dart';
import 'package:gp_app/ui/widgets/primary_button.dart';

class SupportTicketCreatePage extends ConsumerStatefulWidget {
  const SupportTicketCreatePage({super.key});

  @override
  ConsumerState<SupportTicketCreatePage> createState() => _SupportTicketCreatePageState();
}

class _SupportTicketCreatePageState extends ConsumerState<SupportTicketCreatePage> {
  final _formKey = GlobalKey<FormState>();
  final _subject = TextEditingController();
  final _message = TextEditingController();
  String _category = 'general';
  String _priority = 'medium';
  bool _submitting = false;
  final List<PlatformFile> _attachments = [];

  static const _categories = {
    'general': 'General',
    'account': 'Account',
    'referral': 'Referral',
    'diagnostic': 'Diagnostic',
    'billing': 'Billing',
    'technical': 'Technical',
    'notification': 'Notification',
    'other': 'Other',
  };

  static const _priorities = {
    'low': 'Low',
    'medium': 'Medium',
    'high': 'High',
    'urgent': 'Urgent',
  };

  @override
  void dispose() {
    _subject.dispose();
    _message.dispose();
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

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _submitting = true);

    try {
      final ticket = await ref.read(supportRepositoryProvider).createTicket(
            subject: _subject.text.trim(),
            category: _category,
            priority: _priority,
            message: _message.text.trim(),
            attachments: _attachments,
          );

      if (!mounted) return;
      await ref.read(supportTicketListControllerProvider.notifier).refresh();
      if (!mounted) return;
      context.go('/support/${ticket.id}');
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(extractApiErrorMessage(e, fallback: 'Failed to create support ticket'))),
      );
      setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Create Ticket', style: TextStyle(fontWeight: FontWeight.w600)),
        centerTitle: true,
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(AppSpacing.md),
          children: [
            AppCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Ticket Details', style: AppStyles.heading2),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _subject,
                    decoration: const InputDecoration(
                      labelText: 'Subject',
                      hintText: 'Briefly describe your issue',
                    ),
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Subject is required' : null,
                  ),
                  const SizedBox(height: 16),
                  DropdownButtonFormField<String>(
                    initialValue: _category,
                    decoration: const InputDecoration(labelText: 'Category'),
                    items: _categories.entries
                        .map((e) => DropdownMenuItem<String>(value: e.key, child: Text(e.value)))
                        .toList(),
                    onChanged: _submitting ? null : (v) => setState(() => _category = v ?? 'general'),
                  ),
                  const SizedBox(height: 16),
                  DropdownButtonFormField<String>(
                    initialValue: _priority,
                    decoration: const InputDecoration(labelText: 'Priority'),
                    items: _priorities.entries
                        .map((e) => DropdownMenuItem<String>(value: e.key, child: Text(e.value)))
                        .toList(),
                    onChanged: _submitting ? null : (v) => setState(() => _priority = v ?? 'medium'),
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _message,
                    minLines: 5,
                    maxLines: 8,
                    decoration: const InputDecoration(
                      labelText: 'Message',
                      hintText: 'Explain the issue, steps, and expected behavior',
                    ),
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Message is required' : null,
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            AppCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(child: Text('Attachments', style: AppStyles.heading2)),
                      TextButton.icon(
                        onPressed: _submitting ? null : _pickAttachments,
                        icon: const Icon(Icons.attach_file_rounded),
                        label: const Text('Add Files'),
                      ),
                    ],
                  ),
                  Text(
                    'Optional. Up to 5 files.',
                    style: AppStyles.bodySmall,
                  ),
                  if (_attachments.isNotEmpty) ...[
                    const SizedBox(height: 12),
                    ..._attachments.map(
                      (file) => Padding(
                        padding: const EdgeInsets.only(bottom: 8),
                        child: Row(
                          children: [
                            const Icon(Icons.insert_drive_file_outlined, size: 18, color: AppColors.textGrey),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                file.name,
                                style: AppStyles.bodySmall.copyWith(color: AppColors.textPrimary),
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            ),
            const SizedBox(height: 20),
            PrimaryButton(
              label: _submitting ? 'Submitting...' : 'Submit Ticket',
              onPressed: _submitting ? null : _submit,
            ),
          ],
        ),
      ),
    );
  }
}
