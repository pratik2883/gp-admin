import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_hero_header.dart';
import 'package:gp_app/ui/widgets/app_card.dart';
import 'package:gp_app/ui/widgets/rounded_search_bar.dart';
import 'package:gp_app/ui/widgets/states.dart';

class GpHospitalListPage extends ConsumerStatefulWidget {
  const GpHospitalListPage({super.key});

  @override
  ConsumerState<GpHospitalListPage> createState() => _GpHospitalListPageState();
}

class _GpHospitalListPageState extends ConsumerState<GpHospitalListPage> {
  String _searchQuery = '';

  @override
  Widget build(BuildContext context) {
    final hospitalsAsync = ref.watch(allHospitalsProvider);
    final hp = AppSpacing.responsiveHorizontal(context);

    return Scaffold(
      backgroundColor: AppColors.background,
      body: Column(
        children: [
          AppHeroHeader(
            leading: IconButton(
              onPressed: () => context.pop(),
              icon: const Icon(Icons.arrow_back_rounded, color: AppColors.textOnDark),
            ),
            title: 'Hospitals',
            subtitle: 'Select a hospital for referral',
          ),
          Expanded(
            child: hospitalsAsync.when(
              loading: () => const Center(child: CircularProgressIndicator(color: AppColors.primaryBlue)),
              error: (err, _) => Center(
                child: AppCard(
                  margin: EdgeInsets.all(hp),
                  child: ErrorState(
                    title: 'Load failed',
                    message: err.toString(),
                    onRetry: () => ref.refresh(allHospitalsProvider),
                  ),
                ),
              ),
              data: (hospitals) {
                final filtered = hospitals.where((h) {
                  final query = _searchQuery.toLowerCase();
                  final name = (h['name'] ?? h['hospital_name'] ?? '').toString().toLowerCase();
                  final city = (h['city'] ?? '').toString().toLowerCase();
                  return name.contains(query) || city.contains(query);
                }).toList();

                if (filtered.isEmpty) {
                  return const Center(
                    child: EmptyState(
                      message: 'No hospitals found',
                      subtitle: 'Try adjusting your search.',
                      icon: Icons.local_hospital_outlined,
                    ),
                  );
                }

                return Column(
                  children: [
                    Padding(
                      padding: EdgeInsets.fromLTRB(hp, 8, hp, 8),
                      child: RoundedSearchBar(
                        hintText: 'Search hospitals...',
                        onChanged: (v) => setState(() => _searchQuery = v),
                      ),
                    ),
                    Expanded(
                      child: ListView.builder(
                        padding: EdgeInsets.fromLTRB(hp, 0, hp, 40),
                        itemCount: filtered.length,
                        itemBuilder: (context, index) {
                          final hospital = filtered[index];
                          return Padding(
                            padding: const EdgeInsets.only(bottom: 12),
                            child: _HospitalCard(
                              hospital: hospital,
                              onTap: () {
                                context.push('/gp/referrals/new', extra: {
                                  'hospital_id': hospital['id'],
                                  'hospital_name': hospital['name'] ?? hospital['hospital_name'],
                                  'referral_type': 'hospital',
                                });
                              },
                            ),
                          );
                        },
                      ),
                    ),
                  ],
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _HospitalCard extends StatelessWidget {
  final Map<String, dynamic> hospital;
  final VoidCallback onTap;

  const _HospitalCard({required this.hospital, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final name = hospital['name'] ?? hospital['hospital_name'] ?? 'Hospital';
    final address = hospital['address'] ?? hospital['street'] ?? '';
    final city = hospital['city'] ?? '';
    final phone = hospital['phone'] ?? '';

    return AppCard(
      onTap: onTap,
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: AppColors.secondaryTeal.withAlpha(20),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(Icons.local_hospital, color: AppColors.secondaryTeal, size: 24),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(name, style: AppStyles.heading2),
              ),
            ],
          ),
          if (address.isNotEmpty || city.isNotEmpty) ...[
            const SizedBox(height: 10),
            Row(
              children: [
                const Icon(Icons.place_outlined, size: 16, color: AppColors.textMuted),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    [address, city].where((s) => s.isNotEmpty).join(', '),
                    style: AppStyles.bodyMedium.copyWith(color: AppColors.textSecondary),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
          ],
          if (phone.isNotEmpty) ...[
            const SizedBox(height: 6),
            Row(
              children: [
                const Icon(Icons.phone_outlined, size: 16, color: AppColors.textMuted),
                const SizedBox(width: 6),
                Text(phone, style: AppStyles.bodyMedium.copyWith(color: AppColors.textSecondary)),
              ],
            ),
          ],
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: Text(
                  'Available for referrals',
                  style: AppStyles.caption.copyWith(color: AppColors.successGreen, fontWeight: FontWeight.w600),
                ),
              ),
              const Icon(Icons.arrow_forward_rounded, size: 18, color: AppColors.primaryBlue),
            ],
          ),
        ],
      ),
    );
  }
}