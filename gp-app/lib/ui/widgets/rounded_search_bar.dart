import 'package:flutter/material.dart';
import 'package:gp_app/ui/styles.dart';

class RoundedSearchBar extends StatelessWidget {
  final String hintText;
  final VoidCallback? onTap;
  final ValueChanged<String>? onChanged;
  final TextEditingController? controller;

  const RoundedSearchBar({
    super.key,
    this.hintText = 'Search',
    this.onTap,
    this.onChanged,
    this.controller,
  });

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.sizeOf(context).width;
    final isCompact = screenWidth < 360;

    return Material(
      color: Colors.white.withAlpha(230),
      borderRadius: AppStyles.radiusPill,
      child: InkWell(
        borderRadius: AppStyles.radiusPill,
        onTap: onTap,
        child: Padding(
          padding: EdgeInsets.symmetric(horizontal: isCompact ? 10 : 14, vertical: isCompact ? 9 : 12),
          child: Row(
            children: [
              Icon(Icons.search, color: AppColors.primaryBlue.withAlpha(220), size: isCompact ? 16 : 20),
              SizedBox(width: isCompact ? 7 : 10),
              Expanded(
                child: TextField(
                  controller: controller,
                  onChanged: onChanged,
                  onTap: onTap,
                  readOnly: onTap != null,
                  style: AppStyles.bodySmall,
                  decoration: InputDecoration(
                    hintText: hintText,
                    hintStyle: AppStyles.bodySmall.copyWith(color: AppColors.textSecondary),
                    border: InputBorder.none,
                    isDense: true,
                    contentPadding: EdgeInsets.zero,
                  ),
                ),
              ),
              Icon(Icons.tune_rounded, color: AppColors.primaryBlue.withAlpha(200), size: isCompact ? 14 : 18),
            ],
          ),
        ),
      ),
    );
  }
}

