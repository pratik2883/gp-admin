import 'package:flutter/material.dart';
import 'package:gp_app/ui/styles.dart';

class StatSummaryCard extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;
  final Color accent;
  final VoidCallback? onTap;

  const StatSummaryCard({
    super.key,
    required this.label,
    required this.value,
    required this.icon,
    required this.accent,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.sizeOf(context).width;
    final isCompact = screenWidth < 360;

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: AppStyles.radiusCard,
        child: Container(
          padding: EdgeInsets.all(isCompact ? 10 : 14),
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: AppStyles.radiusCard,
            border: Border.all(color: AppColors.border),
            boxShadow: AppStyles.cardShadow,
          ),
          child: Row(
            children: [
              Container(
                width: isCompact ? 36 : 44,
                height: isCompact ? 36 : 44,
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [accent.withAlpha(220), accent.withAlpha(130)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(isCompact ? 11 : 14),
                ),
                child: Icon(icon, color: AppColors.textOnDark, size: isCompact ? 18 : 22),
              ),
              SizedBox(width: isCompact ? 8 : 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      label,
                      style: AppStyles.caption,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      value,
                      style: AppStyles.heading2.copyWith(fontSize: isCompact ? 14 : null),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}