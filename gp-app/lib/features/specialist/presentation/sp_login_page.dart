import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/app/role.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/labeled_text_field.dart';
import 'package:gp_app/ui/widgets/labeled_password_field.dart';
import 'package:gp_app/ui/widgets/primary_button.dart';
import 'package:gp_app/features/auth/state/auth_state.dart';
import 'package:gp_app/ui/widgets/app_hero_header.dart';
import 'package:gp_app/ui/widgets/app_card.dart';

class SpLoginPage extends ConsumerStatefulWidget {
  const SpLoginPage({super.key});
  @override
  ConsumerState<SpLoginPage> createState() => _SpLoginPageState();
}

class _SpLoginPageState extends ConsumerState<SpLoginPage> {
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
      setState(() => _error = 'Invalid credentials');
      return;
    }
    final s = ref.read(authStateProvider);
    if (s is Authenticated && s.user.role != 'specialist') {
      await ref.read(authStateProvider.notifier).logout();
      setState(() => _error = 'This account is not a Specialist user');
    }
  }

  @override
  Widget build(BuildContext context) {
    ref.listen<AppRole?>(selectedRoleProvider, (_, next) {
      if (next != AppRole.specialist) {
        setState(() => _error = 'Please select Specialist role to continue');
      }
    });
    final state = ref.watch(authStateProvider);
    final loading = state is Loading;
    final subtype = ref.watch(selectedSubtypeProvider);
    
    final title = switch (subtype) {
      RoleSubtype.hospital => 'Hospital Login',
      RoleSubtype.diagnostic => 'Diagnostic Login',
      _ => 'Specialist Login',
    };

    return Scaffold(
      backgroundColor: AppColors.background,
      body: Column(
        children: [
          AppHeroHeader(
            leading: IconButton(
              onPressed: () => context.pop(),
              icon: const Icon(Icons.arrow_back_rounded, color: AppColors.textOnDark),
            ),
            title: title,
            subtitle: 'Access your account',
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
                        Container(
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: AppColors.inputBackground,
                            borderRadius: BorderRadius.circular(20),
                            border: Border.all(color: AppColors.borderLight),
                            boxShadow: [
                              BoxShadow(
                                color: AppColors.primaryBlue.withAlpha(20),
                                blurRadius: 16,
                                offset: const Offset(0, 6),
                              ),
                            ],
                          ),
                          child: ClipRRect(
                            borderRadius: BorderRadius.circular(14),
                            child: Image.asset('assets/images/app_logo.png', height: 66),
                          ),
                        ),
                        const SizedBox(height: 24),
                        Text('Welcome Back', style: AppStyles.heading1),
                        const SizedBox(height: 8),
                        Text(
                          'Log in to your $title account', 
                          style: AppStyles.bodyMedium.copyWith(color: AppColors.textSecondary),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 32),
                        LabeledTextField(
                          controller: _mobileCtrl,
                          label: 'Mobile Number',
                          hintText: 'Enter your mobile number',
                          keyboardType: TextInputType.phone,
                        ),
                        const SizedBox(height: 16),
                        LabeledPasswordField(controller: _passwordCtrl, label: 'Password', hintText: 'Enter Password'),
                        const SizedBox(height: 16),
                        if (_error != null) ...[
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                            decoration: BoxDecoration(
                              color: AppColors.statusError.withAlpha(26),
                              borderRadius: AppStyles.radiusInput,
                            ),
                            child: Text(_error!, style: AppStyles.bodySmall.copyWith(color: AppColors.statusError)),
                          ),
                          const SizedBox(height: 16),
                        ],
                        PrimaryButton(label: loading ? 'Logging in...' : 'Login', onPressed: loading ? null : _submit),
                        const SizedBox(height: 8),
                        TextButton(
                          onPressed: loading ? null : () => context.push('/sp/otp-login'),
                          child: Text(
                            'Login with OTP',
                            style: AppStyles.bodySmall.copyWith(color: AppColors.primaryBlue),
                          ),
                        ),
                        const SizedBox(height: 24),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text('Don\'t have an account? ', style: AppStyles.bodyMedium),
                            TextButton(
                              onPressed: () => context.push('/sp/register'),
                              child: Text('Register', style: AppStyles.bodyMedium.copyWith(color: AppColors.primaryBlue, fontWeight: FontWeight.bold)),
                            ),
                          ],
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
