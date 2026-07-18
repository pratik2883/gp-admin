import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_card.dart';
import 'package:gp_app/ui/widgets/app_hero_header.dart';
import 'package:gp_app/ui/widgets/gp_bottom_nav.dart';
import 'package:gp_app/ui/widgets/quick_action_tile.dart';
import 'package:gp_app/ui/widgets/states.dart';
import 'package:gp_app/ui/widgets/unread_badge_icon.dart';

class GpDiagnosticsPage extends ConsumerWidget {
  const GpDiagnosticsPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
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
              title: 'Diagnostics',
              subtitle: 'Book tests and manage requests',
              trailing: const UnreadBadgeIcon(),
            ),
            Expanded(
              child: ListView(
                padding: AppSpacing.screenPadding.copyWith(top: 18, bottom: 110),
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: QuickActionTile(
                          icon: Icons.biotech_rounded,
                          label: 'Book Test',
                          accent: AppColors.secondaryTeal,
                          onTap: () => context.push('/gp/referrals/new?type=diagnostic'),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  AppCard(
                    child: const Padding(
                      padding: EdgeInsets.symmetric(vertical: 28),
                      child: EmptyState(
                        message: 'No diagnostic requests yet',
                        subtitle: 'Your booked tests will appear here.',
                        icon: Icons.biotech_outlined,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
        bottomNavigationBar: const GpBottomNav(currentIndex: 2),
      ),
    );
  }
}
