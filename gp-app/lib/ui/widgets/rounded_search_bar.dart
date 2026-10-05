import 'package:flutter/material.dart';
import 'package:gp_app/ui/styles.dart';

class RoundedSearchBar extends StatefulWidget {
  final String hintText;
  final VoidCallback? onTap;
  final ValueChanged<String>? onChanged;
  final TextEditingController? controller;
  final Widget? suffixIcon;
  final VoidCallback? onSuffixTap;

  const RoundedSearchBar({
    super.key,
    this.hintText = 'Search',
    this.onTap,
    this.onChanged,
    this.controller,
    this.suffixIcon,
    this.onSuffixTap,
  });

  @override
  State<RoundedSearchBar> createState() => _RoundedSearchBarState();
}

class _RoundedSearchBarState extends State<RoundedSearchBar> {
  late TextEditingController _effectiveController;
  bool _isLocalController = false;

  @override
  void initState() {
    super.initState();
    if (widget.controller != null) {
      _effectiveController = widget.controller!;
    } else {
      _effectiveController = TextEditingController();
      _isLocalController = true;
    }
    _effectiveController.addListener(_handleTextChange);
  }

  @override
  void didUpdateWidget(covariant RoundedSearchBar oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.controller != oldWidget.controller) {
      if (_isLocalController) {
        _effectiveController.removeListener(_handleTextChange);
        _effectiveController.dispose();
      }
      if (widget.controller != null) {
        _effectiveController = widget.controller!;
        _isLocalController = false;
      } else {
        _effectiveController = TextEditingController();
        _isLocalController = true;
      }
      _effectiveController.addListener(_handleTextChange);
    }
  }

  @override
  void dispose() {
    _effectiveController.removeListener(_handleTextChange);
    if (_isLocalController) {
      _effectiveController.dispose();
    }
    super.dispose();
  }

  void _handleTextChange() {
    if (mounted) {
      setState(() {});
    }
  }

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.sizeOf(context).width;
    final isCompact = screenWidth < 360;
    final hasText = _effectiveController.text.isNotEmpty;

    return Material(
      color: Colors.white.withAlpha(230),
      borderRadius: AppStyles.radiusPill,
      child: InkWell(
        borderRadius: AppStyles.radiusPill,
        onTap: widget.onTap,
        child: Padding(
          padding: EdgeInsets.symmetric(horizontal: isCompact ? 10 : 14, vertical: isCompact ? 9 : 12),
          child: Row(
            children: [
              Icon(Icons.search, color: AppColors.primaryBlue.withAlpha(220), size: isCompact ? 16 : 20),
              SizedBox(width: isCompact ? 7 : 10),
              Expanded(
                child: TextField(
                  controller: _effectiveController,
                  onChanged: widget.onChanged,
                  onTap: widget.onTap,
                  readOnly: widget.onTap != null,
                  style: AppStyles.bodySmall,
                  decoration: InputDecoration(
                    hintText: widget.hintText,
                    hintStyle: AppStyles.bodySmall.copyWith(color: AppColors.textSecondary),
                    border: InputBorder.none,
                    isDense: true,
                    contentPadding: EdgeInsets.zero,
                  ),
                ),
              ),
              if (widget.suffixIcon != null) ...[
                GestureDetector(
                  onTap: widget.onSuffixTap,
                  child: widget.suffixIcon!,
                ),
              ] else if (hasText && widget.onTap == null) ...[
                GestureDetector(
                  onTap: () {
                    _effectiveController.clear();
                    widget.onChanged?.call('');
                  },
                  child: Icon(
                    Icons.cancel_rounded,
                    color: AppColors.textSecondary,
                    size: isCompact ? 16 : 20,
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
