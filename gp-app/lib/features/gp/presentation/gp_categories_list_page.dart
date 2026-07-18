import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_hero_header.dart';
import 'package:gp_app/ui/widgets/app_card.dart';
import 'package:gp_app/ui/widgets/states.dart';

class GpCategoriesListPage extends ConsumerWidget {
  const GpCategoriesListPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final categoriesState = ref.watch(gpSpecialtyCategoriesProvider);
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
            title: 'Categories',
            subtitle: 'Browse all specialties',
          ),
          Expanded(
            child: categoriesState.when(
              loading: () => const Center(child: CircularProgressIndicator(color: AppColors.primaryBlue)),
              error: (err, _) => Center(
                child: AppCard(
                  margin: EdgeInsets.all(hp),
                  child: ErrorState(
                    title: 'Load failed',
                    message: err.toString(),
                    onRetry: () => ref.refresh(gpSpecialtyCategoriesProvider),
                  ),
                ),
              ),
              data: (resp) {
                final items = resp.categories;
                if (items.isEmpty) {
                  return const Center(
                    child: EmptyState(
                      message: 'No categories available',
                      subtitle: 'Please try again later.',
                      icon: Icons.category_outlined,
                    ),
                  );
                }

                return ListView.builder(
                  padding: EdgeInsets.fromLTRB(hp, 16, hp, 40),
                  itemCount: items.length,
                  itemBuilder: (context, index) {
                    final c = items[index];
                    final id = c['id'];
                    final label = (c['label'] ?? c['name'])?.toString() ?? 'Category';
                    if (id == null) return const SizedBox.shrink();

                    return Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: _CategoryListTile(
                        label: label,
                        iconKey: c['icon_key']?.toString(),
                        slug: c['slug']?.toString(),
                        description: c['description']?.toString(),
                        onTap: () {
                          final loc = resp.locationId;
                          context.push('/gp/specialists?specialty_id=$id${loc == null ? '' : '&location_id=$loc'}&title=${Uri.encodeComponent(label)}');
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

class _CategoryListTile extends StatelessWidget {
  final String label;
  final String? iconKey;
  final String? slug;
  final String? description;
  final VoidCallback onTap;

  const _CategoryListTile({
    required this.label,
    this.iconKey,
    this.slug,
    this.description,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return AppCard(
      onTap: onTap,
      padding: const EdgeInsets.all(16),
      child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [AppColors.primaryBlue, AppColors.secondaryTeal],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Icon(
              AppCategoryIcons.fromKey(iconKey, slug: slug, name: label),
              color: Colors.white,
              size: 24,
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label, style: AppStyles.heading2.copyWith(fontSize: 16)),
                if (description != null && description!.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    description!,
                    style: AppStyles.bodyMedium.copyWith(color: AppColors.textSecondary),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ],
            ),
          ),
          const Icon(Icons.arrow_forward_ios, size: 16, color: AppColors.textMuted),
        ],
      ),
    );
  }
}