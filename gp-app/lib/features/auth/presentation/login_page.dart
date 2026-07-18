import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/features/auth/state/auth_state.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_hero_header.dart';
import 'package:gp_app/ui/widgets/app_card.dart';
import 'package:gp_app/ui/widgets/labeled_text_field.dart';
import 'package:gp_app/ui/widgets/labeled_password_field.dart';
import 'package:gp_app/ui/widgets/primary_button.dart';

class LoginPage extends ConsumerStatefulWidget {
  const LoginPage({super.key});
  @override
  ConsumerState<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends ConsumerState<LoginPage> {
  final _formKey = GlobalKey<FormState>();
  final _mobileCtrl = TextEditingController();
  final _passwordCtrl = TextEditingController();
  String? _error;

  @override
  void dispose() {
    _mobileCtrl.dispose();
    _passwordCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    final err = await ref.read(authStateProvider.notifier).login(_mobileCtrl.text.trim(), _passwordCtrl.text);
    if (err != null) {
      setState(() => _error = err);
    } else {
      setState(() => _error = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(authStateProvider);
    final loading = state is Loading;

    return Scaffold(
      backgroundColor: AppColors.background,
      body: Column(
        children: [
          AppHeroHeader(
            leading: IconButton(
              onPressed: () => Navigator.pop(context),
              icon: const Icon(Icons.arrow_back_rounded, color: AppColors.textOnDark),
            ),
            title: 'GP Portal',
            subtitle: 'Secure Login for Doctors',
          ),
          Expanded(
            child: Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(24),
                child: AppCard(
                  child: Form(
                    key: _formKey,
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Image.asset('assets/images/app_logo.png', height: 70),
                        const SizedBox(height: 24),
                        Text('Welcome Doctor', style: AppStyles.heading1),
                        const SizedBox(height: 8),
                        Text(
                          'Sign in to manage your referrals', 
                          style: AppStyles.bodyMedium.copyWith(color: AppColors.textSecondary),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 32),
                        LabeledTextField(
                          controller: _mobileCtrl,
                          label: 'Mobile Number',
                          hintText: 'Enter registered mobile',
                          keyboardType: TextInputType.phone,
                        ),
                        const SizedBox(height: 16),
                        LabeledPasswordField(
                          controller: _passwordCtrl, 
                          label: 'Password', 
                          hintText: 'Enter your password',
                        ),
                        const SizedBox(height: 16),
                        if (_error != null) ...[
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                            decoration: BoxDecoration(
                              color: AppColors.statusError.withOpacity(0.1),
                              borderRadius: AppStyles.radiusInput,
                            ),
                            child: Text(_error!, style: AppStyles.bodySmall.copyWith(color: AppColors.statusError)),
                          ),
                          const SizedBox(height: 16),
                        ],
                        PrimaryButton(
                          label: loading ? 'Signing in...' : 'Sign In', 
                          onPressed: loading ? null : _submit,
                        ),
                        const SizedBox(height: 24),
                        TextButton(
                          onPressed: () {},
                          child: Text('Forgot Password?', style: AppStyles.bodySmall.copyWith(color: AppColors.primaryBlue)),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
