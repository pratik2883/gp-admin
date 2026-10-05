import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/features/auth/state/auth_state.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/states.dart';
import 'package:gp_app/app/role.dart';
import 'package:gp_app/ui/widgets/unread_badge_icon.dart';
import 'package:gp_app/ui/widgets/app_hero_header.dart';
import 'package:gp_app/ui/widgets/rounded_search_bar.dart';
import 'package:gp_app/ui/widgets/global_search_modal.dart';
import 'package:gp_app/ui/widgets/promoted_doctors_section.dart';
import 'package:gp_app/ui/widgets/stat_summary_card.dart';
import 'package:gp_app/ui/widgets/section_header_row.dart';
import 'package:gp_app/features/specialist/presentation/widgets/lead_card.dart';
import 'package:gp_app/features/specialist/state/lead_list_refreshable.dart';
import 'package:flutter/services.dart';
import 'package:gp_app/ui/widgets/role_bottom_nav.dart';

class SpHomePage extends ConsumerStatefulWidget {
  final int initialIndex;
  const SpHomePage({super.key, this.initialIndex = 0});
  @override
  ConsumerState<SpHomePage> createState() => _SpHomePageState();
}

class _SpHomePageState extends ConsumerState<SpHomePage> {
  final _searchCtrl = TextEditingController();
  final _scroll = ScrollController();
  DateTime? _lastPressedAt;

  @override
  void initState() {
    super.initState();
    Future.microtask(() => _notifier().refresh(status: 'pending'));
    _scroll.addListener(() {
      if (_scroll.position.pixels >= _scroll.position.maxScrollExtent - 100) {
        _notifier().loadMore();
      }
    });
  }

  LeadListRefreshable _notifier() {
    return ref.read(selectedSubtypeProvider) == RoleSubtype.diagnostic
        ? ref.read(dxReferralListControllerProvider.notifier)
        : ref.read(spLeadListControllerProvider.notifier);
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    _scroll.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final hp = AppSpacing.responsiveHorizontal(context);
    final auth = ref.watch(authStateProvider);
    if (auth is! Authenticated) {
      return const Scaffold(body: Center(child: CircularProgressIndicator(color: AppColors.primaryBlue)));
    }

    final state = ref.watch(selectedSubtypeProvider) == RoleSubtype.diagnostic
        ? ref.watch(dxReferralListControllerProvider)
        : ref.watch(spLeadListControllerProvider);
    final subtype = ref.watch(selectedSubtypeProvider);
    final isDx = subtype == RoleSubtype.diagnostic;
    final isHospital = subtype == RoleSubtype.hospital;
    final showPromoted = isHospital || isDx;

    final rawName = auth.user.name.trim();
    final String userName;
    if (rawName.toLowerCase().startsWith('dr.') || rawName.toLowerCase().startsWith('dr ')) {
      final parts = rawName.split(RegExp(r'\s+'));
      userName = parts.length > 1 ? '${parts[0]} ${parts[1]}' : rawName;
    } else {
      userName = rawName.split(RegExp(r'\s+')).first;
    }

    final dashTitle = switch (subtype) {
      RoleSubtype.hospital => 'Hospital Dashboard',
      RoleSubtype.diagnostic => 'Diagnostic Portal',
      _ => 'Specialist Connect',
    };

    final isNotOnHomeTab = widget.initialIndex != 0;

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) async {
        if (didPop) return;
        if (isNotOnHomeTab) {
          context.go('/sp/home');
          return;
        }
        final now = DateTime.now();
        if (_lastPressedAt == null || now.difference(_lastPressedAt!) > const Duration(seconds: 2)) {
          _lastPressedAt = now;
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Press back again to exit'), duration: Duration(seconds: 2)),
          );
          return;
        }
        SystemNavigator.pop();
      },
      child: Scaffold(
        backgroundColor: AppColors.background,
        body: Column(
          children: [
            AppHeroHeader(
              leading: isNotOnHomeTab
                  ? IconButton(
                      onPressed: () => context.go('/sp/home'),
                      icon: const Icon(Icons.arrow_back_rounded, color: AppColors.textOnDark),
                    )
                  : null,
              title: dashTitle,
              subtitle: 'Hello, $userName',
              trailing: const UnreadBadgeIcon(),
              bottom: RoundedSearchBar(
                hintText: isDx ? 'Search hospitals & specialists...' : 'Search hospitals & centers...',
                onTap: () => GlobalSearchModal.show(context),
              ),
            ),
            Expanded(
              child: RefreshIndicator(
                onRefresh: () => _notifier().refresh(),
                color: AppColors.primaryBlue,
                child: ListView(
                  controller: _scroll,
                  padding: EdgeInsets.fromLTRB(hp, 16, hp, 110),
                  children: [
                    if (widget.initialIndex == 0) ...[
                      SizedBox(
                        height: 90,
                        child: ListView(
                          scrollDirection: Axis.horizontal,
                          children: [
                            _buildStatCard('New', 'pending', Icons.fiber_new_rounded, AppColors.secondaryTeal),
                            const SizedBox(width: 10),
                            _buildStatCard('Accepted', 'accepted', Icons.check_circle_rounded, AppColors.primaryBlue),
                            const SizedBox(width: 10),
                            _buildStatCard('Consulted', 'consulted', Icons.medical_services_rounded, AppColors.warningYellow),
                            const SizedBox(width: 10),
                            _buildStatCard('Closed', 'closed', Icons.verified_rounded, AppColors.successGreen),
                          ],
                        ),
                      ),
                      const SizedBox(height: 16),
                      if (showPromoted) ...[
                        const PromotedDoctorsSection(),
                        const SizedBox(height: 16),
                      ],
                    ],
                    SectionHeaderRow(title: isDx
                        ? (widget.initialIndex == 0 ? 'Recent Diagnostic Referrals' : 'All Diagnostic Referrals')
                        : (widget.initialIndex == 0 ? 'Recent Lead Requests' : 'All Leads')),
                    const SizedBox(height: 12),
                    if (state.refreshing && state.items.isEmpty)
                      const Center(child: Padding(
                        padding: EdgeInsets.only(top: 40),
                        child: CircularProgressIndicator(color: AppColors.primaryBlue),
                      ))
                    else if (state.error != null && state.items.isEmpty)
                      ErrorState(
                        message: state.error!,
                        onRetry: () => _notifier().refresh(),
                      )
                    else if (state.items.isEmpty)
                      EmptyState(message: isDx ? 'No diagnostic referrals found.' : 'No leads found for this filter.')
                    else
                      ...state.items.map((r) => Padding(
                        padding: const EdgeInsets.only(bottom: 12),
                        child: LeadCard(
                          lead: r,
                          onTap: () => context.push(isDx ? '/sp/dx-referrals/${r.id}' : '/sp/leads/${r.id}'),
                        ),
                      )),
                    if (state.loading)
                      const Padding(
                        padding: EdgeInsets.symmetric(vertical: 20),
                        child: Center(child: CircularProgressIndicator(color: AppColors.primaryBlue)),
                      ),
                  ],
                ),
              ),
            ),
          ],
        ),
        bottomNavigationBar: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (subtype != RoleSubtype.diagnostic)
              Padding(
                padding: EdgeInsets.fromLTRB(hp, 0, hp, 10),
                child: Row(
                  children: [
                    Expanded(
                      child: _DashboardActionButton(
                        label: 'Hospital Referral',
                        icon: Icons.local_hospital_outlined,
                        backgroundColor: AppColors.primaryBlue,
                        onTap: () => context.push('/sp/hospital-referrals/new'),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _DashboardActionButton(
                        label: 'Diagnostic Referral',
                        icon: Icons.biotech_outlined,
                        backgroundColor: AppColors.secondaryTeal,
                        onTap: () => context.push('/sp/diagnostic-referrals/new'),
                      ),
                    ),
                  ],
                ),
              ),
            RoleBottomNav(currentIndex: widget.initialIndex, role: AppRole.specialist),
          ],
        ),
      ),
    );
  }

  Widget _buildStatCard(String label, String statusKey, IconData icon, Color accent) {
    return SizedBox(
      width: 140,
      child: StatSummaryCard(
        label: label,
        value: label == 'New' ? 'Pending' : label,
        icon: icon,
        accent: accent,
        onTap: () => _notifier().refresh(status: statusKey),
      ),
    );
  }
}

class _DashboardActionButton extends StatelessWidget {
  final String label;
  final IconData icon;
  final Color backgroundColor;
  final VoidCallback onTap;

  const _DashboardActionButton({
    required this.label,
    required this.icon,
    required this.backgroundColor,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 56,
      child: ElevatedButton.icon(
        onPressed: onTap,
        style: ElevatedButton.styleFrom(
          backgroundColor: backgroundColor,
          foregroundColor: Colors.white,
          elevation: 8,
          shadowColor: Colors.black.withAlpha(35),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(18),
          ),
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
          textStyle: AppStyles.bodyMedium.copyWith(
            color: Colors.white,
            fontWeight: FontWeight.w700,
          ),
        ),
        icon: Icon(icon, size: 20),
        label: Text(
          label,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
      ),
    );
  }
}
