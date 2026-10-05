import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:file_picker/file_picker.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/labeled_text_field.dart';
import 'package:gp_app/ui/widgets/labeled_password_field.dart';
import 'package:gp_app/ui/widgets/primary_button.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/app/role.dart';
import 'package:gp_app/ui/widgets/app_hero_header.dart';
import 'package:gp_app/ui/widgets/app_card.dart';
import 'package:gp_app/core/http/dio_client.dart';
import 'package:gp_app/features/diagnostic_center/presentation/dx_register_page.dart';
import 'package:gp_app/features/subscriptions/subscription_payment_helper.dart';
import 'package:gp_app/ui/widgets/google_address_autocomplete_field.dart';
import 'package:gp_app/features/policy/widgets/terms_consent_checkbox.dart';

class SpRegisterPage extends ConsumerStatefulWidget {
  const SpRegisterPage({super.key});
  @override
  ConsumerState<SpRegisterPage> createState() => _SpRegisterPageState();
}

class _SpRegisterPageState extends ConsumerState<SpRegisterPage> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _mobile = TextEditingController();
  final _whatsappNumber = TextEditingController();
  final _password = TextEditingController();
  final _hospitalName = TextEditingController();
  final _clinicStreet = TextEditingController();
  final _clinicArea = TextEditingController();
  final _clinicCity = TextEditingController();
  final _clinicPincode = TextEditingController();
  final _registrationNo = TextEditingController();
  final _councilName = TextEditingController();
  final _bio = TextEditingController();
  final _videos = TextEditingController();
  List<Map<String, dynamic>> _specialties = [];
  List<Map<String, dynamic>> _subscriptionPlans = [];
  List<Map<String, dynamic>> _subscriptionGroups = [];
  String? _specialtySlug;
  String? _selectedPlanFamily;
  String? _selectedBedSlab;
  int? _selectedPlanId;
  final List<int> _additionalSpecialtyIds = [];
  bool _allowVideos = false;
  bool _allowCertificates = false;
  bool _subscriptionPaymentsEnabled = false;
  bool _addressAutocompleteEnabled = false;
  String? _googlePlacesApiKey;
  String _googlePlacesCountryCode = 'IN';
  final List<PlatformFile> _certificateFiles = [];
  PlatformFile? _profilePhotoFile;
  bool _submitting = false;
  bool _termsAccepted = false;

  @override
  void initState() {
    super.initState();
    Future.microtask(() async {
      try {
        final subtype = ref.read(selectedSubtypeProvider);
        if (subtype == RoleSubtype.diagnostic) {
          return;
        }
        final items = await ref.read(specialistRepositoryProvider).listSpecialties();
        final flags = await ref.read(specialistRepositoryProvider).fetchPublicFeatureFlags();
        final paymentsEnabled = flags['enable_subscription_payments'] == true;
        List<Map<String, dynamic>> rawPlans = const [];
        List<Map<String, dynamic>> rawGroups = const [];
        if (paymentsEnabled) {
          final plans = await ref.read(specialistRepositoryProvider).fetchSubscriptionPlans(
            subtype == RoleSubtype.hospital ? 'hospital' : 'specialist',
          );
          rawPlans = (plans['plans'] as List? ?? const [])
              .whereType<Map>()
              .map((e) => e.cast<String, dynamic>())
              .toList();
          rawGroups = (plans['groups'] as List? ?? const [])
              .whereType<Map>()
              .map((e) => e.cast<String, dynamic>())
              .toList();
        }
        if (mounted) {
          setState(() {
            _specialties = items;
            _subscriptionPaymentsEnabled = paymentsEnabled;
            _subscriptionPlans = rawPlans;
            _subscriptionGroups = rawGroups;
            _selectedPlanFamily = subtype == RoleSubtype.hospital
                ? null
                : (rawGroups.isNotEmpty ? rawGroups.first['key']?.toString() : null);
            _selectedBedSlab = subtype == RoleSubtype.hospital
                ? (rawGroups.isNotEmpty ? rawGroups.first['key']?.toString() : null)
                : null;
            _allowVideos = flags['allow_profile_videos_for_specialists'] == true;
            _allowCertificates = flags['allow_profile_certificates_for_specialists'] == true;
            _addressAutocompleteEnabled = flags['enable_google_address_autocomplete'] == true;
            _googlePlacesApiKey = flags['google_places_api_key']?.toString();
            _googlePlacesCountryCode = (flags['google_places_country_code']?.toString().trim().isNotEmpty ?? false)
                ? flags['google_places_country_code'].toString()
                : 'IN';
          });
        }
      } catch (_) {}
    });
  }

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _mobile.dispose();
    _whatsappNumber.dispose();
    _password.dispose();
    _hospitalName.dispose();
    _clinicStreet.dispose();
    _clinicArea.dispose();
    _clinicCity.dispose();
    _clinicPincode.dispose();
    _registrationNo.dispose();
    _councilName.dispose();
    _bio.dispose();
    _videos.dispose();
    super.dispose();
  }

  Future<void> _pickAdditionalSpecialties() async {
    if (_specialties.isEmpty) return;
    final selected = Set<int>.from(_additionalSpecialtyIds);

    final result = await showModalBottomSheet<Set<int>>(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return SafeArea(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Additional Specialties', style: AppStyles.heading2),
                    const SizedBox(height: 12),
                    Flexible(
                      child: ListView(
                        shrinkWrap: true,
                        children: _specialties.map((s) {
                          final id = (s['id'] as int?) ?? int.tryParse('${s['id']}');
                          final label = (s['label'] ?? s['name'] ?? '').toString();
                          if (id == null || label.isEmpty) return const SizedBox.shrink();
                          return CheckboxListTile(
                            value: selected.contains(id),
                            onChanged: (v) {
                              setModalState(() {
                                if (v == true) {
                                  selected.add(id);
                                } else {
                                  selected.remove(id);
                                }
                              });
                            },
                            title: Text(label),
                            contentPadding: EdgeInsets.zero,
                          );
                        }).toList(),
                      ),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: OutlinedButton(
                            onPressed: () => Navigator.pop(context, <int>{}),
                            child: const Text('Clear'),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: ElevatedButton(
                            onPressed: () => Navigator.pop(context, selected),
                            child: const Text('Apply'),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );

    if (!mounted) return;
    if (result == null) return;

    setState(() {
      _additionalSpecialtyIds
        ..clear()
        ..addAll(result.toList()..sort());
    });
  }

  Future<void> _pickCertificates() async {
    final res = await FilePicker.platform.pickFiles(
      allowMultiple: true,
      type: FileType.custom,
      allowedExtensions: const ['pdf', 'jpg', 'jpeg', 'png'],
    );
    if (!mounted) return;
    if (res == null) return;
    setState(() {
      _certificateFiles.addAll(res.files.where((f) => f.path != null));
    });
  }

  Future<void> _pickProfilePhoto() async {
    final res = await FilePicker.platform.pickFiles(
      allowMultiple: false,
      type: FileType.custom,
      allowedExtensions: const ['jpg', 'jpeg', 'png'],
    );
    if (!mounted) return;
    if (res == null || res.files.isEmpty) return;
    final picked = res.files.first;
    if ((picked.path == null || picked.path!.isEmpty) && picked.bytes == null) return;
    setState(() => _profilePhotoFile = picked);
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_subscriptionPaymentsEnabled && _selectedPlanId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please select a subscription plan')),
      );
      return;
    }
    if (!_termsAccepted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please agree to the Terms & Conditions and Privacy Policy to continue.')),
      );
      return;
    }
    setState(() => _submitting = true);
    try {
      final subtype = ref.read(selectedSubtypeProvider);
      final roleSubtype = subtype == RoleSubtype.hospital ? 'hospital' : 'specialist';
      final response = await ref.read(specialistRepositoryProvider).register(
        {
          'name': _name.text.trim(),
          'email': _email.text.trim().isEmpty ? null : _email.text.trim(),
          'mobile': _mobile.text.trim(),
          'whatsapp_number': _whatsappNumber.text.trim().isEmpty ? _mobile.text.trim() : _whatsappNumber.text.trim(),
          'password': _password.text,
          'role_subtype': roleSubtype,
          if (_subscriptionPaymentsEnabled) 'subscription_plan_id': _selectedPlanId,
          if ((_specialtySlug ?? '').isNotEmpty) 'specialty_code': _specialtySlug,
          if (_additionalSpecialtyIds.isNotEmpty) 'additional_specialty_ids': _additionalSpecialtyIds,
          'hospital_name': _hospitalName.text.trim().isEmpty ? null : _hospitalName.text.trim(),
          'clinic_street': _clinicStreet.text.trim().isEmpty ? null : _clinicStreet.text.trim(),
          'clinic_area': _clinicArea.text.trim().isEmpty ? null : _clinicArea.text.trim(),
          'clinic_city': _clinicCity.text.trim().isEmpty ? null : _clinicCity.text.trim(),
          'clinic_pincode': _clinicPincode.text.trim().isEmpty ? null : _clinicPincode.text.trim(),
          'registration_no': _registrationNo.text.trim().isEmpty ? null : _registrationNo.text.trim(),
          'council_name': _councilName.text.trim().isEmpty ? null : _councilName.text.trim(),
          'bio': _bio.text.trim().isEmpty ? null : _bio.text.trim(),
          'terms_accepted': true,
          if (_allowVideos)
            'videos': _videos.text
                .split(RegExp(r'[\n,]'))
                .map((e) => e.trim())
                .where((e) => e.isNotEmpty)
                .toList(),
        },
        certificatePaths: _allowCertificates ? _certificateFiles.where((f) => f.path != null).map((f) => f.path!).toList() : const [],
        profilePhoto: _profilePhotoFile,
      );
      final payment = response['payment'] is Map
          ? (response['payment'] as Map).cast<String, dynamic>()
          : null;
      var paymentCompleted = false;
      if (mounted && payment != null && payment.isNotEmpty) {
        paymentCompleted = await SubscriptionPaymentHelper.handlePendingPayment(
          context: context,
          payment: payment,
          fetchStatus: (statusUrl) => ref.read(specialistRepositoryProvider).fetchPaymentStatus(statusUrl),
        );
      }
      if (mounted) {
        final message = payment == null || payment.isEmpty
            ? 'Registered successfully'
            : paymentCompleted
                ? 'Registration and payment completed successfully'
                : 'Registration completed. Payment is still pending';
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
        Navigator.pop(context);
      }
    } catch (_) {
      if (mounted) {
        final msg = extractApiErrorMessage(_, fallback: 'Registration failed');
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
      }
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  List<Map<String, dynamic>> _visiblePlans(RoleSubtype? subtype) {
    if (subtype == RoleSubtype.hospital) {
      final slab = _selectedBedSlab;
      if ((slab ?? '').isEmpty) return const [];
      return _subscriptionPlans.where((plan) => plan['bed_slab']?.toString() == slab).toList();
    }

    final family = _selectedPlanFamily;
    if ((family ?? '').isEmpty) return const [];
    return _subscriptionPlans.where((plan) => plan['plan_family']?.toString() == family).toList();
  }

  Map<String, dynamic>? _selectedPlanDetails() {
    for (final plan in _subscriptionPlans) {
      final id = (plan['id'] as int?) ?? int.tryParse('${plan['id']}');
      if (id == _selectedPlanId) {
        return plan;
      }
    }
    return null;
  }

  Widget _buildSubscriptionSection(RoleSubtype? subtype) {
    final visiblePlans = _visiblePlans(subtype);
    final selectedPlan = _selectedPlanDetails();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Subscription Plan', style: AppStyles.heading2),
        const SizedBox(height: 8),
        Text(
          subtype == RoleSubtype.hospital
              ? 'Hospital size nusar plan select kara.'
              : 'Normal kiwa Premium plan family select kara.',
          style: AppStyles.bodySmall,
        ),
        const SizedBox(height: 12),
        if (_subscriptionPlans.isEmpty)
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: AppColors.surfaceMuted,
              borderRadius: AppStyles.radiusCard,
              border: Border.all(color: AppColors.border),
            ),
            child: Text(
              'Admin ne ajun plans configure kele nahi.',
              style: AppStyles.bodySmall,
            ),
          ),
        if (subtype == RoleSubtype.hospital && _subscriptionGroups.isNotEmpty) ...[
          DropdownButtonFormField<String>(
            initialValue: _selectedBedSlab,
            decoration: const InputDecoration(labelText: 'Hospital Bed Category'),
            items: _subscriptionGroups
                .map(
                  (group) => DropdownMenuItem<String>(
                    value: group['key']?.toString(),
                    child: Text(group['label']?.toString() ?? 'Category'),
                  ),
                )
                .toList(),
            onChanged: (value) {
              setState(() {
                _selectedBedSlab = value;
                _selectedPlanId = null;
              });
            },
          ),
          const SizedBox(height: 12),
        ],
        if (subtype != RoleSubtype.hospital && _subscriptionGroups.isNotEmpty) ...[
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: _subscriptionGroups.map((group) {
              final key = group['key']?.toString();
              final selected = key == _selectedPlanFamily;
              return ChoiceChip(
                label: Text(group['label']?.toString() ?? 'Plan'),
                selected: selected,
                onSelected: (_) {
                  setState(() {
                    _selectedPlanFamily = key;
                    _selectedPlanId = null;
                  });
                },
              );
            }).toList(),
          ),
          const SizedBox(height: 12),
        ],
        ...visiblePlans.map((plan) {
          final id = (plan['id'] as int?) ?? int.tryParse('${plan['id']}');
          return Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: _SubscriptionPlanCard(
              title: plan['name']?.toString() ?? 'Plan',
              subtitle: '${plan['duration_months']} months',
              priceLabel: '${plan['currency'] ?? 'INR'} ${plan['price']}',
              description: plan['description']?.toString(),
              selected: _selectedPlanId == id,
              onTap: id == null
                  ? null
                  : () {
                      setState(() => _selectedPlanId = id);
                    },
            ),
          );
        }),
        if (selectedPlan != null) ...[
          const SizedBox(height: 8),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: AppColors.secondaryTeal.withAlpha(18),
              borderRadius: AppStyles.radiusCard,
              border: Border.all(color: AppColors.secondaryTeal.withAlpha(60)),
            ),
            child: Text(
              'Selected: ${selectedPlan['name']} - ${selectedPlan['currency'] ?? 'INR'} ${selectedPlan['price']}',
              style: AppStyles.bodyMedium,
            ),
          ),
        ],
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    final subtype = ref.watch(selectedSubtypeProvider);
    final gps = ref.watch(currentLocationProvider).asData?.value;
    final gpsLat = gps?.latitude;
    final gpsLng = gps?.longitude;

    if (subtype == RoleSubtype.diagnostic) {
      return const DxRegisterPage();
    }
    
    final registerTitle = switch (subtype) {
      RoleSubtype.hospital => 'Hospital Registration',
      RoleSubtype.diagnostic => 'Diagnostic Registration',
      _ => 'Specialist Registration',
    };

    final buttonLabel = switch (subtype) {
      RoleSubtype.hospital => 'Create Hospital Profile',
      RoleSubtype.diagnostic => 'Create Diagnostic Profile',
      _ => 'Create Specialist Profile',
    };

    return Scaffold(
      backgroundColor: AppColors.background,
      body: Column(
        children: [
          AppHeroHeader(
            leading: IconButton(
              onPressed: () => Navigator.pop(context),
              icon: const Icon(Icons.arrow_back_rounded, color: AppColors.textOnDark),
            ),
            title: registerTitle,
            subtitle: 'Join SpecialistConnect Pro network',
          ),
          Expanded(
            child: SingleChildScrollView(
              padding: AppSpacing.screenPadding.copyWith(top: 18, bottom: 40),
              child: AppCard(
                child: Form(
                  key: _formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Center(
                        child: Column(
                          children: [
                            CircleAvatar(
                              radius: 44,
                              backgroundColor: AppColors.primaryBlue.withAlpha(20),
                              backgroundImage: _profilePhotoFile != null
                                  ? _profilePhotoFile!.path != null
                                      ? FileImage(File(_profilePhotoFile!.path!)) as ImageProvider
                                      : MemoryImage(_profilePhotoFile!.bytes!)
                                  : null,
                              child: _profilePhotoFile == null
                                  ? const Icon(Icons.person_add_rounded, size: 40, color: AppColors.primaryBlue)
                                  : null,
                            ),
                            const SizedBox(height: 12),
                            TextButton.icon(
                              onPressed: _pickProfilePhoto,
                              icon: const Icon(Icons.add_a_photo_outlined, size: 18),
                              label: Text(_profilePhotoFile == null ? 'Add Profile Photo' : 'Change Profile Photo'),
                              style: TextButton.styleFrom(foregroundColor: AppColors.primaryBlue),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 32),
                      Text('Profile Information', style: AppStyles.heading2),
                      const SizedBox(height: 16),
                      LabeledTextField(
                        controller: _name, 
                        label: 'Full Name / Entity Name', 
                        hintText: subtype == RoleSubtype.individual ? 'Dr. John Doe' : 'Name of your Hospital/Center',
                      ),
                      const SizedBox(height: 16),
                      LabeledTextField(
                        controller: _email,
                        label: 'Email Address',
                        hintText: 'example@mail.com',
                        keyboardType: TextInputType.emailAddress,
                      ),
                      const SizedBox(height: 16),
                      Text('Contact Details', style: AppStyles.heading2),
                      const SizedBox(height: 16),
                      LabeledTextField(
                        controller: _mobile, 
                        label: 'Mobile Number', 
                        hintText: 'Enter 10-digit mobile number',
                        keyboardType: TextInputType.phone,
                      ),
                      const SizedBox(height: 16),
                      LabeledTextField(
                        controller: _whatsappNumber, 
                        label: 'WhatsApp Number (Optional)', 
                        hintText: 'If different from mobile number',
                        keyboardType: TextInputType.phone,
                      ),
                      const SizedBox(height: 16),
                      Text('Security', style: AppStyles.heading2),
                      const SizedBox(height: 16),
                      LabeledPasswordField(
                        controller: _password, 
                        label: 'Create Password', 
                        hintText: 'Minimum 6 characters',
                      ),
                      const SizedBox(height: 16),
                      DropdownButtonFormField<String>(
                        initialValue: _specialtySlug,
                        decoration: InputDecoration(
                          labelText: 'Primary Specialty',
                          hintText: 'Select specialty',
                          labelStyle: AppStyles.labelText,
                          filled: true,
                          fillColor: AppColors.inputBackground,
                          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                          border: OutlineInputBorder(
                            borderRadius: AppStyles.radiusInput,
                            borderSide: const BorderSide(color: AppColors.borderLight),
                          ),
                          enabledBorder: OutlineInputBorder(
                            borderRadius: AppStyles.radiusInput,
                            borderSide: const BorderSide(color: AppColors.borderLight),
                          ),
                          focusedBorder: OutlineInputBorder(
                            borderRadius: AppStyles.radiusInput,
                            borderSide: const BorderSide(color: AppColors.primaryBlue, width: 1.5),
                          ),
                        ),
                        items: _specialties
                            .map((s) => DropdownMenuItem(
                                  value: s['slug']?.toString(),
                                  child: Text((s['label'] ?? s['name'] ?? '').toString()),
                                ))
                            .toList(),
                        onChanged: (v) => setState(() => _specialtySlug = v),
                        validator: (v) => (v == null || v.isEmpty) ? 'Required' : null,
                      ),
                      const SizedBox(height: 12),
                      OutlinedButton.icon(
                        onPressed: _specialties.isEmpty ? null : _pickAdditionalSpecialties,
                        icon: const Icon(Icons.add_rounded),
                        label: Text(
                          _additionalSpecialtyIds.isEmpty ? 'Add Additional Specialties' : 'Additional Specialties: ${_additionalSpecialtyIds.length}',
                        ),
                      ),
                      const SizedBox(height: 24),
                      Text('Professional Profile', style: AppStyles.heading2),
                      const SizedBox(height: 16),
                      LabeledTextField(controller: _hospitalName, label: 'Hospital Name', hintText: 'Enter hospital name'),
                      const SizedBox(height: 16),
                      if (_addressAutocompleteEnabled && (_googlePlacesApiKey ?? '').isNotEmpty) ...[
                        GoogleAddressAutocompleteField(
                          apiKey: _googlePlacesApiKey!,
                          countryCode: _googlePlacesCountryCode,
                          label: 'Search Clinic Address',
                          hintText: 'Search address with Google',
                          initialValue: [
                            _clinicStreet.text,
                            _clinicArea.text,
                            _clinicCity.text,
                            _clinicPincode.text,
                          ].where((e) => e.trim().isNotEmpty).join(', '),
                          currentLatitude: gpsLat,
                          currentLongitude: gpsLng,
                          onSelected: (selection) {
                            _clinicStreet.text = selection.street;
                            if (selection.area.isNotEmpty) _clinicArea.text = selection.area;
                            if (selection.city.isNotEmpty) _clinicCity.text = selection.city;
                            if (selection.pincode.isNotEmpty) _clinicPincode.text = selection.pincode;
                          },
                        ),
                        const SizedBox(height: 16),
                      ],
                      LabeledTextField(controller: _clinicStreet, label: 'Street', hintText: 'Street / Building'),
                      const SizedBox(height: 16),
                      LabeledTextField(controller: _clinicArea, label: 'Area', hintText: 'Area / Locality'),
                      const SizedBox(height: 16),
                      LabeledTextField(controller: _clinicCity, label: 'City', hintText: 'City'),
                      const SizedBox(height: 16),
                      LabeledTextField(controller: _clinicPincode, label: 'Pincode', hintText: 'Pincode', keyboardType: TextInputType.number),
                      const SizedBox(height: 16),
                      LabeledTextField(controller: _registrationNo, label: 'Registration No', hintText: 'Medical council registration'),
                      const SizedBox(height: 16),
                      LabeledTextField(controller: _councilName, label: 'Council Name', hintText: 'Council name'),
                      const SizedBox(height: 16),
                      LabeledTextField(controller: _bio, label: 'Bio', hintText: 'Write short professional bio', maxLines: 3),
                      if (_allowVideos) ...[
                        const SizedBox(height: 16),
                        LabeledTextField(
                          controller: _videos,
                          label: 'Videos (URLs)',
                          hintText: 'Comma or new-line separated URLs',
                          maxLines: 3,
                        ),
                      ],
                      if (_allowCertificates) ...[
                        const SizedBox(height: 16),
                        OutlinedButton.icon(
                          onPressed: _pickCertificates,
                          icon: const Icon(Icons.upload_file_rounded),
                          label: const Text('Upload Certificates'),
                        ),
                        const SizedBox(height: 8),
                        Text('Selected: ${_certificateFiles.length}', style: AppStyles.caption),
                      ],
                      const SizedBox(height: 24),
                      if (_subscriptionPaymentsEnabled) _buildSubscriptionSection(subtype),
                      const SizedBox(height: 32),
                      PrimaryButton(
                        label: _submitting ? 'Creating Account...' : buttonLabel, 
                        onPressed: _submitting ? null : _submit,
                      ),
                      const SizedBox(height: 8),
                      TermsConsentCheckbox(
                        value: _termsAccepted,
                        onChanged: (v) => setState(() => _termsAccepted = v ?? false),
                      ),
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

class _SubscriptionPlanCard extends StatelessWidget {
  final String title;
  final String subtitle;
  final String priceLabel;
  final String? description;
  final bool selected;
  final VoidCallback? onTap;

  const _SubscriptionPlanCard({
    required this.title,
    required this.subtitle,
    required this.priceLabel,
    required this.description,
    required this.selected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return AppCard(
      onTap: onTap,
      color: selected ? AppColors.secondaryTeal.withAlpha(14) : AppColors.surface,
      border: Border.all(
        color: selected ? AppColors.secondaryTeal : AppColors.border,
        width: selected ? 1.4 : 1,
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: AppStyles.heading2),
                const SizedBox(height: 4),
                Text(subtitle, style: AppStyles.bodySmall),
                if ((description ?? '').trim().isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Text(description!, style: AppStyles.bodySmall),
                ],
              ],
            ),
          ),
          const SizedBox(width: 12),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(priceLabel, style: AppStyles.bodyLarge),
              const SizedBox(height: 8),
              Icon(
                selected ? Icons.radio_button_checked_rounded : Icons.radio_button_off_rounded,
                color: selected ? AppColors.secondaryTeal : AppColors.textMuted,
              ),
            ],
          ),
        ],
      ),
    );
  }
}
