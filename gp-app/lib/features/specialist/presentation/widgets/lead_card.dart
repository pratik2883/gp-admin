import 'package:flutter/material.dart';
import 'package:gp_app/features/specialist/models/lead.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_status_chip.dart';
import 'package:gp_app/ui/widgets/app_pressable.dart';

class LeadCard extends StatelessWidget {
  final Lead lead;
  final VoidCallback? onTap;

  const LeadCard({
    super.key,
    required this.lead,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final name = lead.patient_name ?? 'Unknown Patient';
    final status = lead.status ?? 'pending';
    final date = lead.created_at;
    final dateLabel = date == null ? '' : '${date.day.toString().padLeft(2, '0')} ${_month(date.month)}';
    final gpName = lead.gp_name?.trim() ?? 'Unknown GP';
    final hospital = lead.hospital_name?.trim();

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
          padding: const EdgeInsets.all(14),
          child: Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: AppColors.primaryBlue.withAlpha(18),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Icon(Icons.person_pin_rounded, color: AppColors.primaryBlue.withAlpha(220)),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(name, style: AppStyles.bodyLarge, maxLines: 1, overflow: TextOverflow.ellipsis),
                    const SizedBox(height: 2),
                    Text('Ref: $gpName', style: AppStyles.bodySmall, maxLines: 1, overflow: TextOverflow.ellipsis),
                    if (hospital != null && hospital.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Text(hospital, style: AppStyles.caption, maxLines: 1, overflow: TextOverflow.ellipsis),
                    ],
                    if (dateLabel.isNotEmpty) ...[
                      const SizedBox(height: 6),
                      Text(dateLabel, style: AppStyles.caption),
                    ],
                  ],
                ),
              ),
              const SizedBox(width: 10),
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
