import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/core/http/dio_client.dart';
import 'package:gp_app/features/auth/state/auth_state.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_card.dart';
import 'package:gp_app/ui/widgets/app_hero_header.dart';
import 'package:gp_app/ui/widgets/labeled_text_field.dart';
import 'package:gp_app/ui/widgets/primary_button.dart';
import 'package:gp_app/features/policy/widgets/terms_consent_checkbox.dart';

class OtpLoginPage extends ConsumerStatefulWidget {
  final String title;
  final String roleHint;

  const OtpLoginPage({
    super.key,
    required this.title,
    required this.roleHint,
  });

  @override
  ConsumerState<OtpLoginPage> createState() => _OtpLoginPageState();
}

class _OtpLoginPageState extends ConsumerState<OtpLoginPage> {
  final _phoneCtrl = TextEditingController();
  final _codeCtrl = TextEditingController();

  String? _verificationId;
  bool _sending = false;
  bool _verifying = false;
  bool _termsAccepted = false;
  String? _error;

  @override
  void dispose() {
    _phoneCtrl.dispose();
    _codeCtrl.dispose();
    super.dispose();
  }

  Future<void> _sendCode() async {
    if (!_termsAccepted) {
      setState(() => _error = 'Please agree to the Terms & Conditions and Privacy Policy to continue.');
      return;
    }
    final phone = _phoneCtrl.text.trim();
    if (phone.isEmpty) {
      setState(() => _error = 'Enter mobile number');
      return;
    }

    setState(() {
      _sending = true;
      _error = null;
    });

    try {
      final repo = ref.read(authRepositoryProvider);
      final verificationId = await repo.sendLoginOtp(mobile: phone);
      if (!mounted) return;
      setState(() => _verificationId = verificationId);
    } catch (e) {
      if (mounted) {
        setState(() => _error = extractApiErrorMessage(e, fallback: 'OTP send failed'));
      }
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  Future<void> _verifyCode() async {
    final verificationId = _verificationId;
    if (verificationId == null || verificationId.isEmpty) {
      setState(() => _error = 'Send OTP first');
      return;
    }

    final code = _codeCtrl.text.trim();
    if (code.isEmpty) {
      setState(() => _error = 'Enter OTP');
      return;
    }

    setState(() {
      _verifying = true;
      _error = null;
    });

    try {
      final err = await ref.read(authStateProvider.notifier).loginWithMessageCentralOtp(
        mobile: _phoneCtrl.text.trim(),
        verificationId: verificationId,
        otpCode: code,
        roleHint: widget.roleHint,
        termsAccepted: _termsAccepted,
      );
      if (!mounted) return;
      if (err != null) {
        setState(() => _error = err);
      }
    } catch (e) {
      if (mounted) {
        setState(() => _error = extractApiErrorMessage(e, fallback: 'OTP verification failed'));
      }
    } finally {
      if (mounted) setState(() => _verifying = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(authStateProvider);
    final busy = _sending || _verifying || state is Loading;

    return Scaffold(
      backgroundColor: AppColors.background,
      body: Column(
        children: [
          AppHeroHeader(
            leading: IconButton(
              onPressed: () => context.pop(),
              icon: const Icon(Icons.arrow_back_rounded, color: AppColors.textOnDark),
            ),
            title: '${widget.title} OTP Login',
            subtitle: 'Verify your mobile number',
          ),
          Expanded(
            child: Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(24),
                child: AppCard(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      AbsorbPointer(
                        absorbing: busy,
                        child: Opacity(
                          opacity: busy ? 0.6 : 1,
                          child: LabeledTextField(
                            controller: _phoneCtrl,
                            label: 'Mobile Number',
                            hintText: '+91XXXXXXXXXX',
                            keyboardType: TextInputType.phone,
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      TermsConsentCheckbox(
                        value: _termsAccepted,
                        onChanged: (v) => setState(() => _termsAccepted = v ?? false),
                      ),
                      const SizedBox(height: 12),
                      PrimaryButton(
                        label: _sending ? 'Sending...' : (_verificationId == null ? 'Send OTP' : 'Resend OTP'),
                        onPressed: busy ? null : _sendCode,
                      ),
                      const SizedBox(height: 24),
                      AbsorbPointer(
                        absorbing: busy,
                        child: Opacity(
                          opacity: busy ? 0.6 : 1,
                          child: LabeledTextField(
                            controller: _codeCtrl,
                            label: 'OTP',
                            hintText: '6-digit OTP',
                            keyboardType: TextInputType.number,
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      PrimaryButton(
                        label: _verifying ? 'Verifying...' : 'Verify & Login',
                        onPressed: busy ? null : _verifyCode,
                      ),
                      if (_error != null) ...[
                        const SizedBox(height: 16),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          decoration: BoxDecoration(
                            color: AppColors.statusError.withAlpha(26),
                            borderRadius: AppStyles.radiusInput,
                          ),
                          child: Text(
                            _error!,
                            style: AppStyles.bodySmall.copyWith(color: AppColors.statusError),
                            textAlign: TextAlign.center,
                          ),
                        ),
                      ],
                    ],
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
