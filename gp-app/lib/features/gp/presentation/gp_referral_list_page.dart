import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/features/gp/state/referral_list_state.dart';
import 'package:gp_app/ui/widgets/app_hero_header.dart';
import 'package:gp_app/ui/widgets/rounded_search_bar.dart';
import 'package:gp_app/ui/widgets/stat_summary_card.dart';
import 'package:gp_app/ui/widgets/section_header_row.dart';
import 'package:gp_app/ui/widgets/referral_patient_card.dart';
import 'package:gp_app/ui/widgets/app_card.dart';
import 'package:gp_app/ui/widgets/primary_button.dart';
import 'package:gp_app/ui/widgets/gp_bottom_nav.dart';
import 'package:gp_app/ui/widgets/home_skeletons.dart';
import 'package:gp_app/ui/widgets/app_segmented_control.dart';

class GpReferralListPage extends ConsumerStatefulWidget {
  const GpReferralListPage({super.key});
  @override
  ConsumerState<GpReferralListPage> createState() => _GpReferralListPageState();
}

class _GpReferralListPageState extends ConsumerState<GpReferralListPage> {
  final _scroll = ScrollController();

  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(referralListControllerProvider.notifier).refresh());
    _scroll.addListener(() {
      if (_scroll.position.pixels >= _scroll.position.maxScrollExtent - 100) {
        ref.read(referralListControllerProvider.notifier).loadMore();
      }
    });
  }

  @override
  void dispose() {
    _scroll.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(referralListControllerProvider);
    final initialLoading = state.refreshing && state.items.isEmpty;
    
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) {
        if (didPop) return;
        context.go('/gp/home');
      },
      child: Scaffold(
        backgroundColor: AppColors.background,
        body: Column(
          children: [
            AppHeroHeader(
              leading: Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: Colors.white.withAlpha(30),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: const Icon(Icons.assignment_rounded, color: Colors.white),
              ),
              title: 'My Referrals',
              subtitle: 'Track specialist, diagnostic & hospital referrals',
              bottom: Column(
                children: [
                  AppSegmentedControl<String>(
                    value: state.referralType,
                    options: const [
                      AppSegmentOption(value: 'specialist', label: 'Specialist', icon: Icons.person_search_outlined),
                      AppSegmentOption(value: 'diagnostic', label: 'Diagnostic', icon: Icons.biotech_outlined),
                      AppSegmentOption(value: 'hospital', label: 'Hospital', icon: Icons.local_hospital_outlined),
                    ],
                    onChanged: (v) => ref.read(referralListControllerProvider.notifier).setReferralType(v),
                  ),
                  const SizedBox(height: 12),
                  RoundedSearchBar(
                    hintText: 'Search referrals...',
                    onChanged: (v) => ref.read(referralListControllerProvider.notifier).refresh(q: v),
                  ),
                ],
              ),
            ),
            Expanded(
              child: RefreshIndicator(
                onRefresh: () async => ref.read(referralListControllerProvider.notifier).refresh(),
                color: AppColors.secondaryTeal,
                child: AnimatedSwitcher(
                  duration: const Duration(milliseconds: 220),
                  switchInCurve: Curves.easeOut,
                  switchOutCurve: Curves.easeIn,
                  child: initialLoading
                      ? KeyedSubtree(
                          key: const ValueKey('referrals_loading'),
                          child: _buildScrollView(state: state, showSkeleton: true),
                        )
                      : KeyedSubtree(
                          key: const ValueKey('referrals_loaded'),
                          child: _buildScrollView(state: state, showSkeleton: false),
                        ),
                ),
              ),
            ),
          ],
        ),
        bottomNavigationBar: const GpBottomNav(currentIndex: 1),
      ),
    );
  }

  Widget _buildScrollView({
    required ReferralListState state,
    required bool showSkeleton,
  }) {
    return CustomScrollView(
      controller: _scroll,
      slivers: [
        SliverToBoxAdapter(
          child: Padding(
            padding: AppSpacing.responsiveScreenPadding(context).copyWith(top: 16, bottom: 0),
            child: Row(
              children: [
                Expanded(
                  child: StatSummaryCard(
                    label: 'Total Submitted',
                    value: '${state.totalSubmitted}',
                    icon: Icons.layers_rounded,
                    accent: AppColors.primaryBlue,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: StatSummaryCard(
                    label: 'Conversion Rate',
                    value: '${state.conversionRate.round()}%',
                    icon: Icons.trending_up_rounded,
                    accent: AppColors.secondaryTeal,
                  ),
                ),
              ],
            ),
          ),
        ),
        SliverToBoxAdapter(
          child: Padding(
            padding: AppSpacing.responsiveScreenPadding(context).copyWith(top: 18, bottom: 12),
            child: SectionHeaderRow(
              title: 'Referrals',
              onAction: state.items.isEmpty ? null : () {},
            ),
          ),
        ),
        showSkeleton ? _buildSkeletonList() : _buildListContent(state),
        const SliverToBoxAdapter(child: SizedBox(height: 110)),
      ],
    );
  }

  Widget _buildSkeletonList() {
    return SliverFillRemaining(
      hasScrollBody: false,
      child: Padding(
        padding: AppSpacing.responsiveScreenPadding(context).copyWith(top: 0),
        child: AppShimmer(
          child: Column(
            children: List.generate(6, (i) {
              return const Padding(
                padding: EdgeInsets.only(bottom: 12),
                child: ReferralPatientCardSkeleton(),
              );
            }),
          ),
        ),
      ),
    );
  }

  Widget _buildListContent(ReferralListState state) {
    if (state.refreshing && state.items.isEmpty) {
      return _buildSkeletonList();
    }
    if (state.error != null && state.items.isEmpty) {
      return SliverFillRemaining(
        hasScrollBody: false,
        child: Center(
          child: Padding(
            padding: AppSpacing.responsiveScreenPadding(context),
            child: AppCard(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('We couldn’t load your referrals.', style: AppStyles.heading2),
                  const SizedBox(height: 8),
                  Text(
                    'Check your connection and try again.',
                    style: AppStyles.bodySmall.copyWith(color: AppColors.textSecondary),
                  ),
                  const SizedBox(height: 8),
                  Text(state.error!, style: AppStyles.caption.copyWith(color: AppColors.textMuted)),
                  const SizedBox(height: 16),
                  PrimaryButton(
                    label: 'Retry',
                    onPressed: () => ref.read(referralListControllerProvider.notifier).refresh(),
                  ),
                ],
              ),
            ),
          ),
        ),
      );
    }
    if (state.items.isEmpty) {
      return SliverFillRemaining(
        hasScrollBody: false,
        child: Center(
          child: Padding(
            padding: AppSpacing.responsiveScreenPadding(context),
            child: AppCard(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 54,
                    height: 54,
                    decoration: BoxDecoration(
                      color: AppColors.secondaryTeal.withAlpha(18),
                      borderRadius: BorderRadius.circular(18),
                    ),
                    child: Icon(Icons.assignment_outlined, color: AppColors.secondaryTeal.withAlpha(220)),
                  ),
                  const SizedBox(height: 14),
                  Text('No referrals yet', style: AppStyles.heading2, textAlign: TextAlign.center),
                  const SizedBox(height: 6),
                  Text(
                    'Referrals you send will appear here.',
                    style: AppStyles.bodySmall.copyWith(color: AppColors.textSecondary),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 6),
                  Text(
                    'Tap New Referral to get started.',
                    style: AppStyles.bodySmall.copyWith(color: AppColors.textSecondary),
                    textAlign: TextAlign.center,
                  ),
                ],
              ),
            ),
          ),
        ),
      );
    }

    return SliverPadding(
      padding: AppSpacing.responsiveScreenPadding(context).copyWith(top: 0, bottom: 0),
      sliver: SliverList(
        delegate: SliverChildBuilderDelegate(
          (context, index) {
            if (index == state.items.length) {
              return const Padding(
                padding: EdgeInsets.symmetric(vertical: 24),
                child: Center(child: CircularProgressIndicator(color: AppColors.primaryBlue)),
              );
            }
            final r = state.items[index];
            final type = (r.referral_type ?? state.referralType).toString();
            return Padding(
              padding: const EdgeInsets.only(bottom: 12.0),
              child: ReferralPatientCard(
                referral: r,
                showNotesPreview: true,
                notesMaxLines: 2,
                onTap: () => context.push('/gp/referrals/${r.id}?type=${Uri.encodeComponent(type)}'),
              ),
            );
          },
          childCount: state.items.length + (state.hasMore ? 1 : 0),
        ),
      ),
    );
  }
}
