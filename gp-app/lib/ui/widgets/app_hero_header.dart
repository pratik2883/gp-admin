import 'package:flutter/material.dart';
import 'package:gp_app/ui/styles.dart';

class AppHeroHeader extends StatelessWidget {
  final Widget? leading;
  final Widget? trailing;
  final String title;
  final String? subtitle;
  final Widget? bottom;

  const AppHeroHeader({
    super.key,
    this.leading,
    this.trailing,
    required this.title,
    this.subtitle,
    this.bottom,
  });

  @override
  Widget build(BuildContext context) {
    final safe = MediaQuery.paddingOf(context).top;
    final screenWidth = MediaQuery.sizeOf(context).width;
    final hp = screenWidth < 360 ? 12.0 : (screenWidth > 420 ? 20.0 : 16.0);
    final vp = screenWidth < 360 ? 14.0 : 18.0;

    return Container(
      decoration: const BoxDecoration(
        gradient: AppColors.heroGradient,
        borderRadius: BorderRadius.only(
          bottomLeft: Radius.circular(28),
          bottomRight: Radius.circular(28),
        ),
      ),
      padding: EdgeInsets.only(top: safe + 12, left: hp, right: hp, bottom: vp),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              if (leading != null) ...[
                leading!,
                const SizedBox(width: 8),
              ],
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: AppStyles.heading1.copyWith(color: AppColors.textOnDark),
                    ),
                    if (subtitle != null) ...[
                      const SizedBox(height: 2),
                      Text(
                        subtitle!,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: AppStyles.bodySmall.copyWith(color: AppColors.textOnDark.withAlpha(210)),
                      ),
                    ],
                  ],
                ),
              ),
              if (trailing != null) ...[
                const SizedBox(width: 8),
                trailing!,
              ],
            ],
          ),
          if (bottom != null) ...[
            const SizedBox(height: 12),
            bottom!,
          ],
        ],
      ),
    );
  }
}

