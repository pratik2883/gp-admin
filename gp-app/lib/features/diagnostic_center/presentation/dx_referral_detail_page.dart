import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/features/diagnostic_center/state/dx_referral_detail_state.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_card.dart';
import 'package:gp_app/ui/widgets/app_hero_header.dart';
import 'package:gp_app/ui/widgets/app_status_chip.dart';
import 'package:gp_app/ui/widgets/primary_button.dart';
import 'package:gp_app/ui/widgets/section_header_row.dart';
import 'package:gp_app/ui/widgets/states.dart';

class DxReferralDetailPage extends ConsumerStatefulWidget {
  final String referralId;
  const DxReferralDetailPage({super.key, required this.referralId});
  @override
  ConsumerState<DxReferralDetailPage> createState() => _DxReferralDetailPageState();
}

class _DxReferralDetailPageState extends ConsumerState<DxReferralDetailPage> {
  bool _updating = false;

  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(dxReferralDetailControllerProvider.notifier).load(widget.referralId));
  }

  Future<void> _setStatus(String status) async {
    setState(() => _updating = true);
    await ref.read(dxReferralDetailControllerProvider.notifier).updateStatus(widget.referralId, status);
    if (mounted) setState(() => _updating = false);
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Status updated to $status')));
      ref.read(dxReferralListControllerProvider.notifier).refresh();
      context.pop();
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(dxReferralDetailControllerProvider);

    return Scaffold(
      backgroundColor: AppColors.background,
      body: Column(
        children: [
          AppHeroHeader(
            leading: IconButton(
              onPressed: () => context.pop(),
              icon: const Icon(Icons.arrow_back_rounded, color: AppColors.textOnDark),
            ),
            title: 'Diagnostic Referral',
            subtitle: 'ID #${widget.referralId}',
          ),
          Expanded(
            child: AnimatedSwitcher(
              duration: const Duration(milliseconds: 220),
              child: switch (state) {
                DxReferralDetailLoading() => const Center(child: CircularProgressIndicator(color: AppColors.primaryBlue)),
                DxReferralDetailError(:final message) => Center(
                    child: Padding(
                      padding: const EdgeInsets.all(24),
                      child: ErrorState(
                        message: message,
                        onRetry: () => ref.read(dxReferralDetailControllerProvider.notifier).load(widget.referralId),
                      ),
                    ),
                  ),
                DxReferralDetailLoaded(:final lead, :final services) => ListView(
                    padding: AppSpacing.responsiveScreenPadding(context).copyWith(top: 18, bottom: 40),
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              lead.patient_name ?? 'Unknown Patient',
                              style: AppStyles.heading2,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          const SizedBox(width: 10),
                          AppStatusChip(status: lead.status ?? 'pending'),
                        ],
                      ),
                      const SizedBox(height: 12),
                      AppCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            _infoRow('Patient Mobile', lead.patient_mobile ?? 'N/A'),
                            _infoRow('Referred By', lead.gp_name ?? 'Unknown'),
                            _infoRow('Center', lead.hospital_name ?? 'N/A'),
                          ],
                        ),
                      ),
                      const SizedBox(height: 16),
                      AppCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const SectionHeaderRow(title: 'Requested Tests'),
                            const SizedBox(height: 12),
                            if (services.isEmpty)
                              Text('No tests listed.', style: AppStyles.bodySmall.copyWith(color: AppColors.textSecondary))
                            else
                              ...services.map((s) => Padding(
                                    padding: const EdgeInsets.only(bottom: 8),
                                    child: Row(
                                      children: [
                                        const Icon(Icons.science_outlined, color: AppColors.secondaryTeal, size: 18),
                                        const SizedBox(width: 8),
                                        Expanded(child: Text('${s['name'] ?? 'Test'}', style: AppStyles.bodyMedium)),
                                      ],
                                    ),
                                  )),
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
                              lead.notes ?? 'No detailed notes provided.',
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
                            if (lead.attachments.isEmpty)
                              Text('No attachments available.', style: AppStyles.bodySmall.copyWith(color: AppColors.textSecondary))
                            else
                              ...lead.attachments.map((a) => Padding(
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
                        _buildActionButtons(lead.status ?? 'pending'),
                    ],
                  ),
              },
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
          Expanded(
            child: Text(
              value,
              style: AppStyles.bodyMedium.copyWith(fontWeight: FontWeight.w600),
              textAlign: TextAlign.end,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildActionButtons(String rawStatus) {
    final status = rawStatus.toLowerCase();

    if (status == 'pending' || status == 'new' || status == 'sent') {
      return Row(
        children: [
          Expanded(
            child: OutlinedButton(
              onPressed: () => _setStatus('rejected'),
              style: OutlinedButton.styleFrom(
                minimumSize: const Size(0, 52),
                foregroundColor: AppColors.statusError,
              ),
              child: const Text('Decline'),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: PrimaryButton(
              label: 'Accept Test',
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
            child: const Text('Decline', style: TextStyle(color: AppColors.statusError)),
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
          color: AppColors.statusSuccess.withAlpha(26),
          borderRadius: AppStyles.radiusCard,
        ),
        child: Text(
          'Case is Closed',
          style: AppStyles.bodyLarge.copyWith(color: AppColors.statusSuccess, fontWeight: FontWeight.bold),
          textAlign: TextAlign.center,
        ),
      );
    }
    return const SizedBox();
  }
}