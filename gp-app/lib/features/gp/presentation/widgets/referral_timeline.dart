import 'package:flutter/material.dart';
import 'package:gp_app/ui/styles.dart';

class ReferralTimeline extends StatelessWidget {
  final String currentStatus;

  const ReferralTimeline({super.key, required this.currentStatus});

  @override
  Widget build(BuildContext context) {
    final steps = _getStepsByStatus(currentStatus);

    return TweenAnimationBuilder<double>(
      key: ValueKey(currentStatus),
      tween: Tween(begin: 0, end: 1),
      duration: const Duration(milliseconds: 360),
      curve: Curves.easeOutCubic,
      builder: (context, t, _) {
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            for (int i = 0; i < steps.length; i++)
              _buildAnimatedStep(
                progress: t,
                index: i,
                title: steps[i]['title'] as String,
                isCompleted: steps[i]['isCompleted'] as bool,
                isLast: i == steps.length - 1,
                isCurrent: steps[i]['isCurrent'] as bool,
              ),
          ],
        );
      },
    );
  }

  List<Map<String, dynamic>> _getStepsByStatus(String status) {
    final s = status.toLowerCase();
    
    final allSteps = [
      {'title': 'Referral Submitted', 'isCompleted': true, 'isCurrent': false},
      {'title': 'Assigned to Specialist', 'isCompleted': false, 'isCurrent': false},
      {'title': 'Specialist Accepted', 'isCompleted': false, 'isCurrent': false},
      {'title': 'Consultation Completed', 'isCompleted': false, 'isCurrent': false},
    ];

    if (s == 'pending') {
      allSteps[1]['isCurrent'] = true;
    } else if (s == 'accepted') {
      allSteps[1]['isCompleted'] = true;
      allSteps[2]['isCompleted'] = true;
      allSteps[2]['isCurrent'] = true;
    } else if (s == 'consulted') {
      allSteps[1]['isCompleted'] = true;
      allSteps[2]['isCompleted'] = true;
      allSteps[3]['isCompleted'] = true;
      allSteps[3]['isCurrent'] = true;
    }

    return allSteps;
  }

  Widget _buildAnimatedStep({
    required double progress,
    required int index,
    required String title,
    required bool isCompleted,
    required bool isLast,
    required bool isCurrent,
  }) {
    final delay = index * 0.08;
    final p = ((progress - delay) / (1 - delay)).clamp(0.0, 1.0);

    return Opacity(
      opacity: p,
      child: Transform.translate(
        offset: Offset(0, 8 * (1 - p)),
        child: _buildStep(title, isCompleted, isLast, isCurrent),
      ),
    );
  }

  Widget _buildStep(String title, bool isCompleted, bool isLast, bool isCurrent) {
    final color = isCompleted || isCurrent ? AppColors.secondaryTeal : AppColors.textMuted;
    
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Column(
          children: [
            Container(
              width: 24,
              height: 24,
              decoration: BoxDecoration(
                color: isCompleted ? AppColors.secondaryTeal.withAlpha(22) : Colors.transparent,
                shape: BoxShape.circle,
                border: Border.all(color: color, width: 2),
              ),
              child: isCompleted 
                ? Icon(Icons.check_rounded, size: 14, color: AppColors.secondaryTeal)
                : (isCurrent ? Center(child: Container(width: 8, height: 8, decoration: BoxDecoration(color: AppColors.secondaryTeal, shape: BoxShape.circle))) : null),
            ),
            if (!isLast)
              Container(
                width: 1,
                height: 40,
                color: color.withAlpha(70),
              ),
          ],
        ),
        const SizedBox(width: 16),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title, 
                style: AppStyles.bodyLarge.copyWith(
                  fontWeight: (isCompleted || isCurrent) ? FontWeight.w700 : FontWeight.w400,
                  color: (isCompleted || isCurrent) ? AppColors.textPrimary : AppColors.textSecondary,
                )
              ),
              const SizedBox(height: 4),
              Text(
                isCompleted ? 'Completed' : (isCurrent ? 'In Progress' : 'Pending'),
                style: AppStyles.caption,
              ),
            ],
          ),
        ),
      ],
    );
  }
}
