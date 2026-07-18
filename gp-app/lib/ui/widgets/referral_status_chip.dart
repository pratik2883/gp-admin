import 'package:flutter/material.dart';
import 'package:gp_app/ui/styles.dart';

class ReferralStatusChip extends StatelessWidget {
  final String status;
  
  const ReferralStatusChip({super.key, required this.status});

  Color _getStatusColor() {
    final s = status.toLowerCase();
    if (s.contains('new') || s.contains('sent') || s.contains('pending')) {
      return AppColors.statusPending; // e.g. orange
    }
    if (s.contains('accepted') || s.contains('consulted')) {
      return const Color(0xFF2E7D32); // Deep green for text matching the mockup 'Accepted'
    }
    if (s.contains('closed') || s.contains('rejected') || s.contains('declined')) {
      return AppColors.statusError; // e.g. red
    }
    return AppColors.textGrey;
  }

  Color _getBackgroundColor(Color baseColor) {
    if (status.toLowerCase().contains('accepted') || status.toLowerCase().contains('consulted')) {
      return const Color(0xFFE8F5E9); // Light green bg for accepted
    }
    if (status.toLowerCase().contains('closed') || status.toLowerCase().contains('rejected') || status.toLowerCase().contains('declined')) {
      return const Color(0xFFFFEBEE); // Light red bg
    }
    return baseColor.withOpacity(0.15); // Light orange for pending
  }

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.sizeOf(context).width;
    final isCompact = screenWidth < 360;
    final color = _getStatusColor();
    final bgColor = _getBackgroundColor(color);

    final displayStatus = status.isNotEmpty
        ? '${status[0].toUpperCase()}${status.substring(1).toLowerCase()}'
        : 'Unknown';

    return Container(
      padding: EdgeInsets.symmetric(horizontal: isCompact ? 7 : 10, vertical: isCompact ? 2 : 4),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        displayStatus,
        style: AppStyles.caption.copyWith(
          fontSize: isCompact ? 9 : null,
          color: color,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}
