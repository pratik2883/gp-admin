import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/core/http/dio_client.dart';
import 'package:gp_app/features/auth/presentation/otp_verify_page.dart';
import 'package:gp_app/features/auth/state/auth_state.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_card.dart';
import 'package:gp_app/ui/widgets/app_hero_header.dart';
import 'package:gp_app/ui/widgets/labeled_text_field.dart';
import 'package:gp_app/ui/widgets/primary_button.dart';
import 'package:gp_app/features/policy/widgets/terms_consent_checkbox.dart';

class OtpPhonePage extends ConsumerStatefulWidget {
  final String title;
  final String roleHint;

  const OtpPhonePage({
    super.key,
    required this.title,
    required this.roleHint,
  });

  @override
  ConsumerState<OtpPhonePage> createState() => _OtpPhonePageState();
}

class _OtpPhonePageState extends ConsumerState<OtpPhonePage> {
  final _phoneCtrl = TextEditingController();
  bool _sending = false;
  bool _termsAccepted = false;
  String? _error;

  @override
  void dispose() {
    _phoneCtrl.dispose();
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

      final prefix = widget.roleHint == 'specialist' ? 'sp' : 'gp';
      context.push('/$prefix/otp-verify', extra: OtpFlowData(
        verificationId: verificationId,
        phoneNumber: phone,
        title: widget.title,
        roleHint: widget.roleHint,
        termsAccepted: _termsAccepted,
      ));
    } catch (e) {
      if (mounted) {
        setState(() => _error = extractApiErrorMessage(e, fallback: 'OTP send failed'));
      }
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(authStateProvider);
    final busy = _sending || state is Loading;

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
            subtitle: 'Enter your mobile number',
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
                        label: _sending ? 'Sending...' : 'Send OTP',
                        onPressed: busy ? null : _sendCode,
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
