import 'package:flutter/material.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/primary_button.dart';

class LoadingState extends StatelessWidget {
  final String message;
  const LoadingState({super.key, this.message = 'Loading…'});
  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const CircularProgressIndicator(color: AppColors.primaryBlue),
          const SizedBox(height: 10),
          Text(message, style: AppStyles.bodySmall.copyWith(color: AppColors.textSecondary)),
        ],
      ),
    );
  }
}

class EmptyState extends StatelessWidget {
  final String message;
  final String? subtitle;
  final IconData icon;

  const EmptyState({
    super.key,
    this.message = 'Nothing here yet',
    this.subtitle,
    this.icon = Icons.inbox_outlined,
  });
  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 54,
            height: 54,
            decoration: BoxDecoration(
              color: AppColors.secondaryTeal.withAlpha(18),
              borderRadius: BorderRadius.circular(18),
            ),
            child: Icon(icon, color: AppColors.secondaryTeal.withAlpha(220)),
          ),
          const SizedBox(height: 14),
          Text(message, style: AppStyles.heading2, textAlign: TextAlign.center),
          if (subtitle != null && subtitle!.trim().isNotEmpty) ...[
            const SizedBox(height: 6),
            Text(
              subtitle!.trim(),
              style: AppStyles.bodySmall.copyWith(color: AppColors.textSecondary),
              textAlign: TextAlign.center,
            ),
          ],
        ],
      ),
    );
  }
}

class ErrorState extends StatelessWidget {
  final String message;
  final String title;
  final String? subtitle;
  final VoidCallback? onRetry;
  final String retryLabel;

  const ErrorState({
    super.key,
    required this.message,
    this.title = 'We couldn’t load this right now.',
    this.subtitle = 'Please try again in a moment.',
    this.onRetry,
    this.retryLabel = 'Retry',
  });
  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(title, style: AppStyles.heading2, textAlign: TextAlign.center),
          const SizedBox(height: 6),
          if (subtitle != null && subtitle!.trim().isNotEmpty)
            Text(
              subtitle!.trim(),
              style: AppStyles.bodySmall.copyWith(color: AppColors.textSecondary),
              textAlign: TextAlign.center,
            ),
          if (message.trim().isNotEmpty) ...[
            const SizedBox(height: 8),
            Text(
              message.trim(),
              style: AppStyles.caption.copyWith(color: AppColors.textMuted),
              textAlign: TextAlign.center,
            ),
          ],
          if (onRetry != null) ...[
            const SizedBox(height: 16),
            SizedBox(
              width: 220,
              child: PrimaryButton(label: retryLabel, onPressed: onRetry),
            ),
          ],
        ],
      ),
    );
  }
}
