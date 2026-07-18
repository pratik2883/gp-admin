import 'package:flutter/material.dart';
import 'package:gp_app/ui/styles.dart';

class LabeledTextField extends StatelessWidget {
  final TextEditingController controller;
  final String label;
  final String? hintText;
  final bool obscure;
  final TextInputType? keyboardType;
  final String? Function(String?)? validator;
  final Widget? prefixIcon;
  final Widget? suffixIcon;
  final int maxLines;
  
  const LabeledTextField({
    super.key,
    required this.controller,
    required this.label,
    this.hintText,
    this.obscure = false,
    this.keyboardType,
    this.validator,
    this.prefixIcon,
    this.suffixIcon,
    this.maxLines = 1,
  });

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      controller: controller,
      obscureText: obscure,
      keyboardType: keyboardType,
      style: AppStyles.bodyMedium.copyWith(color: AppColors.textPrimary),
      maxLines: maxLines,
      decoration: InputDecoration(
        labelText: label,
        hintText: hintText,
        prefixIcon: prefixIcon,
        suffixIcon: suffixIcon,
      ),
      validator: validator ?? (v) => v == null || v.trim().isEmpty ? 'Required' : null,
    );
  }
}
