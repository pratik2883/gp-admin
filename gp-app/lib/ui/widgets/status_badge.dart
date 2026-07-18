import 'package:flutter/material.dart';
import 'package:gp_app/ui/styles.dart';

class StatusBadge extends StatelessWidget {
  final String status;
  
  const StatusBadge({super.key, required this.status});

  Color _getStatusColor() {
    final s = status.toLowerCase();
    if (s.contains('new') || s.contains('sent') || s.contains('pending')) {
      return AppColors.statusPending;
    }
    if (s.contains('accepted') || s.contains('consulted')) {
      return AppColors.statusAccepted;
    }
    if (s.contains('closed') || s.contains('rejected')) {
      return AppColors.statusError;
    }
    return AppColors.textGrey;
  }

  @override
  Widget build(BuildContext context) {
    final color = _getStatusColor();
    
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 8,
          height: 8,
          decoration: BoxDecoration(
            color: color,
            shape: BoxShape.circle,
          ),
        ),
        const SizedBox(width: 6),
        Text(
          status,
          style: AppStyles.bodyMedium.copyWith(
            color: AppColors.textDark,
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }
}
