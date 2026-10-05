import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/core/storage/secure_storage.dart';
import 'package:gp_app/features/auth/state/auth_state.dart';
import 'package:gp_app/features/gp/state/gp_home_state.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/states.dart';
import 'package:flutter/services.dart';
import 'package:gp_app/ui/widgets/unread_badge_icon.dart';
import 'package:gp_app/ui/widgets/app_hero_header.dart';
import 'package:gp_app/ui/widgets/quick_action_tile.dart';
import 'package:gp_app/ui/widgets/stat_summary_card.dart';
import 'package:gp_app/ui/widgets/section_header_row.dart';
import 'package:gp_app/ui/widgets/referral_patient_card.dart';
import 'package:gp_app/ui/widgets/gp_bottom_nav.dart';
import 'package:gp_app/ui/widgets/home_skeletons.dart';
import 'package:gp_app/ui/widgets/app_card.dart';
import 'package:gp_app/ui/widgets/primary_button.dart';
import 'package:gp_app/ui/widgets/rounded_search_bar.dart';
import 'package:gp_app/ui/widgets/global_search_modal.dart';
import 'package:gp_app/ui/widgets/promoted_doctors_section.dart';

class GpHomePage extends ConsumerStatefulWidget {
  const GpHomePage({super.key});
  @override
  ConsumerState<GpHomePage> createState() => _GpHomePageState();
}

class _GpHomePageState extends ConsumerState<GpHomePage> {
  DateTime? _lastPressedAt;
  late final ProviderSubscription<AsyncValue<List<Map<String, dynamic>>>> _nearbyLocationsSub;

  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(gpHomeControllerProvider.notifier).load());
    _nearbyLocationsSub = ref.listenManual<AsyncValue<List<Map<String, dynamic>>>>(
      nearbyReferralLocationsProvider,
      (_, next) async {
        if (!mounted) return;
        if (ref.read(nearbyReferralLocationIdProvider) != null) return;
        final saved = await AppLocalStorage.instance.getString('nearby_referral_location_id');
        final savedId = int.tryParse((saved ?? '').trim());
        final locations = next.asData?.value;
        if (locations == null || locations.isEmpty) return;

        int? selected;

        // 1. Try saved preference
        if (savedId != null) {
          for (final l in locations) {
            final id = int.tryParse('${l['id']}');
            if (id == savedId) {
              selected = savedId;
              break;
            }
          }
        }

        // 2. Try GPS auto-detect
        if (selected == null) {
          final gps = await ref.read(locationServiceProvider).getCurrentLocation();
          if (gps.city != null && gps.error == null) {
            final gpsCity = gps.city!.toLowerCase().trim();
            for (final l in locations) {
              final name = (l['name'] ?? '').toString().toLowerCase().trim();
              if (name.contains(gpsCity) || gpsCity.contains(name)) {
                selected = int.tryParse('${l['id']}');
                if (selected != null) break;
              }
            }
          }
        }

        // 3. Fallback to first location
        selected ??= int.tryParse('${locations.first['id']}');
        if (selected == null) return;
        ref.read(nearbyReferralLocationIdProvider.notifier).state = selected;
      },
    );
  }

  @override
  void dispose() {
    _nearbyLocationsSub.close();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final auth = ref.watch(authStateProvider);
    if (auth is! Authenticated) return const Scaffold(body: GpHomeSkeleton());
    final dash = ref.watch(gpHomeControllerProvider);
    final greeting = _greeting();
    final userName = auth.user.name.trim().isEmpty ? 'Doctor' : auth.user.name.trim();

    final gpStatus = auth.user.gpStatus;
    final knownLocked = gpStatus == 'pending' || gpStatus == 'blocked';
    final showLock = knownLocked || dash is GpHomeNotApproved;

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) async {
        if (didPop) return;
        
        final now = DateTime.now();
        if (_lastPressedAt == null || now.difference(_lastPressedAt!) > const Duration(seconds: 2)) {
          _lastPressedAt = now;
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Press back again to exit'),
              duration: Duration(seconds: 2),
            ),
          );
          return;
        }
        
        SystemNavigator.pop();
      },
      child: Scaffold(
      backgroundColor: AppColors.background,
      body: AnimatedSwitcher(
        duration: const Duration(milliseconds: 220),
        switchInCurve: Curves.easeOut,
        switchOutCurve: Curves.easeIn,
        child: showLock
            ? KeyedSubtree(
                key: const ValueKey('home_not_approved'),
                child: _PendingApprovalView(
                  blocked: gpStatus == 'blocked',
                  message: dash is GpHomeError
                      ? dash.message
                      : dash is GpHomeNotApproved
                          ? dash.message
                          : null,
                  onCheckStatus: () => ref.read(gpHomeControllerProvider.notifier).load(),
                ),
              )
            : dash.when(
                loading: () => const KeyedSubtree(key: ValueKey('home_loading'), child: GpHomeSkeleton()),
                error: (m) => KeyedSubtree(
                  key: const ValueKey('home_error'),
                  child: Padding(
                    padding: AppSpacing.screenPadding.copyWith(top: 18, bottom: 28),
                    child: AppCard(
                      child: ErrorState(
                        title: 'We couldn’t load your dashboard.',
                        subtitle: 'Check your connection and try again.',
                        message: m,
                        onRetry: () => ref.read(gpHomeControllerProvider.notifier).load(),
                      ),
                    ),
                  ),
                ),
                notApproved: (m) => const SizedBox.shrink(),
                loaded: (d) => KeyedSubtree(
            key: const ValueKey('home_loaded'),
            child: Column(
              children: [
                AppHeroHeader(
                  title: 'Specialist Connect',
                  subtitle: '$greeting, $userName',
                  trailing: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const SizedBox(
                        width: 40,
                        height: 40,
                        child: Center(child: UnreadBadgeIcon()),
                      ),
                      const SizedBox(width: 8),
                      InkWell(
                        onTap: () => context.go('/gp/profile'),
                        borderRadius: BorderRadius.circular(999),
                        child: Container(
                          width: 40,
                          height: 40,
                          decoration: BoxDecoration(
                            color: Colors.white.withAlpha(230),
                            borderRadius: BorderRadius.circular(999),
                            border: Border.all(color: Colors.white.withAlpha(210)),
                          ),
                          child: Center(
                            child: Text(
                              userName.isNotEmpty ? userName.trim().substring(0, 1).toUpperCase() : 'D',
                              style: AppStyles.bodySmall.copyWith(fontWeight: FontWeight.w900, color: AppColors.primaryBlue),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                  bottom: RoundedSearchBar(
                    hintText: 'Search specialists, hospitals, centers...',
                    onTap: () => GlobalSearchModal.show(context),
                  ),
                ),
                Expanded(
                  child: ListView(
                    padding: AppSpacing.responsiveScreenPadding(context).copyWith(top: 16, bottom: 110),
                    children: [
                      _buildQuickActions(context),
                      const SizedBox(height: 18),
                      _buildStatsRow(context, d),
                      const SizedBox(height: 22),
                      _buildNearbyReferralSection(context, ref),
                      const SizedBox(height: 18),
                      const PromotedDoctorsSection(),
                      const SizedBox(height: 18),
                      _buildBrowseByLocationSection(context),
                      const SizedBox(height: 22),
                      SectionHeaderRow(
                        title: 'Recently Referred Patients',
                        onAction: () => context.go('/gp/referrals'),
                      ),
                      const SizedBox(height: 12),
                      if (d.recentReferrals.isEmpty)
                        const EmptyState(
                          message: 'No referrals yet',
                          subtitle: 'Referrals you send will appear here.',
                          icon: Icons.assignment_outlined,
                        )
                      else
                        ...d.recentReferrals.take(4).map((r) {
                          return Padding(
                            padding: const EdgeInsets.only(bottom: 12),
                            child: ReferralPatientCard(
                              referral: r,
                              onTap: () => context.push('/gp/referrals/${r.id}'),
                            ),
                          );
                        }),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
      bottomNavigationBar: GpBottomNav(currentIndex: 0, approved: !showLock),
    ),
  );
}

  String _greeting() {
    final hour = DateTime.now().hour;
    if (hour < 12) return 'Good morning';
    if (hour < 17) return 'Good afternoon';
    return 'Good evening';
  }

  Widget _buildQuickActions(BuildContext context) {
    final screenWidth = MediaQuery.sizeOf(context).width;
    final spacing = screenWidth < 360 ? 8.0 : 12.0;
    return Row(
      children: [
        Expanded(
          child: QuickActionTile(
            icon: Icons.add_rounded,
            label: 'New Referral',
            accent: AppColors.secondaryTeal,
            onTap: () => context.push('/gp/referrals/new'),
          ),
        ),
        SizedBox(width: spacing),
        Expanded(
          child: QuickActionTile(
            icon: Icons.assignment_rounded,
            label: 'My Referrals',
            accent: AppColors.primaryBlue,
            onTap: () => context.go('/gp/referrals'),
          ),
        ),
      ],
    );
  }

  Widget _buildStatsRow(BuildContext context, dynamic d) {
    final screenWidth = MediaQuery.sizeOf(context).width;
    final cardWidth = screenWidth < 360 ? 180.0 : (screenWidth > 420 ? 240.0 : 220.0);
    return SizedBox(
      height: 88,
      child: ListView(
        scrollDirection: Axis.horizontal,
        children: [
          SizedBox(
            width: cardWidth,
            child: StatSummaryCard(
              label: 'Total Referrals',
              value: '${d.totalReferrals}',
              icon: Icons.stacked_line_chart_rounded,
              accent: AppColors.primaryBlue,
            ),
          ),
          SizedBox(width: screenWidth < 360 ? 8 : 12),
          SizedBox(
            width: cardWidth,
            child: StatSummaryCard(
              label: 'Pending',
              value: '${d.pendingCount}',
              icon: Icons.hourglass_bottom_rounded,
              accent: AppColors.warningYellow,
            ),
          ),
          SizedBox(width: screenWidth < 360 ? 8 : 12),
          SizedBox(
            width: cardWidth,
            child: StatSummaryCard(
              label: 'Completed',
              value: '${d.closedCount}',
              icon: Icons.verified_rounded,
              accent: AppColors.successGreen,
            ),
          ),
        ],
      ),
    );
  }
}

class _PendingApprovalView extends StatelessWidget {
  final bool blocked;
  final String? message;
  final VoidCallback onCheckStatus;

  const _PendingApprovalView({
    required this.blocked,
    this.message,
    required this.onCheckStatus,
  });

  @override
  Widget build(BuildContext context) {
    final color = blocked ? AppColors.errorRed : AppColors.warningYellow;
    return Padding(
      padding: AppSpacing.screenPadding.copyWith(top: 24, bottom: 28),
      child: AppCard(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 64,
                height: 64,
                decoration: BoxDecoration(
                  color: color.withAlpha(20),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Icon(
                  blocked ? Icons.block_rounded : Icons.hourglass_top_rounded,
                  color: color,
                  size: 30,
                ),
              ),
              const SizedBox(height: 16),
              Text(
                blocked ? 'Account Blocked' : 'Account Pending Approval',
                style: AppStyles.heading2,
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 8),
              Text(
                blocked
                    ? 'Your account has been blocked by the administrator. If you think this is a mistake, please contact support.'
                    : 'Your GP profile is waiting for admin verification. This usually takes 1–2 business days. You can complete your profile details in the meantime.',
                style: AppStyles.bodySmall.copyWith(color: AppColors.textSecondary),
                textAlign: TextAlign.center,
              ),
              if (message != null && message!.trim().isNotEmpty) ...[
                const SizedBox(height: 8),
                Text(
                  message!.trim(),
                  style: AppStyles.caption.copyWith(color: AppColors.textMuted),
                  textAlign: TextAlign.center,
                ),
              ],
              const SizedBox(height: 20),
              PrimaryButton(
                label: 'Check Status',
                onPressed: onCheckStatus,
              ),
              const SizedBox(height: 8),
              TextButton(
                onPressed: () => context.push('/support'),
                child: Text(
                  'Contact Support',
                  style: AppStyles.buttonText.copyWith(color: AppColors.secondaryTeal),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

Widget _buildNearbyReferralSection(BuildContext context, WidgetRef ref) {
  final locationsAsync = ref.watch(nearbyReferralLocationsProvider);
  final selectedLocationId = ref.watch(nearbyReferralLocationIdProvider);
  final gpsAsync = ref.watch(currentLocationProvider);
  final gps = gpsAsync.asData?.value;
  final hasGpsFix = gps != null && gps.city != null && gps.error == null;

  return Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      const SectionHeaderRow(title: 'Nearby Referral'),
      const SizedBox(height: 12),
      locationsAsync.when(
        loading: () => const AppShimmer(child: _NearbyReferralSkeleton()),
        error: (err, _) => AppCard(
          child: ErrorState(
            title: 'Load failed',
            subtitle: 'We couldn\'t load locations.',
            message: err.toString(),
            onRetry: () => ref.invalidate(nearbyReferralLocationsProvider),
          ),
        ),
        data: (locations) {
          final selected = _pickLocationMap(locations, selectedLocationId) ?? (locations.isEmpty ? null : locations.first);
          final selectedId = int.tryParse('${selected?['id']}');
          final selectedName = (selected?['name'] ?? selected?['label'] ?? 'Select Location').toString();

          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              AppCard(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
                onTap: locations.isEmpty
                    ? null
                    : () async {
                        final picked = await _pickNearbyLocationId(
                          context,
                          locations: locations,
                          selectedId: selectedId,
                        );
                        if (picked == null) return;
                        ref.read(nearbyReferralLocationIdProvider.notifier).state = picked;
                        await AppLocalStorage.instance.setString('nearby_referral_location_id', picked.toString());
                        ref.invalidate(nearbyReferralCategoriesByLocationProvider(picked));
                      },
                child: Row(
                  children: [
                    Container(
                      width: 42,
                      height: 42,
                      decoration: BoxDecoration(
                        color: AppColors.primaryBlue.withAlpha(16),
                        borderRadius: BorderRadius.circular(14),
                      ),
                      child: gpsAsync.isLoading
                          ? const Padding(
                              padding: EdgeInsets.all(10),
                              child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.primaryBlue),
                            )
                          : hasGpsFix
                              ? const Icon(Icons.my_location_rounded, color: AppColors.primaryBlue, size: 22)
                              : const Icon(Icons.place_rounded, color: AppColors.primaryBlue, size: 22),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Location', style: AppStyles.caption.copyWith(color: AppColors.textSecondary, fontWeight: FontWeight.w700)),
                          const SizedBox(height: 3),
                          Row(
                            children: [
                              Flexible(
                                child: Text(
                                  selectedName,
                                  style: AppStyles.bodyMedium.copyWith(fontWeight: FontWeight.w800),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                              if (hasGpsFix)
                                Padding(
                                  padding: const EdgeInsets.only(left: 6),
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                    decoration: BoxDecoration(
                                      color: AppColors.successGreen.withAlpha(20),
                                      borderRadius: BorderRadius.circular(4),
                                    ),
                                    child: Text(
                                      'GPS',
                                      style: AppStyles.caption.copyWith(color: AppColors.successGreen, fontSize: 8, fontWeight: FontWeight.w800),
                                    ),
                                  ),
                                ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    Text(
                      'Change',
                      style: AppStyles.bodySmall.copyWith(color: AppColors.secondaryTeal, fontWeight: FontWeight.w800),
                    ),
                    const SizedBox(width: 6),
                    const Icon(Icons.chevron_right_rounded, color: AppColors.textMuted),
                  ],
                ),
              ),
              if (selectedId == null) ...[
                const SizedBox(height: 12),
                const EmptyState(
                  message: 'Select a location to continue',
                  subtitle: 'Nearby categories will appear after you choose your area.',
                  icon: Icons.place_outlined,
                ),
              ] else ...[
                const SizedBox(height: 14),
                Text('Select Specialty', style: AppStyles.heading2.copyWith(fontSize: 16)),
                const SizedBox(height: 10),
                _NearbyCategoriesGrid(
                  locationId: selectedId,
                  onCategoryTap: (categoryId, label) {
                    context.push(
                      '/gp/specialists?specialty_id=$categoryId&location_id=$selectedId&title=${Uri.encodeComponent(label)}',
                    );
                  },
                ),
              ],
            ],
          );
        },
      ),
    ],
  );
}

Widget _buildBrowseByLocationSection(BuildContext context) {
  final screenWidth = MediaQuery.sizeOf(context).width;
  final spacing = screenWidth < 360 ? 8.0 : 12.0;
  final vPad = screenWidth < 360 ? 12.0 : 14.0;

  return Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      const SectionHeaderRow(title: 'Browse by Location'),
      const SizedBox(height: 12),
      Row(
        children: [
          Expanded(
            child: _BrowseQuickActionCard(
              icon: Icons.person_search_rounded,
              label: 'Specialist',
              subtitle: 'Find nearby',
              accent: AppColors.primaryBlue,
              onTap: () => context.push('/gp/specialists'),
              vPad: vPad,
            ),
          ),
          SizedBox(width: spacing),
          Expanded(
            child: _BrowseQuickActionCard(
              icon: Icons.local_hospital_rounded,
              label: 'Hospital',
              subtitle: 'Find nearby',
              accent: AppColors.secondaryTeal,
              onTap: () => context.push('/gp/hospitals'),
              vPad: vPad,
            ),
          ),
          SizedBox(width: spacing),
          Expanded(
            child: _BrowseQuickActionCard(
              icon: Icons.biotech_rounded,
              label: 'Diagnostic',
              subtitle: 'Book a test',
              accent: AppColors.warningYellow,
              onTap: () => context.push('/gp/diagnostic-centers'),
              vPad: vPad,
            ),
          ),
        ],
      ),
    ],
  );
}

class _BrowseQuickActionCard extends StatelessWidget {
  final IconData icon;
  final String label;
  final String subtitle;
  final Color accent;
  final VoidCallback onTap;
  final double vPad;

  const _BrowseQuickActionCard({
    required this.icon,
    required this.label,
    required this.subtitle,
    required this.accent,
    required this.onTap,
    this.vPad = 14,
  });

  @override
  Widget build(BuildContext context) {
    final isCompact = MediaQuery.sizeOf(context).width < 360;
    return AppCard(
      onTap: onTap,
      padding: EdgeInsets.symmetric(vertical: vPad, horizontal: isCompact ? 8 : 12),
      child: Column(
        children: [
          Container(
            width: isCompact ? 34 : 40,
            height: isCompact ? 34 : 40,
            decoration: BoxDecoration(
              color: accent.withAlpha(20),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, color: accent, size: isCompact ? 18 : 22),
          ),
          const SizedBox(height: 6),
          Text(
            label,
            style: AppStyles.bodySmall.copyWith(fontWeight: FontWeight.w700),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 2),
          Text(
            subtitle,
            style: AppStyles.caption.copyWith(color: AppColors.textMuted, fontSize: 9),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }
}

Map<String, dynamic>? _pickLocationMap(List<Map<String, dynamic>> locations, int? selectedId) {
  if (selectedId == null) return null;
  for (final l in locations) {
    final id = int.tryParse('${l['id']}');
    if (id == selectedId) return l;
  }
  return null;
}

class _NearbyReferralSkeleton extends StatelessWidget {
  const _NearbyReferralSkeleton();

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        const AppCard(
          padding: EdgeInsets.symmetric(horizontal: 14, vertical: 14),
          child: Row(
            children: [
              CircleAvatar(radius: 20, backgroundColor: AppColors.inputBackground),
              SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    AppSkeletonBox(width: 56, height: 10, borderRadius: BorderRadius.all(Radius.circular(999))),
                    SizedBox(height: 8),
                    AppSkeletonBox(width: 140, height: 12, borderRadius: BorderRadius.all(Radius.circular(999))),
                  ],
                ),
              ),
              AppSkeletonBox(width: 44, height: 10, borderRadius: BorderRadius.all(Radius.circular(999))),
            ],
          ),
        ),
        const SizedBox(height: 14),
        GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 3,
            crossAxisSpacing: 12,
            mainAxisSpacing: 12,
            childAspectRatio: 1.0,
          ),
          itemCount: 6,
          itemBuilder: (_, __) => const AppCard(
            padding: EdgeInsets.all(12),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                CircleAvatar(radius: 16, backgroundColor: AppColors.inputBackground),
                SizedBox(height: 8),
                AppSkeletonBox(width: 56, height: 10, borderRadius: BorderRadius.all(Radius.circular(999))),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

class _NearbyCategoriesGrid extends ConsumerWidget {
  final int locationId;
  final void Function(dynamic categoryId, String label) onCategoryTap;

  const _NearbyCategoriesGrid({
    required this.locationId,
    required this.onCategoryTap,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final categoriesAsync = ref.watch(nearbyReferralCategoriesByLocationProvider(locationId));

    return categoriesAsync.when(
      loading: () => const AppShimmer(child: _NearbyCategoriesSkeleton()),
      error: (err, _) => AppCard(
        child: ErrorState(
          title: 'Load failed',
          subtitle: 'We couldn\'t load categories for this location.',
          message: err.toString(),
          onRetry: () => ref.invalidate(nearbyReferralCategoriesByLocationProvider(locationId)),
        ),
      ),
      data: (items) {
        if (items.isEmpty) {
          return const EmptyState(
            message: 'No categories available',
            subtitle: 'Try changing your location.',
            icon: Icons.category_outlined,
          );
        }

        return LayoutBuilder(
          builder: (context, constraints) {
            final spacing = constraints.maxWidth < 360 ? 8.0 : 12.0;
            final minTileHeight = constraints.maxWidth < 360 ? 96.0 : 116.0;
            final maxWidth = constraints.maxWidth;
            final crossAxisCount = maxWidth < 320 ? 2 : 3;
            final tileWidth = (maxWidth - (spacing * (crossAxisCount - 1))) / crossAxisCount;
            final ratio = (tileWidth / minTileHeight).clamp(0.65, 1.4);

            return GridView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: crossAxisCount,
                crossAxisSpacing: spacing,
                mainAxisSpacing: spacing,
                childAspectRatio: ratio,
              ),
              itemCount: items.length > 9 ? 9 : items.length,
              itemBuilder: (context, i) {
                final c = items[i];
                final id = c['id'];
                final label = (c['label'] ?? c['name'])?.toString() ?? 'Category';
                if (id == null) return const SizedBox.shrink();
                return _NearbyCategoryCard(
                  label: label,
                  iconKey: c['icon_key']?.toString(),
                  slug: c['slug']?.toString(),
                  onTap: () => onCategoryTap(id, label),
                );
              },
            );
          },
        );
      },
    );
  }
}

class _NearbyCategoriesSkeleton extends StatelessWidget {
  const _NearbyCategoriesSkeleton();

  @override
  Widget build(BuildContext context) {
    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 3,
        crossAxisSpacing: 12,
        mainAxisSpacing: 12,
        childAspectRatio: 1.0,
      ),
      itemCount: 6,
      itemBuilder: (_, __) => const AppCard(
        padding: EdgeInsets.all(12),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            CircleAvatar(radius: 16, backgroundColor: AppColors.inputBackground),
            SizedBox(height: 8),
            AppSkeletonBox(width: 56, height: 10, borderRadius: BorderRadius.all(Radius.circular(999))),
          ],
        ),
      ),
    );
  }
}

class _NearbyCategoryCard extends StatelessWidget {
  final String label;
  final String? iconKey;
  final String? slug;
  final VoidCallback onTap;

  const _NearbyCategoryCard({
    required this.label,
    required this.onTap,
    this.iconKey,
    this.slug,
  });

  @override
  Widget build(BuildContext context) {
    return AppCard(
      onTap: onTap,
      padding: const EdgeInsets.all(12),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 36,
            height: 36,
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [AppColors.primaryBlue, AppColors.secondaryTeal],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(
              AppCategoryIcons.fromKey(iconKey, slug: slug, name: label),
              color: Colors.white,
              size: 20,
            ),
          ),
          const SizedBox(height: 6),
          Expanded(
            child: Center(
              child: Text(
                label,
                style: AppStyles.bodySmall.copyWith(fontWeight: FontWeight.w700),
                textAlign: TextAlign.center,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

Future<int?> _pickNearbyLocationId(
  BuildContext context, {
  required List<Map<String, dynamic>> locations,
  required int? selectedId,
}) {
  final controller = TextEditingController();
  return showModalBottomSheet<int>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (context) {
      String query = '';
      return StatefulBuilder(
        builder: (context, setModalState) {
          final filtered = locations.where((l) {
            final name = (l['name'] ?? l['label'] ?? '').toString().toLowerCase();
            return name.contains(query.toLowerCase());
          }).toList();

          final hp = AppSpacing.responsiveHorizontal(context);

          return SafeArea(
            child: Container(
              decoration: const BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
              ),
              child: Padding(
                padding: EdgeInsets.fromLTRB(hp, 10, hp, 16),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 44,
                      height: 4,
                      decoration: BoxDecoration(
                        color: AppColors.border,
                        borderRadius: BorderRadius.circular(999),
                      ),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(child: Text('Select Location', style: AppStyles.heading2)),
                        IconButton(
                          onPressed: () => Navigator.pop(context),
                          icon: const Icon(Icons.close_rounded),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    RoundedSearchBar(
                      hintText: 'Search locations...',
                      controller: controller,
                      onChanged: (v) => setModalState(() => query = v),
                    ),
                    const SizedBox(height: 10),
                    Flexible(
                      child: ListView.builder(
                        shrinkWrap: true,
                        itemCount: filtered.length,
                        itemBuilder: (context, index) {
                          final l = filtered[index];
                          final id = int.tryParse('${l['id']}');
                          final name = (l['name'] ?? l['label'] ?? 'Location').toString();
                          final isSelected = id != null && id == selectedId;

                          return Padding(
                            padding: const EdgeInsets.only(bottom: 10),
                            child: AppCard(
                              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                              color: isSelected ? AppColors.primaryBlue.withAlpha(12) : null,
                              border: isSelected ? Border.all(color: AppColors.primaryBlue.withAlpha(60)) : null,
                              onTap: id == null ? null : () => Navigator.pop<int>(context, id),
                              child: Row(
                                children: [
                                  Container(
                                    width: 36,
                                    height: 36,
                                    decoration: BoxDecoration(
                                      color: AppColors.primaryBlue.withAlpha(16),
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: const Icon(Icons.place_rounded, color: AppColors.primaryBlue, size: 18),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Text(
                                      name,
                                      style: AppStyles.bodyMedium.copyWith(fontWeight: FontWeight.w800),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                  if (isSelected)
                                    const Icon(Icons.check_circle_rounded, color: AppColors.secondaryTeal)
                                  else
                                    const Icon(Icons.chevron_right_rounded, color: AppColors.textMuted),
                                ],
                              ),
                            ),
                          );
                        },
                      ),
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      );
    },
  ).whenComplete(controller.dispose);
}
