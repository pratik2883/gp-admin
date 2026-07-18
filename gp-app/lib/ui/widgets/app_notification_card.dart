import 'package:flutter/material.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_pressable.dart';

class AppNotificationCard extends StatelessWidget {
  final String title;
  final String body;
  final String? dateStr;
  final bool isRead;
  final VoidCallback onTap;

  const AppNotificationCard({
    super.key,
    required this.title,
    required this.body,
    this.dateStr,
    required this.isRead,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.sizeOf(context).width;
    final isCompact = screenWidth < 360;

    final bgColor = isRead ? AppColors.cardWhite : AppColors.primaryBlue.withAlpha(13);
    final shadow = isRead ? AppStyles.cardShadow : null;
    final borderColor = isRead ? AppColors.borderLight : AppColors.primaryBlue.withAlpha(128);
    final borderWidth = isRead ? 0.5 : 1.5;

    return AppPressable(
      onTap: onTap,
      borderRadius: AppStyles.radiusCard,
      pressedScale: 0.99,
      pressedOpacity: 0.96,
      child: AnimatedContainer(
          padding: EdgeInsets.all(isCompact ? 10 : AppSpacing.md),
          duration: const Duration(milliseconds: 180),
          curve: Curves.easeOut,
          decoration: BoxDecoration(
            color: bgColor,
            borderRadius: AppStyles.radiusCard,
            boxShadow: shadow,
            border: Border.all(color: borderColor, width: borderWidth),
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: EdgeInsets.all(isCompact ? 6 : 8),
                decoration: BoxDecoration(
                  color: isRead ? AppColors.background : AppColors.primaryBlue.withAlpha(26),
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  isRead ? Icons.notifications_none_outlined : Icons.notifications_active,
                  color: isRead ? AppColors.textGrey : AppColors.primaryBlue,
                  size: isCompact ? 16 : 20,
                ),
              ),
              SizedBox(width: isCompact ? 10 : AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: AppStyles.heading2.copyWith(
                        fontSize: isCompact ? 14 : 16,
                        color: isRead ? AppColors.textPrimary : AppColors.textDark,
                      ),
                    ),
                    SizedBox(height: isCompact ? 4 : AppSpacing.xs),
                    Text(
                      body,
                      style: AppStyles.bodyMedium.copyWith(
                        fontSize: isCompact ? 11 : null,
                        color: isRead ? AppColors.textSecondary : AppColors.textPrimary,
                      ),
                    ),
                    if (dateStr != null && dateStr!.isNotEmpty) ...[
                      SizedBox(height: isCompact ? 6 : AppSpacing.sm),
                      Text(
                        dateStr!,
                        style: AppStyles.bodySmall.copyWith(
                          color: isRead ? AppColors.textGrey : AppColors.primaryBlue,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              AnimatedScale(
                scale: isRead ? 0.7 : 1,
                duration: const Duration(milliseconds: 180),
                curve: Curves.easeOut,
                child: AnimatedOpacity(
                  opacity: isRead ? 0 : 1,
                  duration: const Duration(milliseconds: 180),
                  curve: Curves.easeOut,
                  child: Container(
                    width: 8,
                    height: 8,
                    margin: EdgeInsets.only(top: isCompact ? 4 : 8),
                    decoration: const BoxDecoration(
                      color: AppColors.primaryBlue,
                      shape: BoxShape.circle,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
    );
  }
}
