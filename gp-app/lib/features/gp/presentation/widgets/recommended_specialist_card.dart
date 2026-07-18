import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/features/gp/models/recommended_specialist.dart';
import 'package:gp_app/ui/styles.dart';

class RecommendedSpecialistCard extends StatelessWidget {
  final RecommendedSpecialist specialist;

  const RecommendedSpecialistCard({super.key, required this.specialist});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 280,
      margin: const EdgeInsets.only(right: 16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.cardWhite,
        borderRadius: AppStyles.radiusCard,
        boxShadow: AppStyles.cardShadow,
        border: Border.all(
          color: specialist.isPremium ? Colors.amber.withOpacity(0.3) : AppColors.borderLight,
          width: specialist.isPremium ? 1.5 : 1.0,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Text(
                  specialist.name,
                  style: AppStyles.bodyLarge.copyWith(fontWeight: FontWeight.bold, fontSize: 16),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              if (specialist.isPremium)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.amber.shade100,
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.star, color: Colors.amber, size: 12),
                      const SizedBox(width: 4),
                      Text(
                        'PREMIUM',
                        style: AppStyles.bodySmall.copyWith(
                          color: Colors.amber.shade900,
                          fontWeight: FontWeight.w900,
                          fontSize: 10,
                          letterSpacing: 0.5,
                        ),
                      ),
                    ],
                  ),
                ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            specialist.speciality,
            style: AppStyles.bodyMedium.copyWith(color: AppColors.primaryBlue, fontWeight: FontWeight.w700),
          ),
          if (specialist.hospitalName != null) ...[
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                  child: Text(
                    specialist.hospitalName!,
                    style: AppStyles.bodyMedium.copyWith(fontWeight: FontWeight.w600, color: AppColors.textDark),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                if (specialist.isSuperSpecialist) ...[
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                    decoration: BoxDecoration(
                      color: AppColors.primaryBlue.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: Text(
                      'Super Specialist',
                      style: AppStyles.bodySmall.copyWith(
                        color: AppColors.primaryBlue,
                        fontWeight: FontWeight.bold,
                        fontSize: 9,
                      ),
                    ),
                  ),
                ]
              ],
            ),
          ],
          if (specialist.department != null || specialist.role != null) ...[
            const SizedBox(height: 4),
            Text(
              [specialist.department, specialist.role].whereType<String>().join(' · '),
              style: AppStyles.bodySmall.copyWith(color: AppColors.textGrey, fontSize: 11),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
          if (specialist.yearsOfExperience != null || (specialist.languages?.isNotEmpty ?? false)) ...[
            const SizedBox(height: 4),
            Text(
              [
                if (specialist.yearsOfExperience != null) '${specialist.yearsOfExperience} yrs exp',
                if (specialist.languages?.isNotEmpty ?? false) specialist.languages!.take(2).join(', ')
              ].join(' · '),
              style: AppStyles.caption,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
          const SizedBox(height: 10),
          Row(
            children: [
              const Icon(Icons.location_on, size: 14, color: AppColors.textGrey),
              const SizedBox(width: 4),
              Text(
                specialist.areaName,
                style: AppStyles.bodyMedium.copyWith(color: AppColors.textGrey),
              ),
            ],
          ),
          const Spacer(),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: () => context.push('/gp/referrals/new', extra: {
                'specialist_id': specialist.id,
                'location_id': specialist.locationId,
                'category_code': specialist.categoryCode,
                'area_name': specialist.areaName,
                'speciality': specialist.speciality,
              }),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primaryBlue,
                foregroundColor: Colors.white,
                elevation: 0,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                padding: const EdgeInsets.symmetric(vertical: 10),
              ),
              child: const Text('Refer Now', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 13)),
            ),
          ),
        ],
      ),
    );
  }
}

class RecommendedSpecialistLoading extends StatelessWidget {
  const RecommendedSpecialistLoading({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 280,
      margin: const EdgeInsets.only(right: 16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.cardWhite.withOpacity(0.5),
        borderRadius: AppStyles.radiusCard,
        border: Border.all(color: AppColors.borderLight),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(width: 140, height: 18, color: Colors.grey.shade200),
          const SizedBox(height: 8),
          Container(width: 100, height: 14, color: Colors.grey.shade100),
          const SizedBox(height: 20),
          Container(width: 80, height: 14, color: Colors.grey.shade100),
          const Spacer(),
          Container(width: double.infinity, height: 36, color: Colors.grey.shade200),
        ],
      ),
    );
  }
}
