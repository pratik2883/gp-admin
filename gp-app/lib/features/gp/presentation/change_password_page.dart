import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_bar_gradient.dart';
import 'package:gp_app/ui/widgets/labeled_text_field.dart';
import 'package:gp_app/ui/widgets/primary_button.dart';

class ChangePasswordPage extends ConsumerStatefulWidget {
  const ChangePasswordPage({super.key});

  @override
  ConsumerState<ChangePasswordPage> createState() => _ChangePasswordPageState();
}

class _ChangePasswordPageState extends ConsumerState<ChangePasswordPage> {
  final _formKey = GlobalKey<FormState>();
  final _currentPasswordController = TextEditingController();
  final _newPasswordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();
  
  bool _isLoading = false;

  @override
  void dispose() {
    _currentPasswordController.dispose();
    _newPasswordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    if (_newPasswordController.text != _confirmPasswordController.text) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('New passwords do not match')),
      );
      return;
    }

    setState(() => _isLoading = true);
    try {
      await ref.read(gpRepositoryProvider).changePassword(
        currentPassword: _currentPasswordController.text,
        newPassword: _newPasswordController.text,
        confirmPassword: _confirmPasswordController.text,
      );
      
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Password changed successfully')),
        );
        Navigator.pop(context);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: ${e.toString()}')),
        );
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Change Password', style: TextStyle(fontWeight: FontWeight.w600)),
        centerTitle: true,
        flexibleSpace: Container(decoration: appBarGradient()),
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: AppColors.cardWhite,
                  borderRadius: AppStyles.radiusCard,
                  boxShadow: AppStyles.cardShadow,
                ),
                child: Column(
                  children: [
                    LabeledTextField(
                      controller: _currentPasswordController,
                      label: 'Current Password',
                      hintText: 'Enter your current password',
                      obscure: true,
                      prefixIcon: const Icon(Icons.lock_outline_rounded, color: AppColors.primaryBlue, size: 20),
                    ),
                    const SizedBox(height: 24),
                    LabeledTextField(
                      controller: _newPasswordController,
                      label: 'New Password',
                      hintText: 'Minimum 8 characters',
                      obscure: true,
                      prefixIcon: const Icon(Icons.vpn_key_outlined, color: AppColors.primaryBlue, size: 20),
                      validator: (v) {
                        if (v == null || v.isEmpty) return 'Please enter a new password';
                        if (v.length < 8) return 'Password must be at least 8 characters';
                        return null;
                      },
                    ),
                    const SizedBox(height: 24),
                    LabeledTextField(
                      controller: _confirmPasswordController,
                      label: 'Confirm New Password',
                      hintText: 'Re-enter new password',
                      obscure: true,
                      prefixIcon: const Icon(Icons.check_circle_outline_rounded, color: AppColors.primaryBlue, size: 20),
                      validator: (v) {
                        if (v != _newPasswordController.text) return 'Passwords do not match';
                        return null;
                      },
                    ),
                    const SizedBox(height: 40),
                    PrimaryButton(
                      label: _isLoading ? 'Updating...' : 'Update Password',
                      onPressed: _isLoading ? null : _submit,
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 8),
                child: Text(
                  'Make sure your new password is secure and not used elsewhere.',
                  style: AppStyles.bodySmall.copyWith(color: AppColors.textGrey),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
