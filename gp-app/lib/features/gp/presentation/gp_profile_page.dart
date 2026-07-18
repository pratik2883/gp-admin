import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/features/auth/models/user.dart';
import 'package:gp_app/features/auth/state/auth_state.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/core/utils/initials_helper.dart';
import 'package:gp_app/features/gp/models/gp_profile.dart';
import 'package:gp_app/ui/widgets/app_card.dart';
import 'package:gp_app/ui/widgets/app_hero_header.dart';
import 'package:gp_app/ui/widgets/app_settings_row.dart';
import 'package:gp_app/ui/widgets/gp_bottom_nav.dart';
import 'package:gp_app/ui/widgets/stat_summary_card.dart';

class GpProfilePage extends ConsumerStatefulWidget {
  const GpProfilePage({super.key});

  @override
  ConsumerState<GpProfilePage> createState() => _GpProfilePageState();
}

class _GpProfilePageState extends ConsumerState<GpProfilePage> {
  @override
  void initState() {
    super.initState();
    final current = ref.read(gpHomeControllerProvider);
    final shouldLoad = current.maybeWhen(loaded: (_) => false, orElse: () => true);
    if (shouldLoad) {
      Future.microtask(() => ref.read(gpHomeControllerProvider.notifier).load());
    }
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authStateProvider);
    if (authState is! Authenticated) return const SizedBox();
    
    final user = authState.user;
    final String fullName = user.name;
    final String speciality = user.speciality ?? user.designation ?? 'General Practitioner';
    final regNoRaw = user.registrationNumber?.trim();
    final String regNo = (regNoRaw == null || regNoRaw.isEmpty) ? '—' : regNoRaw;

    final dashState = ref.watch(gpHomeControllerProvider);
    final (activeReferralsCount, successRate) = dashState.maybeWhen(
      loaded: (d) {
        final total = d.totalReferrals;
        final closed = d.closedCount;
        final active = (total - closed).clamp(0, 1 << 31).toInt();
        final rate = total <= 0 ? '—' : '${((closed / total) * 100).round()}%';
        return (active, rate);
      },
      orElse: () => (0, '—'),
    );
    
    final GpProfile profile = GpProfile(
      fullName: fullName,
      specialityTitle: speciality,
      registrationNumber: regNo,
      activeReferralsCount: activeReferralsCount,
      successRate: successRate,
    );

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
              leading: IconButton(
                onPressed: () {
                  if (Navigator.canPop(context)) {
                    Navigator.pop(context);
                  } else {
                    context.go('/gp/home');
                  }
                },
                icon: const Icon(Icons.arrow_back_rounded, color: AppColors.textOnDark),
              ),
              title: 'My Profile',
              subtitle: speciality,
            ),
            Expanded(
              child: ListView(
                padding: AppSpacing.screenPadding.copyWith(top: 18, bottom: 28),
                children: [
                  _profileSummaryCard(profile: profile, user: user),
                  const SizedBox(height: 14),
                  Row(
                    children: [
                      Expanded(
                        child: StatSummaryCard(
                          label: 'Active Referrals',
                          value: profile.activeReferralsCount.toString(),
                          icon: Icons.assignment_rounded,
                          accent: AppColors.primaryBlue,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: StatSummaryCard(
                          label: 'Success Rate',
                          value: profile.successRate,
                          icon: Icons.verified_rounded,
                          accent: AppColors.secondaryTeal,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 18),
                  AppCard(
                    padding: EdgeInsets.zero,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Padding(
                          padding: EdgeInsets.fromLTRB(16, 16, 16, 8),
                          child: Text('Account', style: AppStyles.heading2),
                        ),
                        AppSettingsRow(
                          icon: Icons.person_outline_rounded,
                          title: 'Account Settings',
                          subtitle: 'Profile, clinic, and security',
                          onTap: () => context.push('/account-settings'),
                        ),
                        const Divider(height: 1, thickness: 1, color: AppColors.borderLight),
                        AppSettingsRow(
                          icon: Icons.notifications_none_rounded,
                          title: 'Notification Preferences',
                          subtitle: 'Alerts and reminders',
                          onTap: () => context.push('/notification-preferences'),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                  AppCard(
                    padding: EdgeInsets.zero,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Padding(
                          padding: EdgeInsets.fromLTRB(16, 16, 16, 8),
                          child: Text('Support', style: AppStyles.heading2),
                        ),
                        AppSettingsRow(
                          icon: Icons.help_outline_rounded,
                          title: 'Support',
                          subtitle: 'Help and policies',
                          onTap: () => context.push('/support'),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                  AppCard(
                    padding: EdgeInsets.zero,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Padding(
                          padding: EdgeInsets.fromLTRB(16, 16, 16, 8),
                          child: Text('Session', style: AppStyles.heading2),
                        ),
                        AppSettingsRow(
                          icon: Icons.logout_rounded,
                          title: 'Logout',
                          subtitle: 'Sign out on this device',
                          destructive: true,
                          showChevron: false,
                          onTap: () async {
                            await ref.read(authStateProvider.notifier).logout();
                            if (context.mounted) {
                              context.go('/role');
                            }
                          },
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
        bottomNavigationBar: const GpBottomNav(currentIndex: 3),
      ),
    );
  }

  Widget _profileSummaryCard({
    required GpProfile profile,
    required User user,
  }) {
    final clinic = user.clinicName?.trim();
    final email = user.email?.trim();
    final mobile = user.mobile.trim();
    final location = [
      user.city?.trim(),
      user.address?.trim(),
    ].whereType<String>().where((s) => s.isNotEmpty).join(' · ');

    return AppCard(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 54,
            height: 54,
            decoration: BoxDecoration(
              gradient: AppColors.primaryGradient,
              borderRadius: BorderRadius.circular(18),
              boxShadow: AppStyles.cardShadow,
            ),
            child: Center(
              child: Text(
                getInitials(profile.fullName),
                style: AppStyles.heading2.copyWith(color: AppColors.textOnDark, fontWeight: FontWeight.w900),
              ),
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        profile.fullName,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: AppStyles.heading2,
                      ),
                    ),
                    const SizedBox(width: 10),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                      decoration: BoxDecoration(
                        color: AppColors.secondaryTeal.withAlpha(16),
                        borderRadius: AppStyles.radiusPill,
                        border: Border.all(color: AppColors.secondaryTeal.withAlpha(80)),
                      ),
                      child: Text(
                        profile.registrationNumber,
                        style: AppStyles.chipText.copyWith(color: AppColors.secondaryTeal),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 6),
                Text(profile.specialityTitle, style: AppStyles.bodySmall),
                if (clinic != null && clinic.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(clinic, style: AppStyles.bodySmall.copyWith(color: AppColors.textSecondary)),
                ],
                if (location.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(location, style: AppStyles.caption),
                ],
                if ((email != null && email.isNotEmpty) || mobile.isNotEmpty) ...[
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      if (mobile.isNotEmpty) ...[
                        const Icon(Icons.phone_rounded, size: 16, color: AppColors.textMuted),
                        const SizedBox(width: 6),
                        Flexible(child: Text(mobile, style: AppStyles.caption, overflow: TextOverflow.ellipsis)),
                      ],
                      if (mobile.isNotEmpty && email != null && email.isNotEmpty) ...[
                        const SizedBox(width: 12),
                      ],
                      if (email != null && email.isNotEmpty) ...[
                        const Icon(Icons.mail_outline_rounded, size: 16, color: AppColors.textMuted),
                        const SizedBox(width: 6),
                        Flexible(child: Text(email, style: AppStyles.caption, overflow: TextOverflow.ellipsis)),
                      ],
                    ],
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}
