import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/ui/styles.dart';

class GpBottomNav extends StatelessWidget {
  final int currentIndex;
  final bool approved;

  const GpBottomNav({
    super.key,
    required this.currentIndex,
    this.approved = true,
  });

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      top: false,
      child: Container(
        margin: const EdgeInsets.fromLTRB(12, 0, 12, 8),
        padding: const EdgeInsets.fromLTRB(8, 8, 8, 8),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(26),
          boxShadow: AppStyles.navShadow,
          border: Border.all(color: AppColors.border),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceEvenly,
          children: [
            _NavItem(
              label: 'Home',
              icon: Icons.home_rounded,
              selected: currentIndex == 0,
              onTap: () => _go(context, 0),
            ),
            if (approved) ...[
              _NavItem(
                label: 'Referrals',
                icon: Icons.assignment_rounded,
                selected: currentIndex == 1,
                onTap: () => _go(context, 1),
              ),
              _CenterAction(
                onTap: () => context.push('/gp/referrals/new'),
              ),
              _NavItem(
                label: 'Diagnostics',
                icon: Icons.biotech_rounded,
                selected: currentIndex == 2,
                onTap: () => _go(context, 2),
              ),
            ],
            _NavItem(
              label: 'Profile',
              icon: Icons.account_circle_rounded,
              selected: currentIndex == 3,
              onTap: () => _go(context, 3),
            ),
          ],
        ),
      ),
    );
  }

  void _go(BuildContext context, int index) {
    if (index == currentIndex) return;
    if (index == 0) context.go('/gp/home');
    if (index == 1) context.go('/gp/referrals');
    if (index == 2) context.go('/gp/diagnostics');
    if (index == 3) context.go('/gp/profile');
  }
}

class _NavItem extends StatelessWidget {
  final String label;
  final IconData icon;
  final bool selected;
  final VoidCallback onTap;

  const _NavItem({
    required this.label,
    required this.icon,
    required this.selected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final color = selected ? AppColors.secondaryTeal : AppColors.textMuted;
    return InkWell(
      borderRadius: AppStyles.radiusPill,
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 2, vertical: 6),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            SizedBox(
              width: 24,
              height: 24,
              child: Center(
                child: Icon(icon, color: color, size: 20),
              ),
            ),
            const SizedBox(height: 3),
            Text(
              label,
              style: AppStyles.caption.copyWith(
                color: color,
                fontWeight: selected ? FontWeight.w800 : FontWeight.w600,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}

class _CenterAction extends StatelessWidget {
  final VoidCallback onTap;

  const _CenterAction({required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(999),
        child: Container(
          width: 52,
          height: 52,
          decoration: BoxDecoration(
            gradient: AppColors.primaryGradient,
            borderRadius: BorderRadius.circular(999),
            boxShadow: const [
              BoxShadow(
                color: Color(0x300B3B8B),
                blurRadius: 18,
                offset: Offset(0, 10),
              ),
            ],
          ),
          child: const Icon(Icons.add_rounded, color: Colors.white, size: 26),
        ),
      ),
    );
  }
}
