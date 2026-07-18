import 'package:flutter/material.dart';
import 'package:gp_app/ui/styles.dart';

class LabeledPasswordField extends StatefulWidget {
  final TextEditingController controller;
  final String label;
  final String? hintText;
  final String? Function(String?)? validator;
  
  const LabeledPasswordField({
    super.key,
    required this.controller,
    required this.label,
    this.hintText,
    this.validator,
  });

  @override
  State<LabeledPasswordField> createState() => _LabeledPasswordFieldState();
}

class _LabeledPasswordFieldState extends State<LabeledPasswordField> {
  bool _obscure = true;
  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(widget.label, style: AppStyles.labelText),
        const SizedBox(height: 8),
        TextFormField(
          controller: widget.controller,
          obscureText: _obscure,
          style: AppStyles.bodyLarge,
          decoration: InputDecoration(
            hintText: widget.hintText,
            hintStyle: AppStyles.bodyLarge.copyWith(color: AppColors.textGrey),
            filled: true,
            fillColor: AppColors.inputBackground,
            contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
            border: OutlineInputBorder(
              borderRadius: AppStyles.radiusInput,
              borderSide: const BorderSide(color: AppColors.borderLight, width: 1.0),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: AppStyles.radiusInput,
              borderSide: const BorderSide(color: AppColors.borderLight, width: 1.0),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: AppStyles.radiusInput,
              borderSide: const BorderSide(color: AppColors.primaryBlue, width: 1.5),
            ),
            errorBorder: OutlineInputBorder(
              borderRadius: AppStyles.radiusInput,
              borderSide: const BorderSide(color: AppColors.statusError, width: 1.0),
            ),
            suffixIcon: IconButton(
              icon: Icon(
                _obscure ? Icons.visibility_off_outlined : Icons.visibility_outlined,
                color: AppColors.textGrey,
              ),
              onPressed: () => setState(() => _obscure = !_obscure),
            ),
          ),
          validator: widget.validator ?? (v) => v == null || v.isEmpty ? 'Required' : null,
        ),
      ],
    );
  }
}
