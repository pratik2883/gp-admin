import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/ui/widgets/app_hero_header.dart';
import 'package:gp_app/ui/widgets/app_card.dart';
import 'package:gp_app/ui/widgets/app_status_chip.dart';
import 'package:gp_app/ui/widgets/section_header_row.dart';
import 'package:gp_app/ui/widgets/primary_button.dart';
import 'package:gp_app/ui/widgets/states.dart';
import 'package:gp_app/app/role.dart';

class SpLeadDetailPage extends ConsumerStatefulWidget {
  final String leadId;
  const SpLeadDetailPage({super.key, required this.leadId});
  @override
  ConsumerState<SpLeadDetailPage> createState() => _SpLeadDetailPageState();
}

class _SpLeadDetailPageState extends ConsumerState<SpLeadDetailPage> {
  bool _updating = false;
  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(spLeadDetailControllerProvider.notifier).load(widget.leadId));
  }

  Future<void> _setStatus(String status) async {
    setState(() => _updating = true);
    await ref.read(spLeadDetailControllerProvider.notifier).updateStatus(widget.leadId, status);
    if (mounted) setState(() => _updating = false);
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Status updated to $status')));
      ref.read(spLeadListControllerProvider.notifier).refresh();
      context.pop();
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(spLeadDetailControllerProvider);
    final subtype = ref.watch(selectedSubtypeProvider);
    
    final detailTitle = switch (subtype) {
      RoleSubtype.hospital => 'Lead Detail',
      RoleSubtype.diagnostic => 'Diagnostic Detail',
      _ => 'Lead Detail',
    };

    return Scaffold(
      backgroundColor: AppColors.background,
      body: Column(
        children: [
          AppHeroHeader(
            leading: IconButton(
              onPressed: () => context.pop(),
              icon: const Icon(Icons.arrow_back_rounded, color: AppColors.textOnDark),
            ),
            title: detailTitle,
            subtitle: 'ID #${widget.leadId}',
          ),
          Expanded(
            child: AnimatedSwitcher(
              duration: const Duration(milliseconds: 220),
              child: state.when(
                loading: () => const Center(child: CircularProgressIndicator(color: AppColors.primaryBlue)),
                error: (m) => Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: ErrorState(
                      message: m, 
                      onRetry: () => ref.read(spLeadDetailControllerProvider.notifier).load(widget.leadId)
                    ),
                  ),
                ),
                loaded: (l) => ListView(
                  padding: AppSpacing.responsiveScreenPadding(context).copyWith(top: 18, bottom: 40),
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            l.patient_name ?? 'Unknown Patient', 
                            style: AppStyles.heading2,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        const SizedBox(width: 10),
                        AppStatusChip(status: l.status ?? 'pending'),
                      ],
                    ),
                    const SizedBox(height: 12),
                    AppCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          _infoRow('Patient ID', l.patient_mobile ?? 'N/A'),
                          _infoRow('Referred By', l.gp_name ?? 'Unknown GP'),
                          _infoRow('Location', l.hospital_name ?? 'N/A'),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                    AppCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const SectionHeaderRow(title: 'Case Summary'),
                          const SizedBox(height: 12),
                          Text(
                            l.notes ?? 'No detailed notes provided.',
                            style: AppStyles.bodyMedium.copyWith(height: 1.5),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                    AppCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const SectionHeaderRow(title: 'Attachments'),
                          const SizedBox(height: 12),
                          if (l.attachments.isEmpty)
                            Text('No attachments available.', style: AppStyles.bodySmall.copyWith(color: AppColors.textSecondary))
                          else
                            ...l.attachments.map((a) => Padding(
                              padding: const EdgeInsets.only(bottom: 8),
                              child: ListTile(
                                dense: true,
                                tileColor: AppColors.background,
                                shape: RoundedRectangleBorder(borderRadius: AppStyles.radiusInput),
                                leading: const Icon(Icons.insert_drive_file, color: AppColors.primaryBlue),
                                title: Text(a.name ?? 'File', style: AppStyles.bodyMedium),
                                trailing: const Icon(Icons.download_rounded, size: 18),
                                onTap: () {},
                              ),
                            )),
                        ],
                      ),
                    ),
                    const SizedBox(height: 32),
                    if (_updating)
                      const Center(child: CircularProgressIndicator(color: AppColors.primaryBlue))
                    else
                      _buildActionButtons(l),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _infoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: AppStyles.bodySmall.copyWith(color: AppColors.textSecondary)),
          Text(value, style: AppStyles.bodyMedium.copyWith(fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }

  Widget _buildActionButtons(l) {
    final status = (l.status ?? 'pending').toLowerCase();

    if (status == 'pending' || status == 'new') {
      return Row(
        children: [
          Expanded(
            child: OutlinedButton(
              onPressed: () => _setStatus('rejected'),
              style: OutlinedButton.styleFrom(
                minimumSize: const Size(0, 52),
                foregroundColor: AppColors.statusError,
              ),
              child: const Text('Decline Lead'),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: PrimaryButton(
              label: 'Accept Lead',
              onPressed: () => _setStatus('accepted'),
            ),
          ),
        ],
      );
    } else if (status == 'accepted' || status == 'active') {
      return Column(
        children: [
          PrimaryButton(
            label: 'Mark as Consulted',
            onPressed: () => _setStatus('consulted'),
            color: AppColors.secondaryBlue,
          ),
          const SizedBox(height: 12),
          TextButton(
            onPressed: () => _setStatus('rejected'),
            child: const Text('Decline Lead', style: TextStyle(color: AppColors.statusError)),
          ),
        ],
      );
    } else if (status == 'consulted' || status == 'treated') {
      return PrimaryButton(
        label: 'Close Case',
        onPressed: () => _setStatus('closed'),
        color: AppColors.statusError,
      );
    } else if (status == 'closed' || status == 'completed') {
      return Container(
        padding: const EdgeInsets.all(16),
        width: double.infinity,
        decoration: BoxDecoration(
          color: AppColors.statusSuccess.withOpacity(0.1),
          borderRadius: AppStyles.radiusCard,
        ),
        child: Text(
          '✅ Case is Closed',
          style: AppStyles.bodyLarge.copyWith(color: AppColors.statusSuccess, fontWeight: FontWeight.bold),
          textAlign: TextAlign.center,
        ),
      );
    }
    return const SizedBox();
  }
}
