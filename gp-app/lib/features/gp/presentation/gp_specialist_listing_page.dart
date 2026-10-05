import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/features/gp/models/recommended_specialist.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_hero_header.dart';
import 'package:gp_app/ui/widgets/app_card.dart';
import 'package:gp_app/ui/widgets/rounded_search_bar.dart';
import 'package:gp_app/ui/widgets/states.dart';
import 'package:gp_app/ui/widgets/specialist_profile_detail_modal.dart';

class GpSpecialistListingPage extends ConsumerStatefulWidget {
  final int? specialtyId;
  final int? locationId;
  final String? title;
  final int? categoryId;

  const GpSpecialistListingPage({
    super.key,
    this.specialtyId,
    this.locationId,
    this.title,
    this.categoryId,
  });

  @override
  ConsumerState<GpSpecialistListingPage> createState() => _GpSpecialistListingPageState();
}

class _GpSpecialistListingPageState extends ConsumerState<GpSpecialistListingPage> {
  String _searchQuery = '';
  
  @override
  Widget build(BuildContext context) {
    final hasFilter = widget.specialtyId != null;
    final specialistsAsync = hasFilter
        ? ref.watch(
            specialistsBySpecialtyProvider(
              SpecialtySpecialistsQuery(
                locationId: widget.locationId ?? 0,
                specialtyId: widget.specialtyId!,
              ),
            ),
          )
        : ref.watch(recommendedSpecialistsProvider);

    return Scaffold(
      backgroundColor: AppColors.background,
      body: Column(
        children: [
          AppHeroHeader(
            leading: IconButton(
              onPressed: () => context.pop(),
              icon: const Icon(Icons.arrow_back_rounded, color: AppColors.textOnDark),
            ),
            title: widget.title?.trim().isNotEmpty == true ? widget.title!.trim() : 'Browse Specialists',
            subtitle: hasFilter ? 'Specialists in selected category' : 'Recommended for your area',
            bottom: RoundedSearchBar(
              hintText: 'Search by name or specialty...',
              onChanged: (v) => setState(() => _searchQuery = v),
            ),
          ),
          Expanded(
            child: specialistsAsync.when(
              loading: () => const Center(child: CircularProgressIndicator(color: AppColors.secondaryTeal)),
              error: (err, _) => Center(
                child: AppCard(
                  margin: const EdgeInsets.all(20),
                  child: ErrorState(
                    title: 'Search failed',
                    message: '',
                    subtitle: 'We couldn\'t load the specialist list.',
                    onRetry: () {
                      if (hasFilter) {
                        ref.invalidate(
                          specialistsBySpecialtyProvider(
                            SpecialtySpecialistsQuery(locationId: widget.locationId ?? 0, specialtyId: widget.specialtyId!),
                          ),
                        );
                      } else {
                        ref.invalidate(recommendedSpecialistsProvider);
                      }
                    },
                  ),
                ),
              ),
              data: (specialists) {
                // Apply Premium-First sorting and Filtering
                final filtered = specialists.where((s) {
                  final query = _searchQuery.toLowerCase();
                  return s.name.toLowerCase().contains(query) || 
                         s.speciality.toLowerCase().contains(query) ||
                         (s.hospitalName?.toLowerCase().contains(query) ?? false);
                }).toList();

                // Business Rule: Premium specialists always appear first
                filtered.sort((a, b) {
                  if (a.isPremium && !b.isPremium) return -1;
                  if (!a.isPremium && b.isPremium) return 1;
                  return 0;
                });

                if (filtered.isEmpty) {
                  return const Center(
                    child: EmptyState(
                      message: 'No specialists found',
                      subtitle: 'Try adjusting your search or filters.',
                      icon: Icons.person_search_outlined,
                    ),
                  );
                }

                return ListView.builder(
                  padding: AppSpacing.responsiveScreenPadding(context).copyWith(top: 16, bottom: 40),
                  itemCount: filtered.length,
                  itemBuilder: (context, index) {
                    final specialist = filtered[index];
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: _ListSpecialistCard(
                        specialist: specialist,
                        onTap: () {
                          context.push('/gp/referrals/new', extra: {
                            'specialist_id': specialist.id,
                            'location_id': specialist.locationId,
                            'category_code': specialist.categoryCode,
                            'area_name': specialist.areaName,
                            'speciality': specialist.speciality,
                          });
                        },
                      ),
                    );
                  },
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _ListSpecialistCard extends StatelessWidget {
  final RecommendedSpecialist specialist;
  final VoidCallback onTap;

  const _ListSpecialistCard({required this.specialist, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final hospital = specialist.hospitalName?.trim();
    final hasHospital = hospital != null && hospital.isNotEmpty;
    final badgeLabel = specialist.isPremium ? 'Premium' : (specialist.isSuperSpecialist ? 'Super' : null);
    final badgeColor = specialist.isPremium ? AppColors.warningYellow : AppColors.secondaryTeal;

    return AppCard(
      onTap: onTap,
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _Avatar(name: specialist.name, isPremium: specialist.isPremium, photoUrl: specialist.profilePhoto),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      specialist.name,
                      style: AppStyles.heading2,
                    ),
                    const SizedBox(height: 2),
                    Text(
                      specialist.speciality,
                      style: AppStyles.bodyMedium.copyWith(
                        color: AppColors.primaryBlue,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),
              if (badgeLabel != null)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: badgeColor.withAlpha(20),
                    borderRadius: AppStyles.radiusPill,
                    border: Border.all(color: badgeColor.withAlpha(60)),
                  ),
                  child: Text(
                    badgeLabel.toUpperCase(),
                    style: AppStyles.chipText.copyWith(
                      color: badgeColor,
                      fontWeight: FontWeight.w800,
                      fontSize: 10,
                      letterSpacing: 0.5,
                    ),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 16),
          _InfoRow(
            icon: Icons.local_hospital_outlined,
            label: hasHospital ? hospital : 'Independent Practice',
          ),
          const SizedBox(height: 8),
          _InfoRow(icon: Icons.place_outlined, label: specialist.areaName),
          if (specialist.department != null || specialist.role != null) ...[
            const SizedBox(height: 8),
            _InfoRow(
              icon: Icons.assignment_ind_outlined,
              label: [specialist.department, specialist.role].whereType<String>().join(' · '),
            ),
          ],
          if (specialist.yearsOfExperience != null || (specialist.languages?.isNotEmpty ?? false)) ...[
            const SizedBox(height: 8),
            _InfoRow(
              icon: Icons.workspace_premium_outlined,
              label: [
                if (specialist.yearsOfExperience != null) '${specialist.yearsOfExperience} years experience',
                if (specialist.languages?.isNotEmpty ?? false) 'Languages: ${specialist.languages!.take(3).join(', ')}',
              ].join(' · '),
            ),
          ],
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: () {
                    SpecialistProfileDetailModal.show(
                      context,
                      specialist: {
                        'id': specialist.id,
                        'name': specialist.name,
                        'speciality': specialist.speciality,
                        'area_name': specialist.areaName,
                        'hospital_name': specialist.hospitalName,
                        'clinic_address': specialist.clinicAddress,
                        'years_of_experience': specialist.yearsOfExperience,
                        'is_premium': specialist.isPremium,
                        'is_super_specialist': specialist.isSuperSpecialist,
                        'clinic_timings': specialist.clinicTimings,
                        'hospital_visiting_hours': specialist.hospitalVisitingHours,
                        'profile_photo': specialist.profilePhoto,
                        'show_mobile_number': specialist.showMobileNumber,
                        'show_whatsapp_number': specialist.showWhatsappNumber,
                        'mobile': specialist.mobile,
                        'whatsapp_number': specialist.whatsappNumber,
                        'languages': specialist.languages,
                      },
                      onReferPressed: onTap,
                    );
                  },
                  icon: const Icon(Icons.person_pin_rounded, size: 16),
                  label: const Text('View Profile'),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColors.primaryBlue,
                    side: const BorderSide(color: AppColors.primaryBlue),
                    padding: const EdgeInsets.symmetric(vertical: 8),
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: onTap,
                  icon: const Icon(Icons.send_rounded, size: 16),
                  label: const Text('Refer Patient'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primaryBlue,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 8),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _Avatar extends StatelessWidget {
  final String name;
  final bool isPremium;
  final String? photoUrl;

  const _Avatar({required this.name, this.isPremium = false, this.photoUrl});

  @override
  Widget build(BuildContext context) {
    final photo = photoUrl?.trim();
    if (photo != null && photo.isNotEmpty) {
      return Container(
        width: 52,
        height: 52,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          border: isPremium ? Border.all(color: const Color(0xFFFFD700), width: 2) : null,
          image: DecorationImage(
            image: NetworkImage(photo),
            fit: BoxFit.cover,
          ),
          boxShadow: isPremium ? [
            BoxShadow(
              color: Colors.amber.withAlpha(77),
              blurRadius: 8,
              offset: const Offset(0, 4),
            )
          ] : null,
        ),
      );
    }

    final initials = name.isNotEmpty ? name.trim().split(' ').map((e) => e[0]).take(2).join().toUpperCase() : '?';
    
    return Container(
      width: 52,
      height: 52,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: isPremium 
            ? [const Color(0xFFFFD700), const Color(0xFFFFA500)]
            : [AppColors.primaryBlue, AppColors.secondaryTeal],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        boxShadow: isPremium ? [
          BoxShadow(
            color: Colors.amber.withAlpha(77),
            blurRadius: 8,
            offset: const Offset(0, 4),
          )
        ] : null,
      ),
      alignment: Alignment.center,
      child: Text(
        initials,
        style: AppStyles.bodyLarge.copyWith(color: Colors.white, fontWeight: FontWeight.w900),
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final IconData icon;
  final String label;

  const _InfoRow({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, size: 18, color: AppColors.textMuted),
        const SizedBox(width: 10),
        Expanded(
          child: Text(
            label,
            style: AppStyles.bodyMedium.copyWith(color: AppColors.textSecondary),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ),
      ],
    );
  }
}
