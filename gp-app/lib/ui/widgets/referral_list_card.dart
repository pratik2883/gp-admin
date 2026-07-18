import 'package:flutter/material.dart';
import 'package:gp_app/features/gp/models/referral.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/referral_status_chip.dart';

class ReferralListCard extends StatelessWidget {
  final Referral referral;
  final VoidCallback onTapNotes;
  final VoidCallback onTapMenu;
  final bool isSpecialist;

  const ReferralListCard({
    super.key,
    required this.referral,
    required this.onTapNotes,
    required this.onTapMenu,
    this.isSpecialist = false,
  });

  @override
  Widget build(BuildContext context) {
    final status = referral.status ?? 'Pending';
    final date = referral.created_at != null
        ? "${_getMonth(referral.created_at!.month)} ${referral.created_at!.day}, ${referral.created_at!.year}"
        : 'Unknown Date';
    final screenWidth = MediaQuery.sizeOf(context).width;
    final isCompact = screenWidth < 360;

    final buttonText = _getButtonText(status);
    final buttonIcon = _getButtonIcon(status);
    final buttonColor = _getButtonColor(status);

    return Container(
      decoration: BoxDecoration(
        color: AppColors.cardWhite,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.03),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: EdgeInsets.only(left: isCompact ? 14 : 20, top: isCompact ? 14 : 20, right: isCompact ? 14 : 20, bottom: isCompact ? 12 : 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    Expanded(
                      child: Text(
                        referral.patient_name ?? 'Unknown Patient',
                        style: AppStyles.heading2.copyWith(fontSize: isCompact ? 16 : 20),
                        overflow: TextOverflow.ellipsis,
                        maxLines: 1,
                      ),
                    ),
                    const SizedBox(width: 8),
                    ReferralStatusChip(status: status),
                  ],
                ),
                const SizedBox(height: 6),
                Text(
                  'Referred: $date',
                  style: AppStyles.bodyMedium.copyWith(color: AppColors.textGrey, fontSize: isCompact ? 12 : 15),
                ),
                SizedBox(height: isCompact ? 10 : 16),
                Container(
                  padding: EdgeInsets.symmetric(horizontal: isCompact ? 10 : 16, vertical: isCompact ? 8 : 12),
                  decoration: BoxDecoration(
                    color: AppColors.background,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.medical_services_rounded, color: AppColors.primaryBlue, size: isCompact ? 16 : 20),
                      SizedBox(width: isCompact ? 8 : 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              isSpecialist ? (referral.gp_id != null ? 'Referred by GP' : 'General Practitioner') : 'Specialist / Clinic',
                              style: AppStyles.bodyMedium.copyWith(fontWeight: FontWeight.w600),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 2),
                            Text(
                              isSpecialist ? 'Primary Care • Details' : 'Medical Center • Department',
                              style: AppStyles.caption.copyWith(color: AppColors.textGrey),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Divider(height: 1, thickness: 1, color: AppColors.borderLight),
          Padding(
            padding: EdgeInsets.symmetric(horizontal: isCompact ? 4 : 8, vertical: 4),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                TextButton.icon(
                  onPressed: onTapNotes,
                  icon: Icon(buttonIcon, size: isCompact ? 14 : 16, color: buttonColor),
                  label: Text(
                    buttonText,
                    style: AppStyles.bodyMedium.copyWith(
                      color: buttonColor,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  style: TextButton.styleFrom(
                    padding: EdgeInsets.symmetric(horizontal: isCompact ? 8 : 12, vertical: isCompact ? 8 : 12),
                    alignment: Alignment.centerLeft,
                  ),
                ),
                IconButton(
                  onPressed: onTapMenu,
                  icon: Icon(Icons.more_horiz, color: AppColors.borderLight, size: isCompact ? 18 : 20),
                  tooltip: 'More actions',
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  String _getMonth(int month) {
    const m = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return m[month - 1];
  }

  String _getButtonText(String status) {
    final s = status.toLowerCase();
    if (s.contains('accepted')) return 'View Patient Notes';
    if (s.contains('pending')) return 'Expedite Request';
    if (s.contains('declined') || s.contains('rejected')) return 'Review Reason';
    return 'View Details';
  }

  IconData _getButtonIcon(String status) {
    final s = status.toLowerCase();
    if (s.contains('accepted')) return Icons.arrow_forward;
    if (s.contains('pending')) return Icons.flash_on;
    if (s.contains('declined') || s.contains('rejected')) return Icons.warning_rounded;
    return Icons.arrow_forward;
  }

  Color _getButtonColor(String status) {
    final s = status.toLowerCase();
    if (s.contains('accepted') || s.contains('pending')) return AppColors.primaryBlue;
    if (s.contains('declined') || s.contains('rejected')) return AppColors.statusError;
    return AppColors.primaryBlue;
  }
}
