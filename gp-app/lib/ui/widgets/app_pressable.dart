import 'package:flutter/material.dart';
import 'package:gp_app/ui/styles.dart';

class AppPressable extends StatefulWidget {
  final VoidCallback? onTap;
  final bool enabled;
  final BorderRadius? borderRadius;
  final double pressedScale;
  final double pressedOpacity;
  final Duration duration;
  final Curve curve;
  final Widget child;

  const AppPressable({
    super.key,
    required this.child,
    this.onTap,
    this.enabled = true,
    this.borderRadius,
    this.pressedScale = 0.985,
    this.pressedOpacity = 0.94,
    this.duration = const Duration(milliseconds: 120),
    this.curve = Curves.easeOut,
  });

  @override
  State<AppPressable> createState() => _AppPressableState();
}

class _AppPressableState extends State<AppPressable> {
  bool _pressed = false;

  void _setPressed(bool v) {
    if (!mounted) return;
    if (_pressed == v) return;
    setState(() => _pressed = v);
  }

  @override
  Widget build(BuildContext context) {
    final canTap = widget.enabled && widget.onTap != null;
    final radius = widget.borderRadius ?? AppStyles.radiusCard;

    return AnimatedScale(
      scale: canTap && _pressed ? widget.pressedScale : 1,
      duration: widget.duration,
      curve: widget.curve,
      child: AnimatedOpacity(
        opacity: canTap && _pressed ? widget.pressedOpacity : 1,
        duration: widget.duration,
        curve: widget.curve,
        child: Material(
          color: Colors.transparent,
          child: InkWell(
            onTap: canTap ? widget.onTap : null,
            onTapDown: canTap ? (_) => _setPressed(true) : null,
            onTapCancel: canTap ? () => _setPressed(false) : null,
            onTapUp: canTap ? (_) => _setPressed(false) : null,
            borderRadius: radius,
            child: widget.child,
          ),
        ),
      ),
    );
  }
}
