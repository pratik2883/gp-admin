import 'package:flutter/material.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_pressable.dart';

class PrimaryButton extends StatelessWidget {
  final String label;
  final VoidCallback? onPressed;
  final Color? color;
  const PrimaryButton({super.key, required this.label, this.onPressed, this.color});

  @override
  Widget build(BuildContext context) {
    final enabled = onPressed != null;
    return SizedBox(
      width: double.infinity,
      height: 52,
      child: Semantics(
        button: true,
        enabled: enabled,
        label: label,
        child: DecoratedBox(
          decoration: BoxDecoration(
            borderRadius: AppStyles.radiusButton,
            color: enabled && color != null ? color : null,
            gradient: enabled && color == null
                ? AppColors.primaryGradient
                : (!enabled ? const LinearGradient(colors: [Color(0xFFE0E0E8), Color(0xFFE0E0E8)]) : null),
            boxShadow: enabled
                ? [
                    BoxShadow(
                      color: AppColors.primaryBlue.withAlpha(77),
                      offset: const Offset(0, 4),
                      blurRadius: 10,
                    )
                  ]
                : null,
          ),
          child: AppPressable(
            onTap: onPressed,
            enabled: enabled,
            borderRadius: AppStyles.radiusButton,
            pressedScale: 0.985,
            pressedOpacity: 0.92,
            child: Center(
              child: Text(label, style: AppStyles.buttonText),
            ),
          ),
        ),
      ),
    );
  }
}
