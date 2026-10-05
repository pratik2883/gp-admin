import 'dart:async';
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

class OtpFlowData {
  final String verificationId;
  final String phoneNumber;
  final String title;
  final String roleHint;
  final bool termsAccepted;

  const OtpFlowData({
    required this.verificationId,
    required this.phoneNumber,
    required this.title,
    required this.roleHint,
    required this.termsAccepted,
  });
}

class OtpVerifyPage extends ConsumerStatefulWidget {
  final OtpFlowData data;

  const OtpVerifyPage({super.key, required this.data});

  @override
  ConsumerState<OtpVerifyPage> createState() => _OtpVerifyPageState();
}

class _OtpVerifyPageState extends ConsumerState<OtpVerifyPage> {
  final _codeCtrl = TextEditingController();
  late String _verificationId = widget.data.verificationId;
  bool _verifying = false;
  bool _resending = false;
  String? _error;
  int _resendCooldown = 0;
  Timer? _cooldownTimer;

  @override
  void initState() {
    super.initState();
    _startResendCooldown();
  }

  @override
  void dispose() {
    _codeCtrl.dispose();
    _cooldownTimer?.cancel();
    super.dispose();
  }

  void _startResendCooldown() {
    _resendCooldown = 30;
    _cooldownTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) { timer.cancel(); return; }
      setState(() {
        _resendCooldown--;
        if (_resendCooldown <= 0) {
          _resendCooldown = 0;
          timer.cancel();
        }
      });
    });
  }

  Future<void> _verifyCode() async {
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
        mobile: widget.data.phoneNumber,
        verificationId: _verificationId,
        otpCode: code,
        roleHint: widget.data.roleHint,
        termsAccepted: widget.data.termsAccepted,
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

  Future<void> _resendCode() async {
    setState(() {
      _resending = true;
      _error = null;
    });

    try {
      final repo = ref.read(authRepositoryProvider);
      final newVerificationId = await repo.sendLoginOtp(mobile: widget.data.phoneNumber);
      if (!mounted) return;
      setState(() {
        _verificationId = newVerificationId;
        _resendCooldown = 0;
      });
      _startResendCooldown();
    } catch (e) {
      if (mounted) {
        setState(() => _error = extractApiErrorMessage(e, fallback: 'Resend failed'));
      }
    } finally {
      if (mounted) setState(() => _resending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(authStateProvider);
    final busy = _verifying || _resending || state is Loading;

    return Scaffold(
      backgroundColor: AppColors.background,
      body: Column(
        children: [
          AppHeroHeader(
            leading: IconButton(
              onPressed: () => context.pop(),
              icon: const Icon(Icons.arrow_back_rounded, color: AppColors.textOnDark),
            ),
            title: '${widget.data.title} OTP Login',
            subtitle: 'Enter the code sent to ${widget.data.phoneNumber}',
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
                      const SizedBox(height: 16),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text("Didn't receive the code? ", style: AppStyles.bodySmall.copyWith(color: AppColors.textSecondary)),
                          if (_resendCooldown > 0)
                            Text('${_resendCooldown}s', style: AppStyles.bodySmall.copyWith(color: AppColors.textMuted))
                          else
                            GestureDetector(
                              onTap: busy ? null : _resendCode,
                              child: Text(
                                _resending ? 'Resending...' : 'Resend OTP',
                                style: AppStyles.bodySmall.copyWith(color: AppColors.primaryBlue, fontWeight: FontWeight.w700),
                              ),
                            ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      GestureDetector(
                        onTap: () => context.pop(),
                        child: Text(
                          'Change mobile number',
                          style: AppStyles.bodySmall.copyWith(color: AppColors.secondaryTeal, fontWeight: FontWeight.w600),
                        ),
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
