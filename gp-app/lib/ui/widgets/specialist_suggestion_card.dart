import 'package:flutter/material.dart';
import 'package:gp_app/core/utils/initials_helper.dart';
import 'package:gp_app/features/gp/models/recommended_specialist.dart';
import 'package:gp_app/ui/styles.dart';

class SpecialistSuggestionCard extends StatelessWidget {
  final RecommendedSpecialist specialist;
  final VoidCallback? onTap;

  const SpecialistSuggestionCard({
    super.key,
    required this.specialist,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final hospital = specialist.hospitalName?.trim();
    final hasHospital = hospital != null && hospital.isNotEmpty;
    final badgeLabel = specialist.isPremium ? 'Premium' : (specialist.isSuperSpecialist ? 'Super' : null);
    final badgeColor = specialist.isPremium ? AppColors.warningYellow : AppColors.secondaryTeal;

    return Material(
      color: AppColors.surface,
      borderRadius: AppStyles.radiusCard,
      child: InkWell(
        borderRadius: AppStyles.radiusCard,
        onTap: onTap,
        child: Container(
          width: 248,
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            borderRadius: AppStyles.radiusCard,
            border: Border.all(color: AppColors.border),
            boxShadow: AppStyles.cardShadow,
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  _avatar(),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          specialist.name,
                          style: AppStyles.cardTitle,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 2),
                        Text(
                          specialist.speciality,
                          style: AppStyles.caption.copyWith(color: AppColors.textSecondary),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              if (hasHospital)
                Row(
                  children: [
                    Icon(Icons.local_hospital_outlined, size: 16, color: AppColors.textMuted),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        hospital,
                        style: AppStyles.bodySmall,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                )
              else
                Row(
                  children: [
                    Icon(Icons.place_outlined, size: 16, color: AppColors.textMuted),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        specialist.areaName,
                        style: AppStyles.bodySmall,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
              if (badgeLabel != null) ...[
                const SizedBox(height: 10),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                  decoration: BoxDecoration(
                    color: badgeColor.withAlpha(26),
                    borderRadius: AppStyles.radiusPill,
                    border: Border.all(color: badgeColor.withAlpha(70)),
                  ),
                  child: Text(
                    badgeLabel,
                    style: AppStyles.chipText.copyWith(color: badgeColor),
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _avatar() {
    final photo = specialist.profilePhoto?.trim();
    if (photo != null && photo.isNotEmpty) {
      return Container(
        width: 42,
        height: 42,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          image: DecorationImage(
            image: NetworkImage(photo),
            fit: BoxFit.cover,
          ),
        ),
      );
    }
    final initials = getInitials(specialist.name);
    return Container(
      width: 42,
      height: 42,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [AppColors.primaryBlue.withAlpha(230), AppColors.secondaryTeal.withAlpha(200)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
      ),
      alignment: Alignment.center,
      child: Text(
        initials,
        style: AppStyles.bodyMedium.copyWith(color: AppColors.textOnDark, fontWeight: FontWeight.w800),
      ),
    );
  }
}
