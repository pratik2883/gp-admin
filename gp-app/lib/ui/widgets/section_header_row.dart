import 'package:flutter/material.dart';
import 'package:gp_app/ui/styles.dart';

class SectionHeaderRow extends StatelessWidget {
  final String title;
  final String actionLabel;
  final VoidCallback? onAction;

  const SectionHeaderRow({
    super.key,
    required this.title,
    this.actionLabel = 'See more',
    this.onAction,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(title, style: AppStyles.heading2),
        if (onAction != null)
          TextButton(
            onPressed: onAction,
            style: TextButton.styleFrom(
              foregroundColor: AppColors.secondaryTeal,
              textStyle: AppStyles.bodySmall.copyWith(fontWeight: FontWeight.w700),
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
            ),
            child: Text(actionLabel),
          ),
      ],
    );
  }
}

