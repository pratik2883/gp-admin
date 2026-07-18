import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_hero_header.dart';
import 'package:gp_app/ui/widgets/app_card.dart';
import 'package:gp_app/ui/widgets/rounded_search_bar.dart';
import 'package:gp_app/ui/widgets/states.dart';

class GpDiagnosticCenterListPage extends ConsumerStatefulWidget {
  const GpDiagnosticCenterListPage({super.key});

  @override
  ConsumerState<GpDiagnosticCenterListPage> createState() => _GpDiagnosticCenterListPageState();
}

class _GpDiagnosticCenterListPageState extends ConsumerState<GpDiagnosticCenterListPage> {
  String _searchQuery = '';

  @override
  Widget build(BuildContext context) {
    final centersAsync = ref.watch(allDiagnosticCentersProvider);
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
            title: 'Diagnostic Centers',
            subtitle: 'Select a center for test referral',
          ),
          Expanded(
            child: centersAsync.when(
              loading: () => const Center(child: CircularProgressIndicator(color: AppColors.warningYellow)),
              error: (err, _) => Center(
                child: AppCard(
                  margin: EdgeInsets.all(hp),
                  child: ErrorState(
                    title: 'Load failed',
                    message: err.toString(),
                    onRetry: () => ref.refresh(allDiagnosticCentersProvider),
                  ),
                ),
              ),
              data: (centers) {
                final filtered = centers.where((c) {
                  final query = _searchQuery.toLowerCase();
                  final name = (c['name'] ?? c['center_name'] ?? '').toString().toLowerCase();
                  final city = (c['city'] ?? '').toString().toLowerCase();
                  return name.contains(query) || city.contains(query);
                }).toList();

                if (filtered.isEmpty) {
                  return const Center(
                    child: EmptyState(
                      message: 'No diagnostic centers found',
                      subtitle: 'Try adjusting your search.',
                      icon: Icons.biotech_outlined,
                    ),
                  );
                }

                return Column(
                  children: [
                    Padding(
                      padding: EdgeInsets.fromLTRB(hp, 8, hp, 8),
                      child: RoundedSearchBar(
                        hintText: 'Search centers...',
                        onChanged: (v) => setState(() => _searchQuery = v),
                      ),
                    ),
                    Expanded(
                      child: ListView.builder(
                        padding: EdgeInsets.fromLTRB(hp, 0, hp, 40),
                        itemCount: filtered.length,
                        itemBuilder: (context, index) {
                          final center = filtered[index];
                          return Padding(
                            padding: const EdgeInsets.only(bottom: 12),
                            child: _DiagnosticCenterCard(
                              center: center,
                              onTap: () {
                                context.push('/gp/referrals/new', extra: {
                                  'diagnostic_center_id': center['id'],
                                  'diagnostic_center_name': center['name'] ?? center['center_name'],
                                  'referral_type': 'diagnostic',
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

class _DiagnosticCenterCard extends StatelessWidget {
  final Map<String, dynamic> center;
  final VoidCallback onTap;

  const _DiagnosticCenterCard({required this.center, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final name = center['name'] ?? center['center_name'] ?? 'Diagnostic Center';
    final address = center['address'] ?? center['street'] ?? '';
    final city = center['city'] ?? '';
    final phone = center['phone'] ?? '';

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
                  color: AppColors.warningYellow.withAlpha(20),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(Icons.biotech, color: AppColors.warningYellow, size: 24),
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
                  'Available for test referrals',
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