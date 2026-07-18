import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/features/auth/state/auth_state.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_bar_gradient.dart';
import 'package:gp_app/ui/widgets/google_address_autocomplete_field.dart';
import 'package:gp_app/ui/widgets/labeled_text_field.dart';
import 'package:gp_app/ui/widgets/primary_button.dart';

class AccountSettingsPage extends ConsumerStatefulWidget {
  const AccountSettingsPage({super.key});

  @override
  ConsumerState<AccountSettingsPage> createState() => _AccountSettingsPageState();
}

class _AccountSettingsPageState extends ConsumerState<AccountSettingsPage> {
  final _nameController = TextEditingController();
  final _emailController = TextEditingController(); // read-only
  final _mobileController = TextEditingController();
  final _regController = TextEditingController(); // read-only
  final _clinicController = TextEditingController();
  final _addressController = TextEditingController();
  final _cityController = TextEditingController();
  
  bool _isSaving = false;
  bool _addressAutocompleteEnabled = false;
  String? _googlePlacesApiKey;
  String _googlePlacesCountryCode = 'IN';

  @override
  void initState() {
    super.initState();
    final auth = ref.read(authStateProvider);
    if (auth is Authenticated) {
      _nameController.text = auth.user.name;
      _emailController.text = auth.user.email ?? '';
      _mobileController.text = auth.user.mobile;
      _regController.text = auth.user.registrationNumber ?? 'REG-PENDING';
      _clinicController.text = auth.user.clinicName ?? '';
      _addressController.text = auth.user.address ?? '';
      _cityController.text = auth.user.city ?? '';
    }
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
    _nameController.dispose();
    _emailController.dispose();
    _mobileController.dispose();
    _regController.dispose();
    _clinicController.dispose();
    _addressController.dispose();
    _cityController.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    setState(() => _isSaving = true);
    try {
      final updated = await ref.read(gpRepositoryProvider).updateProfile({
        'name': _nameController.text.trim(),
        'mobile': _mobileController.text.trim(),
        'address': _addressController.text.trim(),
        'city': _cityController.text.trim(),
        'clinic_name': _clinicController.text.trim(),
      });
      
      // Update global auth state
      ref.read(authStateProvider.notifier).updateUser(updated);
      
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Profile updated successfully')),
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
      if (mounted) setState(() => _isSaving = false);
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
        title: const Text('Account Settings', style: TextStyle(fontWeight: FontWeight.w600)),
        centerTitle: true,
        flexibleSpace: Container(decoration: appBarGradient()),
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
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
                    controller: _nameController,
                    label: 'Full Name',
                    hintText: 'Enter your name',
                  ),
                  const SizedBox(height: 20),
                  _ReadOnlyField(
                    label: 'Email Address',
                    value: _emailController.text,
                    icon: Icons.email_outlined,
                  ),
                  const SizedBox(height: 20),
                  LabeledTextField(
                    controller: _mobileController,
                    label: 'Mobile Number',
                    hintText: 'Enter your mobile',
                    keyboardType: TextInputType.phone,
                  ),
                  const SizedBox(height: 20),
                  LabeledTextField(
                    controller: _clinicController,
                    label: 'Clinic / Practice Name',
                    hintText: 'Enter your practice name',
                  ),
                  const SizedBox(height: 20),
                  if (_addressAutocompleteEnabled && (_googlePlacesApiKey ?? '').isNotEmpty) ...[
                    GoogleAddressAutocompleteField(
                      apiKey: _googlePlacesApiKey!,
                      countryCode: _googlePlacesCountryCode,
                      label: 'Search Address',
                      hintText: 'Search clinic address with Google',
                      initialValue: _addressController.text,
                      currentLatitude: gpsLat,
                      currentLongitude: gpsLng,
                      onSelected: (selection) {
                        _addressController.text = selection.fullAddress;
                        _cityController.text = selection.cityArea;
                      },
                    ),
                    const SizedBox(height: 20),
                  ],
                  LabeledTextField(
                    controller: _addressController,
                    label: 'Full Address',
                    hintText: 'Enter your clinic address',
                    maxLines: 2,
                  ),
                  const SizedBox(height: 20),
                  LabeledTextField(
                    controller: _cityController,
                    label: 'City / Area',
                    hintText: 'e.g. Mumbai, Bandra',
                  ),
                  const SizedBox(height: 20),
                  _ReadOnlyField(
                    label: 'Registration Number',
                    value: _regController.text,
                    icon: Icons.assignment_ind_outlined,
                  ),
                  const SizedBox(height: 32),
                  const SizedBox(height: 32),
                  const Divider(),
                  const SizedBox(height: 24),
                  Text('SECURITY', style: AppStyles.bodySmall.copyWith(fontWeight: FontWeight.w800, color: AppColors.textGrey, letterSpacing: 1.1)),
                  const SizedBox(height: 16),
                  _ProfileActionTile(
                    icon: Icons.lock_outline_rounded,
                    title: 'Change Password',
                    subtitle: 'Update your login credentials',
                    onTap: () => context.push('/change-password'),
                  ),
                  const SizedBox(height: 32),
                  PrimaryButton(
                    label: _isSaving ? 'Saving...' : 'Save Changes',
                    onPressed: _isSaving ? null : _save,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ReadOnlyField extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;

  const _ReadOnlyField({
    required this.label,
    required this.value,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: AppStyles.labelText),
        const SizedBox(height: 8),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          decoration: BoxDecoration(
            color: Colors.grey.shade50,
            borderRadius: AppStyles.radiusInput,
            border: Border.all(color: AppColors.borderLight),
          ),
          child: Row(
            children: [
              Icon(icon, size: 18, color: AppColors.textGrey),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  value,
                  style: AppStyles.bodyLarge.copyWith(color: AppColors.textGrey, fontWeight: FontWeight.w500),
                ),
              ),
              const Icon(Icons.lock_outline_rounded, size: 14, color: AppColors.textGrey),
            ],
          ),
        ),
      ],
    );
  }
}

class _ProfileActionTile extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;
  const _ProfileActionTile({required this.icon, required this.title, required this.subtitle, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(color: Colors.white, borderRadius: AppStyles.radiusCard, boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.01), blurRadius: 4, offset: const Offset(0, 1))]),
      child: ListTile(
        onTap: onTap,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
        shape: RoundedRectangleBorder(borderRadius: AppStyles.radiusCard),
        leading: Container(padding: const EdgeInsets.all(8), decoration: BoxDecoration(color: AppColors.primaryBlue.withOpacity(0.1), borderRadius: BorderRadius.circular(10)), child: Icon(icon, color: AppColors.primaryBlue, size: 20)),
        title: Text(title, style: AppStyles.bodyMedium.copyWith(fontWeight: FontWeight.w700, color: AppColors.textDark)),
        subtitle: Text(subtitle, style: AppStyles.bodySmall.copyWith(color: AppColors.textGrey, fontSize: 11)),
        trailing: const Icon(Icons.chevron_right_rounded, color: AppColors.textGrey, size: 18),
      ),
    );
  }
}
