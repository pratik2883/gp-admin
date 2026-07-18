import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/app/role.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/labeled_text_field.dart';
import 'package:gp_app/ui/widgets/labeled_password_field.dart';
import 'package:gp_app/ui/widgets/primary_button.dart';
import 'package:gp_app/ui/widgets/app_bar_gradient.dart';
import 'package:gp_app/features/auth/state/auth_state.dart';

class GpLoginPage extends ConsumerStatefulWidget {
  const GpLoginPage({super.key});
  @override
  ConsumerState<GpLoginPage> createState() => _GpLoginPageState();
}

class _GpLoginPageState extends ConsumerState<GpLoginPage> {
  final _formKey = GlobalKey<FormState>();
  final _mobileCtrl = TextEditingController();
  final _passwordCtrl = TextEditingController();
  String? _error;
  ProviderSubscription<AppRole?>? _roleSub;

  @override
  void initState() {
    super.initState();
    _roleSub = ref.listenManual<AppRole?>(selectedRoleProvider, (_, next) {
      if (next != AppRole.gp) {
        setState(() => _error = 'Please select GP role to continue');
      }
    });
  }

  @override
  void dispose() {
    _roleSub?.close();
    _mobileCtrl.dispose();
    _passwordCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    final err = await ref
        .read(authStateProvider.notifier)
        .login(_mobileCtrl.text.trim(), _passwordCtrl.text);

    if (!mounted) return;

    if (err != null) {
      setState(() => _error = 'Invalid credentials');
      return;
    }

    final s = ref.read(authStateProvider);
    if (s is Authenticated && s.user.role != 'gp') {
      await ref.read(authStateProvider.notifier).logout();
      if (!mounted) return;
      setState(() => _error = 'This account is not a GP user');
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(authStateProvider);
    final loading = state is Loading;
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('GP Login'),
        flexibleSpace: Container(decoration: appBarGradient()),
        elevation: 0,
        centerTitle: true,
      ),
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 420),
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: AppColors.cardWhite,
                  borderRadius: AppStyles.radiusCard,
                  boxShadow: AppStyles.cardShadow,
                ),
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
                          child: Image.asset('assets/images/app_logo.png',
                              height: 72),
                        ),
                      ),
                      const SizedBox(height: 18),
                      Text('Welcome Back', style: AppStyles.heading1),
                      const SizedBox(height: 8),
                      Text('Log in to your GP account',
                          style: AppStyles.bodyMedium),
                      const SizedBox(height: 32),
                      LabeledTextField(
                        controller: _mobileCtrl,
                        label: 'Mobile Number',
                        hintText: '+91',
                        keyboardType: TextInputType.phone,
                      ),
                      const SizedBox(height: 16),
                      LabeledPasswordField(
                          controller: _passwordCtrl,
                          label: 'Password',
                          hintText: 'Enter Password'),
                      const SizedBox(height: 16),
                      if (_error != null) ...[
                        Text(_error!,
                            style: AppStyles.bodyMedium
                                .copyWith(color: AppColors.statusError)),
                        const SizedBox(height: 16),
                      ],
                      PrimaryButton(
                          label: loading ? 'Logging in...' : 'Login',
                          onPressed: loading ? null : _submit),
                      const SizedBox(height: 8),
                      TextButton(
                        onPressed: loading
                            ? null
                            : () => context.push('/gp/otp-login'),
                        child: Text(
                          'Login with OTP',
                          style: AppStyles.bodySmall
                              .copyWith(color: AppColors.primaryBlue),
                        ),
                      ),
                      const SizedBox(height: 16),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text('Don\'t have an account? ',
                              style: AppStyles.bodyMedium),
                          TextButton(
                            onPressed: () => context.push('/gp/register'),
                            child: Text('Register',
                                style: AppStyles.bodyMedium.copyWith(
                                    color: AppColors.primaryBlue,
                                    fontWeight: FontWeight.bold)),
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
      ),
    );
  }
}
