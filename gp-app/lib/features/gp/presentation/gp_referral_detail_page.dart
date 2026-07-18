import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/features/gp/models/referral.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/features/gp/presentation/widgets/referral_timeline.dart';
import 'package:gp_app/ui/widgets/app_card.dart';
import 'package:gp_app/ui/widgets/app_hero_header.dart';
import 'package:gp_app/ui/widgets/app_status_chip.dart';
import 'package:gp_app/ui/widgets/primary_button.dart';
import 'package:gp_app/ui/widgets/referral_patient_card.dart';
import 'package:gp_app/ui/widgets/section_header_row.dart';
import 'package:gp_app/ui/widgets/home_skeletons.dart';
import 'package:intl/intl.dart';

final gpReferralDetailProvider = FutureProvider.family<Referral, ({String id, String? type})>((ref, q) async {
  return ref.read(gpRepositoryProvider).fetchReferral(q.id, referralType: q.type);
});

class GpReferralDetailPage extends ConsumerWidget {
  final String referralId;
  final String? referralType;

  const GpReferralDetailPage({super.key, required this.referralId, this.referralType});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(gpReferralDetailProvider((id: referralId, type: referralType)));

    return Scaffold(
      backgroundColor: AppColors.background,
      body: Column(
        children: [
          AppHeroHeader(
            leading: IconButton(
              onPressed: () => Navigator.maybePop(context),
              icon: const Icon(Icons.arrow_back_rounded, color: AppColors.textOnDark),
            ),
            title: 'Referral Detail',
            subtitle: 'ID #$referralId',
          ),
          Expanded(
            child: AnimatedSwitcher(
              duration: const Duration(milliseconds: 220),
              switchInCurve: Curves.easeOut,
              switchOutCurve: Curves.easeIn,
              child: state.when(
                loading: () => const KeyedSubtree(
                  key: ValueKey('referral_detail_loading'),
                  child: ReferralDetailSkeleton(),
                ),
                error: (err, _) => KeyedSubtree(
                  key: const ValueKey('referral_detail_error'),
                  child: _errorView(
                    errorDetail: err.toString(),
                    onRetry: () => ref.refresh(gpReferralDetailProvider((id: referralId, type: referralType))),
                  ),
                ),
                data: (referral) => KeyedSubtree(
                  key: const ValueKey('referral_detail_loaded'),
                  child: _detailView(referral),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _errorView({
    required String errorDetail,
    required VoidCallback onRetry,
  }) {
    return ListView(
      padding: AppSpacing.screenPadding.copyWith(top: 18, bottom: 28),
      children: [
        AppCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Referral details aren’t available right now.', style: AppStyles.heading2),
              const SizedBox(height: 8),
              Text(
                'Please try again in a moment.',
                style: AppStyles.bodySmall.copyWith(color: AppColors.textSecondary),
              ),
              const SizedBox(height: 8),
              Text(errorDetail, style: AppStyles.caption.copyWith(color: AppColors.textMuted)),
              const SizedBox(height: 16),
              PrimaryButton(label: 'Retry', onPressed: onRetry),
            ],
          ),
        ),
      ],
    );
  }

  Widget _detailView(Referral referral) {
    final dateStr = referral.created_at != null
        ? DateFormat('MMM dd, yyyy h:mm a').format(referral.created_at!)
        : 'Unknown';
    final status = referral.status ?? 'pending';

    return ListView(
      padding: AppSpacing.screenPadding.copyWith(top: 18, bottom: 28),
      children: [
        Row(
          children: [
            Expanded(
              child: Text(
                referral.patient_name?.trim().isNotEmpty == true ? referral.patient_name!.trim() : 'Patient',
                style: AppStyles.heading2,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ),
            const SizedBox(width: 10),
            AppStatusChip(status: status),
          ],
        ),
        const SizedBox(height: 12),
        ReferralPatientCard(
          referral: referral,
          showNotesPreview: false,
        ),
        const SizedBox(height: 12),
        AppCard(
          child: Row(
            children: [
              _metaItem(
                icon: Icons.schedule_rounded,
                label: 'Submitted',
                value: dateStr,
              ),
              const SizedBox(width: 12),
              _metaItem(
                icon: Icons.local_hospital_outlined,
                label: 'Visit Type',
                value: referral.visit_type?.trim().isNotEmpty == true ? referral.visit_type!.trim() : 'OPD',
              ),
            ],
          ),
        ),
        const SizedBox(height: 16),
        _detailCard(
          title: 'Patient Details',
          children: [
            _infoRow('Mobile Number', referral.patient_mobile ?? 'N/A'),
            _infoRow('Age', referral.patient_age != null ? '${referral.patient_age} yrs' : 'N/A'),
            _infoRow('Gender', referral.patient_gender ?? 'N/A'),
          ],
        ),
        const SizedBox(height: 12),
        _detailCard(
          title: 'Destination Details',
          children: [
            if ((referral.referral_type ?? 'specialist').toLowerCase() == 'diagnostic') ...[
              _infoRow('Diagnostic Center', referral.diagnostic_center_name ?? 'N/A'),
              _infoRow('Priority', referral.priority ?? 'Routine'),
              _infoRow('Visit Type', referral.visit_type ?? 'OPD'),
            ] else if ((referral.referral_type ?? 'specialist').toLowerCase() == 'hospital') ...[
              _infoRow('Hospital', referral.hospital_name ?? 'N/A'),
              if (referral.hospital_address != null && referral.hospital_address!.isNotEmpty)
                _infoRow('Address', referral.hospital_address!),
              _infoRow('Department', referral.department ?? 'N/A'),
              _infoRow('Priority', referral.priority ?? 'Routine'),
              _infoRow('Visit Type', referral.visit_type ?? 'OPD'),
            ] else ...[
              _infoRow('Specialist', referral.specialist_name ?? 'N/A'),
              if (referral.specialist_speciality != null && referral.specialist_speciality!.isNotEmpty)
                _infoRow('Speciality', referral.specialist_speciality!),
              if (referral.hospital_name != null && referral.hospital_name!.isNotEmpty)
                _infoRow('Clinic/Hospital', referral.hospital_name!),
              if (referral.specialist_address != null && referral.specialist_address!.isNotEmpty)
                _infoRow('Address', referral.specialist_address!),
              if (referral.specialist_experience != null)
                _infoRow('Experience', '${referral.specialist_experience} years'),
              _infoRow('Visit Type', referral.visit_type ?? 'OPD'),
            ],
          ],
        ),
        const SizedBox(height: 12),
        _detailCard(
          title: 'Case Summary',
          children: [
            Text(
              (referral.notes != null && referral.notes!.trim().isNotEmpty)
                  ? referral.notes!.trim()
                  : 'No detailed clinical notes provided.',
              style: AppStyles.bodyMedium,
            ),
          ],
        ),
        const SizedBox(height: 12),
        _detailCard(
          title: 'Referral Timeline',
          children: [
            ReferralTimeline(currentStatus: status),
          ],
        ),
      ],
    );
  }

  Widget _detailCard({
    required String title,
    required List<Widget> children,
  }) {
    return AppCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SectionHeaderRow(title: title),
          const SizedBox(height: 12),
          ...children,
        ],
      ),
    );
  }

  Widget _metaItem({
    required IconData icon,
    required String label,
    required String value,
  }) {
    return Expanded(
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: AppColors.secondaryTeal.withAlpha(18),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Icon(icon, color: AppColors.secondaryTeal.withAlpha(230), size: 20),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label, style: AppStyles.caption),
                const SizedBox(height: 4),
                Text(value, style: AppStyles.bodySmall, maxLines: 2, overflow: TextOverflow.ellipsis),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _infoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Text(label, style: AppStyles.bodySmall.copyWith(color: AppColors.textSecondary)),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(value, style: AppStyles.bodyMedium.copyWith(fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );
  }
}
