import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/app/role.dart';
import 'package:gp_app/ui/styles.dart';

class RoleBottomNav extends ConsumerStatefulWidget {
  final int currentIndex;
  final AppRole role;

  const RoleBottomNav({
    super.key,
    required this.currentIndex,
    required this.role,
  });

  @override
  ConsumerState<RoleBottomNav> createState() => _RoleBottomNavState();
}

class _RoleBottomNavState extends ConsumerState<RoleBottomNav> {
  void _onTap(int index) {
    if (index == widget.currentIndex) return;
    final subtype = ref.read(selectedSubtypeProvider);

    if (widget.role == AppRole.gp) {
      if (index == 0) context.go('/gp/home');
      if (index == 1) context.go('/gp/referrals');
      if (index == 2) context.go('/gp/profile');
    } else {
      if (index == 0) context.go('/sp/home');
      if (index == 1) {
        if (subtype == RoleSubtype.diagnostic) {
          context.push('/sp/diagnostic-referrals/new');
        } else {
          context.go('/sp/leads');
        }
      }
      if (index == 2) context.go('/sp/profile');
    }
  }

  @override
  Widget build(BuildContext context) {
    final subtype = ref.watch(selectedSubtypeProvider);
    final screenWidth = MediaQuery.sizeOf(context).width;
    final isCompact = screenWidth < 360;

    final leadsLabel = switch (subtype) {
      RoleSubtype.hospital => 'Requests',
      RoleSubtype.diagnostic => 'Tests',
      _ => 'Leads',
    };

    final leadsIcon = switch (subtype) {
      RoleSubtype.hospital => Icons.assignment_outlined,
      RoleSubtype.diagnostic => Icons.biotech_outlined,
      _ => Icons.people_outline,
    };

    final leadsActiveIcon = switch (subtype) {
      RoleSubtype.hospital => Icons.assignment_rounded,
      RoleSubtype.diagnostic => Icons.biotech_rounded,
      _ => Icons.people_alt,
    };

    final destinations = <NavigationDestination>[
      const NavigationDestination(
        icon: Icon(Icons.maps_home_work_outlined, size: 22),
        selectedIcon: Icon(Icons.maps_home_work, size: 22),
        label: 'Home',
      ),
      if (widget.role == AppRole.gp)
        const NavigationDestination(
          icon: Icon(Icons.assignment_outlined, size: 22),
          selectedIcon: Icon(Icons.assignment_rounded, size: 22),
          label: 'Referrals',
        )
      else ...[
        NavigationDestination(
          icon: Icon(leadsIcon, size: 22),
          selectedIcon: Icon(leadsActiveIcon, size: 22),
          label: leadsLabel,
        ),
      ],
      const NavigationDestination(
        icon: Icon(Icons.account_circle_outlined, size: 22),
        selectedIcon: Icon(Icons.account_circle, size: 22),
        label: 'Profile',
      ),
    ];

    return SafeArea(
      top: false,
      child: Container(
        margin: EdgeInsets.fromLTRB(isCompact ? 8 : 12, 0, isCompact ? 8 : 12, 8),
        padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 6),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(24),
          boxShadow: AppStyles.navShadow,
          border: Border.all(color: AppColors.border),
        ),
        child: NavigationBar(
          selectedIndex: widget.currentIndex,
          onDestinationSelected: _onTap,
          backgroundColor: Colors.transparent,
          indicatorColor: AppColors.primaryBlue.withAlpha(20),
          elevation: 0,
          height: 56,
          destinations: destinations,
          labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
        ),
      ),
    );
  }
}