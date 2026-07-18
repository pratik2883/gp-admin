import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/core/http/dio_client.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/labeled_text_field.dart';
import 'package:gp_app/ui/widgets/labeled_password_field.dart';
import 'package:gp_app/ui/widgets/primary_button.dart';
import 'package:gp_app/ui/widgets/app_bar_gradient.dart';
import 'package:gp_app/ui/widgets/google_address_autocomplete_field.dart';
import 'package:gp_app/features/policy/widgets/terms_consent_checkbox.dart';

class GpRegisterPage extends ConsumerStatefulWidget {
  const GpRegisterPage({super.key});

  @override
  ConsumerState<GpRegisterPage> createState() => _GpRegisterPageState();
}

class _GpRegisterPageState extends ConsumerState<GpRegisterPage> {
  final _formKey = GlobalKey<FormState>();
  final _nameCtrl = TextEditingController();
  final _mobileCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _clinicNameCtrl = TextEditingController();
  final _clinicAddrCtrl = TextEditingController();
  final _passwordCtrl = TextEditingController();
  final _confirmPasswordCtrl = TextEditingController();
  bool _submitting = false;
  bool _termsAccepted = false;
  bool _addressAutocompleteEnabled = false;
  String? _googlePlacesApiKey;
  String _googlePlacesCountryCode = 'IN';

  @override
  void initState() {
    super.initState();
    Future.microtask(_loadAddressSettings);
  }

  Future<void> _loadAddressSettings() async {
    try {
      final flags = await ref.read(publicFeatureFlagsProvider.future);
      if (!mounted) return;
      setState(() {
        _addressAutocompleteEnabled = flags['enable_google_address_autocomplete'] == true;
        _googlePlacesApiKey = flags['google_places_api_key']?.toString();
        _googlePlacesCountryCode = (flags['google_places_country_code']?.toString().trim().isNotEmpty ?? false)
            ? flags['google_places_country_code'].toString()
            : 'IN';
      });
    } catch (_) {}
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _mobileCtrl.dispose();
    _emailCtrl.dispose();
    _clinicNameCtrl.dispose();
    _clinicAddrCtrl.dispose();
    _passwordCtrl.dispose();
    _confirmPasswordCtrl.dispose();
    super.dispose();
  }

  void _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_passwordCtrl.text != _confirmPasswordCtrl.text) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Passwords do not match')));
      return;
    }
    if (!_termsAccepted) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Please agree to the Terms & Conditions and Privacy Policy to continue.')));
      return;
    }
    
    setState(() => _submitting = true);
    try {
      await ref.read(authRepositoryProvider).register(
            name: _nameCtrl.text.trim(),
            email: _emailCtrl.text.trim().isEmpty ? null : _emailCtrl.text.trim(),
            mobile: _mobileCtrl.text.trim(),
            password: _passwordCtrl.text,
            role: 'gp',
            clinicName: _clinicNameCtrl.text.trim(),
            addressLine: _clinicAddrCtrl.text.trim(),
            termsAccepted: _termsAccepted,
          );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Registration successful! Please login.')));
      context.go('/gp/login');
    } catch (e) {
      if (!mounted) return;
      final msg = extractApiErrorMessage(e, fallback: 'Registration failed');
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final gps = ref.watch(currentLocationProvider).asData?.value;
    final gpsLat = gps?.latitude;
    final gpsLng = gps?.longitude;
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Create Account'),
        centerTitle: true,
        flexibleSpace: Container(decoration: appBarGradient()),
        elevation: 0,
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 24),
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
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Center(
                    child: Container(
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
                        child: Image.asset('assets/images/app_logo.png', height: 68),
                      ),
                    ),
                  ),
                  const SizedBox(height: 20),
                  Text('Registration Details', style: AppStyles.heading2),
                  const SizedBox(height: 24),
                  LabeledTextField(controller: _nameCtrl, label: 'Full Name', hintText: 'Dr. First Last'),
                  const SizedBox(height: 16),
                  LabeledTextField(controller: _mobileCtrl, label: 'Mobile Number', hintText: '+91 9999999999', keyboardType: TextInputType.phone),
                  const SizedBox(height: 16),
                  LabeledTextField(controller: _emailCtrl, label: 'Email', hintText: 'doctor@example.com', keyboardType: TextInputType.emailAddress),
                  const SizedBox(height: 16),
                  LabeledTextField(controller: _clinicNameCtrl, label: 'Clinic/Hospital Name', hintText: 'Enter clinic name'),
                  const SizedBox(height: 16),
                  if (_addressAutocompleteEnabled && (_googlePlacesApiKey ?? '').isNotEmpty) ...[
                    GoogleAddressAutocompleteField(
                      apiKey: _googlePlacesApiKey!,
                      countryCode: _googlePlacesCountryCode,
                      label: 'Search Address',
                      hintText: 'Search clinic address with Google',
                      initialValue: _clinicAddrCtrl.text,
                      currentLatitude: gpsLat,
                      currentLongitude: gpsLng,
                      onSelected: (selection) {
                        _clinicAddrCtrl.text = selection.fullAddress;
                      },
                    ),
                    const SizedBox(height: 12),
                  ],
                  LabeledTextField(controller: _clinicAddrCtrl, label: 'Clinic/Hospital Address', hintText: 'Enter full address'),
                  const SizedBox(height: 16),
                  LabeledPasswordField(controller: _passwordCtrl, label: 'Create Password', hintText: 'Enter strong password'),
                  const SizedBox(height: 16),
                  LabeledPasswordField(
                    controller: _confirmPasswordCtrl, 
                    label: 'Confirm Password', 
                    hintText: 'Re-enter password',
                    validator: (v) {
                      if (v == null || v.isEmpty) return 'Required';
                      if (v != _passwordCtrl.text) return 'Passwords do not match';
                      return null;
                    },
                  ),
                  const SizedBox(height: 16),
                  TermsConsentCheckbox(
                    value: _termsAccepted,
                    onChanged: (v) => setState(() => _termsAccepted = v ?? false),
                  ),
                  const SizedBox(height: 16),
                  PrimaryButton(label: _submitting ? 'Creating...' : 'Create Account', onPressed: _submitting ? null : _submit),
                  const SizedBox(height: 16),
                  Center(
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text('Already have an account? ', style: AppStyles.bodyMedium),
                        TextButton(
                          onPressed: () => context.pop(),
                          child: Text('Login', style: AppStyles.bodyMedium.copyWith(color: AppColors.primaryBlue, fontWeight: FontWeight.bold)),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
