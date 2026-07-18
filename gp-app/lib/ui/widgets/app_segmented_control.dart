import 'package:flutter/material.dart';
import 'package:gp_app/ui/styles.dart';

class AppSegmentOption<T> {
  final T value;
  final String label;
  final IconData? icon;

  const AppSegmentOption({
    required this.value,
    required this.label,
    this.icon,
  });
}

class AppSegmentedControl<T> extends StatelessWidget {
  final T value;
  final List<AppSegmentOption<T>> options;
  final ValueChanged<T> onChanged;
  final bool expand;

  const AppSegmentedControl({
    super.key,
    required this.value,
    required this.options,
    required this.onChanged,
    this.expand = true,
  });

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.sizeOf(context).width;
    final isCompact = screenWidth < 360;
    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: Colors.white.withAlpha(230),
        borderRadius: AppStyles.radiusPill,
        border: Border.all(color: AppColors.border),
        boxShadow: AppStyles.cardShadow,
      ),
      child: Row(
        mainAxisSize: expand ? MainAxisSize.max : MainAxisSize.min,
        children: [
          for (final option in options) ...[
            if (option != options.first) SizedBox(width: isCompact ? 4 : 6),
            _Segment(
              selected: option.value == value,
              label: option.label,
              icon: option.icon,
              onTap: () => onChanged(option.value),
              expand: expand,
              isCompact: isCompact,
            ),
          ],
        ],
      ),
    );
  }
}

class _Segment extends StatelessWidget {
  final bool selected;
  final String label;
  final IconData? icon;
  final VoidCallback onTap;
  final bool expand;
  final bool isCompact;

  const _Segment({
    required this.selected,
    required this.label,
    required this.onTap,
    required this.expand,
    required this.isCompact,
    this.icon,
  });

  @override
  Widget build(BuildContext context) {
    final content = AnimatedContainer(
      duration: const Duration(milliseconds: 180),
      curve: Curves.easeOut,
      padding: EdgeInsets.symmetric(horizontal: isCompact ? 8 : 12, vertical: isCompact ? 7 : 10),
      decoration: BoxDecoration(
        gradient: selected ? AppColors.primaryGradient : null,
        color: selected ? null : Colors.transparent,
        borderRadius: AppStyles.radiusPill,
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          if (icon != null) ...[
            Icon(icon, size: isCompact ? 15 : 18, color: selected ? AppColors.textOnDark : AppColors.textSecondary),
            const SizedBox(width: 5),
          ],
          Flexible(
            child: Text(
              label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: AppStyles.caption.copyWith(
                fontSize: isCompact ? 9 : null,
                color: selected ? AppColors.textOnDark : AppColors.textSecondary,
                fontWeight: selected ? FontWeight.w800 : FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
    );

    final tappable = Material(
      color: Colors.transparent,
      child: InkWell(
        borderRadius: AppStyles.radiusPill,
        onTap: onTap,
        child: content,
      ),
    );

    if (!expand) return tappable;
    return Expanded(child: tappable);
  }
}

