import 'package:flutter/material.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_pressable.dart';

class QuickActionTile extends StatelessWidget {
  final IconData icon;
  final String label;
  final String? subtitle;
  final VoidCallback? onTap;
  final Color? accent;

  const QuickActionTile({
    super.key,
    required this.icon,
    required this.label,
    this.subtitle,
    this.onTap,
    this.accent,
  });

  @override
  Widget build(BuildContext context) {
    final accentColor = accent ?? AppColors.secondaryTeal;
    final disabled = onTap == null;
    final screenWidth = MediaQuery.sizeOf(context).width;
    final isCompact = screenWidth < 360;

    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: AppStyles.radiusCard,
        border: Border.all(color: AppColors.border),
        boxShadow: AppStyles.cardShadow,
      ),
      child: AppPressable(
        onTap: onTap,
        enabled: !disabled,
        borderRadius: AppStyles.radiusCard,
        pressedScale: 0.99,
        pressedOpacity: 0.96,
        child: Padding(
          padding: EdgeInsets.fromLTRB(isCompact ? 8 : 12, isCompact ? 10 : 14, isCompact ? 8 : 12, isCompact ? 10 : 14),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: isCompact ? 36 : 44,
                height: isCompact ? 36 : 44,
                decoration: BoxDecoration(
                  color: accentColor.withAlpha(22),
                  borderRadius: BorderRadius.circular(isCompact ? 12 : 16),
                ),
                child: Icon(icon, color: disabled ? AppColors.textMuted : accentColor, size: isCompact ? 18 : 22),
              ),
              SizedBox(height: isCompact ? 7 : 10),
              Text(
                label,
                style: AppStyles.bodySmall.copyWith(
                  color: disabled ? AppColors.textMuted : AppColors.textPrimary,
                  fontWeight: FontWeight.w800,
                  fontSize: isCompact ? 10 : null,
                ),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                textAlign: TextAlign.center,
              ),
              if (subtitle != null) ...[
                const SizedBox(height: 4),
                Text(
                  subtitle!,
                  style: AppStyles.caption,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  textAlign: TextAlign.center,
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
