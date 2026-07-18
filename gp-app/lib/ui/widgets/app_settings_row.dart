import 'package:flutter/material.dart';
import 'package:gp_app/ui/styles.dart';

class AppSettingsRow extends StatelessWidget {
  final IconData icon;
  final String title;
  final String? subtitle;
  final VoidCallback? onTap;
  final bool destructive;
  final bool enabled;
  final Widget? trailing;
  final bool showChevron;

  const AppSettingsRow({
    super.key,
    required this.icon,
    required this.title,
    this.subtitle,
    this.onTap,
    this.destructive = false,
    this.enabled = true,
    this.trailing,
    this.showChevron = true,
  });

  @override
  Widget build(BuildContext context) {
    final canTap = enabled && onTap != null;
    final accent = destructive ? AppColors.statusError : AppColors.secondaryTeal;
    final iconBg = destructive ? AppColors.statusError.withAlpha(18) : AppColors.secondaryTeal.withAlpha(18);
    final screenWidth = MediaQuery.sizeOf(context).width;
    final isCompact = screenWidth < 360;

    final content = Padding(
      padding: EdgeInsets.symmetric(horizontal: isCompact ? 10 : 14, vertical: isCompact ? 10 : 12),
      child: Row(
        children: [
          Container(
            width: isCompact ? 34 : 42,
            height: isCompact ? 34 : 42,
            decoration: BoxDecoration(
              color: enabled ? iconBg : AppColors.borderLight,
              borderRadius: BorderRadius.circular(isCompact ? 12 : 16),
              border: Border.all(color: enabled ? iconBg.withAlpha(120) : AppColors.borderLight),
            ),
            child: Icon(icon, color: enabled ? accent : AppColors.textMuted, size: isCompact ? 18 : 22),
          ),
          SizedBox(width: isCompact ? 8 : 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: AppStyles.bodyMedium.copyWith(
                    fontWeight: FontWeight.w800,
                    fontSize: isCompact ? 11 : null,
                    color: enabled
                        ? (destructive ? AppColors.statusError : AppColors.textPrimary)
                        : AppColors.textMuted,
                  ),
                ),
                if (subtitle != null && subtitle!.trim().isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    subtitle!.trim(),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: AppStyles.bodySmall.copyWith(color: enabled ? AppColors.textSecondary : AppColors.textMuted),
                  ),
                ],
              ],
            ),
          ),
          if (trailing != null) trailing!,
          if (trailing == null && showChevron)
            Icon(Icons.chevron_right_rounded, color: enabled ? AppColors.textMuted : AppColors.border, size: isCompact ? 16 : 20),
        ],
      ),
    );

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: canTap ? onTap : null,
        borderRadius: AppStyles.radiusCard,
        child: content,
      ),
    );
  }
}

