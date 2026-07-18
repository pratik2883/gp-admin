import 'package:flutter/material.dart';
import 'package:gp_app/features/gp/models/referral.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_status_chip.dart';
import 'package:gp_app/ui/widgets/app_pressable.dart';

class ReferralPatientCard extends StatelessWidget {
  final Referral referral;
  final VoidCallback? onTap;
  final bool showNotesPreview;
  final int notesMaxLines;
  final bool showContextLine;
  final String? contextOverride;

  const ReferralPatientCard({
    super.key,
    required this.referral,
    this.onTap,
    this.showNotesPreview = false,
    this.notesMaxLines = 2,
    this.showContextLine = true,
    this.contextOverride,
  });

  @override
  Widget build(BuildContext context) {
    final name = referral.patient_name ?? 'Patient';
    final status = referral.status ?? 'pending';
    final date = referral.created_at;
    final dateLabel = date == null ? '' : '${date.day.toString().padLeft(2, '0')} ${_month(date.month)}';
    final type = (referral.referral_type ?? 'specialist').toLowerCase();
    final specialist = referral.specialist_name?.trim();
    final hospital = referral.hospital_name?.trim();
    final diagnosticCenter = referral.diagnostic_center_name?.trim();
    final dept = referral.department?.trim();
    final screenWidth = MediaQuery.sizeOf(context).width;
    final isCompact = screenWidth < 360;

    final icon = switch (type) {
      'diagnostic' => Icons.biotech_outlined,
      'hospital' => Icons.local_hospital_outlined,
      _ => Icons.person_outline_rounded,
    };

    final subtitle = switch (type) {
      'diagnostic' => (diagnosticCenter != null && diagnosticCenter.isNotEmpty) ? diagnosticCenter : 'Diagnostic',
      'hospital' => (hospital != null && hospital.isNotEmpty) ? hospital : 'Hospital',
      _ => (specialist != null && specialist.isNotEmpty) ? specialist : 'Specialist',
    };

    final contextLine = contextOverride ??
        switch (type) {
          'hospital' => (dept != null && dept.isNotEmpty) ? dept : null,
          'diagnostic' => null,
          _ => ((hospital != null && hospital.isNotEmpty && (specialist != null && specialist.isNotEmpty)) ? hospital : null),
        };
    final notes = referral.notes?.trim();
    final showNotes = showNotesPreview && notes != null && notes.isNotEmpty;

    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: AppStyles.radiusCard,
        border: Border.all(color: AppColors.border),
        boxShadow: AppStyles.cardShadow,
      ),
      child: AppPressable(
        onTap: onTap,
        enabled: onTap != null,
        borderRadius: AppStyles.radiusCard,
        pressedScale: 0.99,
        pressedOpacity: 0.96,
        child: Padding(
          padding: EdgeInsets.all(isCompact ? 10 : 14),
          child: Row(
            children: [
              Container(
                width: isCompact ? 36 : 44,
                height: isCompact ? 36 : 44,
                decoration: BoxDecoration(
                  color: AppColors.primaryBlue.withAlpha(18),
                  borderRadius: BorderRadius.circular(isCompact ? 11 : 16),
                ),
                child: Icon(icon, color: AppColors.primaryBlue.withAlpha(220), size: isCompact ? 16 : null),
              ),
              SizedBox(width: isCompact ? 8 : 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(name, style: AppStyles.bodyLarge.copyWith(fontSize: isCompact ? 13 : null), maxLines: 1, overflow: TextOverflow.ellipsis),
                    const SizedBox(height: 2),
                    Text(subtitle, style: AppStyles.bodySmall, maxLines: 1, overflow: TextOverflow.ellipsis),
                    if (showContextLine && contextLine != null && contextLine.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Text(contextLine, style: AppStyles.caption, maxLines: 1, overflow: TextOverflow.ellipsis),
                    ],
                    if (showNotes) ...[
                      const SizedBox(height: 6),
                      Text(
                        notes,
                        style: AppStyles.bodySmall.copyWith(color: AppColors.textSecondary),
                        maxLines: notesMaxLines,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                    if (dateLabel.isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Text(dateLabel, style: AppStyles.caption),
                    ],
                  ],
                ),
              ),
              SizedBox(width: isCompact ? 6 : 10),
              AppStatusChip(status: status),
            ],
          ),
        ),
      ),
    );
  }

  String _month(int month) {
    const m = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return m[month - 1];
  }
}
