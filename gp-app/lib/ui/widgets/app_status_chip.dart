import 'package:flutter/material.dart';
import 'package:gp_app/ui/styles.dart';

class AppStatusChip extends StatelessWidget {
  final String status;

  const AppStatusChip({
    super.key,
    required this.status,
  });

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.sizeOf(context).width;
    final isCompact = screenWidth < 360;

    final s = status.trim().toLowerCase();
    final (bg, fg, label) = switch (s) {
      'sent' || 'pending' => (AppColors.warningYellow.withAlpha(28), AppColors.warningYellow, 'Pending'),
      'open' => (AppColors.warningYellow.withAlpha(28), AppColors.warningYellow, 'Open'),
      'in_progress' => (AppColors.primaryBlue.withAlpha(22), AppColors.primaryBlue, 'In Progress'),
      'accepted' => (AppColors.secondaryTeal.withAlpha(26), AppColors.secondaryTeal, 'Accepted'),
      'consulted' => (AppColors.primaryBlue.withAlpha(22), AppColors.primaryBlue, 'Consulted'),
      'resolved' => (AppColors.secondaryTeal.withAlpha(24), AppColors.secondaryTeal, 'Resolved'),
      'closed' || 'completed' => (AppColors.successGreen.withAlpha(26), AppColors.successGreen, 'Closed'),
      _ => (AppColors.surfaceMuted, AppColors.textSecondary, status),
    };

    return AnimatedContainer(
      padding: EdgeInsets.symmetric(horizontal: isCompact ? 7 : 10, vertical: isCompact ? 4 : 6),
      duration: const Duration(milliseconds: 180),
      curve: Curves.easeOut,
      decoration: BoxDecoration(
        color: bg,
        borderRadius: AppStyles.radiusPill,
        border: Border.all(color: fg.withAlpha(60)),
      ),
      child: AnimatedDefaultTextStyle(
        duration: const Duration(milliseconds: 180),
        curve: Curves.easeOut,
        style: AppStyles.chipText.copyWith(fontSize: isCompact ? 9 : null, color: fg),
        child: Text(label),
      ),
    );
  }
}
