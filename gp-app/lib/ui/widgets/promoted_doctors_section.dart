import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/app/role.dart';
import 'package:gp_app/features/gp/models/recommended_specialist.dart';
import 'package:gp_app/ui/styles.dart';

class PromotedDoctorsSection extends ConsumerWidget {
  final VoidCallback? onSeeAllTap;

  const PromotedDoctorsSection({
    super.key,
    this.onSeeAllTap,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final asyncSpecialists = ref.watch(recommendedSpecialistsProvider);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Section Header Row: "Promoted Doctors ⭐" ... "See All >"
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Row(
                children: [
                  Text(
                    'Recommended Doctors',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w800,
                      color: AppColors.textPrimary,
                    ),
                  ),
                  SizedBox(width: 6),
                  Text('⭐', style: TextStyle(fontSize: 16)),
                ],
              ),
              InkWell(
                onTap: onSeeAllTap ?? () => context.push('/gp/specialists'),
                borderRadius: BorderRadius.circular(4),
                child: const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 4, vertical: 2),
                  child: Row(
                    children: [
                      Text(
                        'See All',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.bold,
                          color: AppColors.primaryBlue,
                        ),
                      ),
                      SizedBox(width: 2),
                      Icon(
                        Icons.chevron_right_rounded,
                        size: 18,
                        color: AppColors.primaryBlue,
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),

        const SizedBox(height: 8),

        // Horizontal List of Promoted Doctor Cards
        SizedBox(
          height: 340,
          child: asyncSpecialists.when(
            data: (specialists) {
              final promoted = specialists.where((s) => s.isPremium || true).toList();
              if (promoted.isEmpty) {
                return const SizedBox.shrink();
              }
              return ListView.builder(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 16),
                itemCount: promoted.length,
                itemBuilder: (ctx, index) {
                  final sp = promoted[index];
                  return PromotedDoctorCard(specialist: sp);
                },
              );
            },
            loading: () => ListView.builder(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              itemCount: 3,
              itemBuilder: (ctx, index) => const PromotedDoctorCardSkeleton(),
            ),
            error: (_, __) => const SizedBox.shrink(),
          ),
        ),
      ],
    );
  }
}

class PromotedDoctorCard extends ConsumerStatefulWidget {
  final RecommendedSpecialist specialist;
  const PromotedDoctorCard({super.key, required this.specialist});

  @override
  ConsumerState<PromotedDoctorCard> createState() => _PromotedDoctorCardState();
}

class _PromotedDoctorCardState extends ConsumerState<PromotedDoctorCard> {
  bool _isBookmarked = false;

  @override
  Widget build(BuildContext context) {
    final sp = widget.specialist;

    return Container(
      width: 260,
      margin: const EdgeInsets.only(right: 14, bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: sp.isPremium ? AppColors.secondaryTeal.withAlpha(80) : Colors.grey.shade200,
          width: sp.isPremium ? 1.5 : 1.0,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withAlpha(12),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          // Top Row: Premium Badge & Bookmark Button
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              // Premium Badge Pill
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: AppColors.secondaryTeal,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.star, color: Colors.amber, size: 12),
                    SizedBox(width: 4),
                    Text(
                      'Premium',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 10,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
              ),

              // Bookmark Icon Button
              InkWell(
                onTap: () {
                  setState(() {
                    _isBookmarked = !_isBookmarked;
                  });
                },
                borderRadius: BorderRadius.circular(6),
                child: Container(
                  padding: const EdgeInsets.all(6),
                  decoration: BoxDecoration(
                    color: Colors.grey.shade100,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(
                    _isBookmarked ? Icons.bookmark_rounded : Icons.bookmark_outline_rounded,
                    size: 16,
                    color: _isBookmarked ? AppColors.primaryBlue : Colors.grey.shade600,
                  ),
                ),
              ),
            ],
          ),

          const SizedBox(height: 8),

          // Doctor Avatar Circle
          CircleAvatar(
            radius: 32,
            backgroundColor: AppColors.primaryBlue.withAlpha(25),
            backgroundImage: (sp.profilePhoto != null && sp.profilePhoto!.isNotEmpty)
                ? NetworkImage(sp.profilePhoto!)
                : null,
            child: (sp.profilePhoto == null || sp.profilePhoto!.isEmpty)
                ? Text(
                    sp.name.isNotEmpty ? sp.name[0].toUpperCase() : 'D',
                    style: const TextStyle(
                      color: AppColors.primaryBlue,
                      fontWeight: FontWeight.w800,
                      fontSize: 24,
                    ),
                  )
                : null,
          ),

          const SizedBox(height: 8),

          // Doctor Name
          Text(
            sp.name,
            style: const TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 14,
              color: AppColors.textPrimary,
            ),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            textAlign: TextAlign.center,
          ),

          // Specialty
          Text(
            sp.speciality,
            style: TextStyle(
              fontSize: 12,
              color: Colors.grey.shade700,
              fontWeight: FontWeight.w500,
            ),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            textAlign: TextAlign.center,
          ),

          const SizedBox(height: 6),

          // Location Line
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.location_on_outlined, size: 13, color: Colors.grey.shade600),
              const SizedBox(width: 2),
              Flexible(
                child: Text(
                  sp.areaName.isNotEmpty ? sp.areaName : 'Mumbai',
                  style: TextStyle(fontSize: 11, color: Colors.grey.shade600),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),

          // Hospital / Clinic Line
          if (sp.hospitalName != null && sp.hospitalName!.isNotEmpty) ...[
            const SizedBox(height: 2),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.business_outlined, size: 13, color: Colors.grey.shade600),
                const SizedBox(width: 2),
                Flexible(
                  child: Text(
                    sp.hospitalName!,
                    style: TextStyle(fontSize: 11, color: Colors.grey.shade600),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
          ],

          // Visit Timings Line
          if ((sp.clinicTimings?.isNotEmpty ?? false) || (sp.hospitalVisitingHours?.isNotEmpty ?? false)) ...[
            const SizedBox(height: 2),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.access_time_rounded, size: 13, color: AppColors.primaryBlue),
                const SizedBox(width: 2),
                Flexible(
                  child: Text(
                    sp.clinicTimings ?? sp.hospitalVisitingHours!,
                    style: const TextStyle(fontSize: 11, color: AppColors.primaryBlue, fontWeight: FontWeight.w600),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
          ],

          const SizedBox(height: 4),

          // Rating Row: ⭐ 4.8 (28 Reviews)
          const Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.star_rounded, size: 14, color: Colors.amber),
              SizedBox(width: 2),
              Text(
                '4.8',
                style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
              ),
              SizedBox(width: 4),
              Text(
                '(28 Reviews)',
                style: TextStyle(fontSize: 11, color: AppColors.textSecondary),
              ),
            ],
          ),

          const SizedBox(height: 6),

          // Available for Referrals Badge
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
            decoration: BoxDecoration(
              color: Colors.green.shade50,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: Colors.green.shade200),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 6,
                  height: 6,
                  decoration: const BoxDecoration(
                    color: Colors.green,
                    shape: BoxShape.circle,
                  ),
                ),
                const SizedBox(width: 4),
                Text(
                  'Available for Referrals',
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.bold,
                    color: Colors.green.shade800,
                  ),
                ),
              ],
            ),
          ),

          const Spacer(),

          // Bottom Action Buttons Row: [ Refer Patient ] [ View Profile ]
          Row(
            children: [
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: () {
                    final role = ref.read(selectedRoleProvider);
                    if (role == AppRole.gp) {
                      context.push('/gp/referrals/new', extra: {'specialist_id': sp.id});
                    } else {
                      context.push('/gp/specialists/${sp.id}');
                    }
                  },
                  icon: const Icon(Icons.person_add_alt_1_rounded, size: 12),
                  label: const Text(
                    'Refer Patient',
                    style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold),
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.secondaryTeal,
                    foregroundColor: Colors.white,
                    elevation: 0,
                    padding: const EdgeInsets.symmetric(vertical: 8),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                ),
              ),
              const SizedBox(width: 6),
              OutlinedButton(
                onPressed: () {
                  context.push('/gp/specialists/${sp.id}');
                },
                style: OutlinedButton.styleFrom(
                  foregroundColor: AppColors.primaryBlue,
                  side: BorderSide(color: AppColors.primaryBlue.withAlpha(150)),
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                ),
                child: const Text(
                  'View Profile',
                  style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class PromotedDoctorCardSkeleton extends StatelessWidget {
  const PromotedDoctorCardSkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 260,
      margin: const EdgeInsets.only(right: 14, bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Container(width: 60, height: 18, color: Colors.grey.shade200),
              Container(width: 24, height: 24, color: Colors.grey.shade200),
            ],
          ),
          const SizedBox(height: 12),
          CircleAvatar(radius: 32, backgroundColor: Colors.grey.shade200),
          const SizedBox(height: 12),
          Container(width: 120, height: 14, color: Colors.grey.shade200),
          const SizedBox(height: 6),
          Container(width: 90, height: 12, color: Colors.grey.shade200),
        ],
      ),
    );
  }
}
