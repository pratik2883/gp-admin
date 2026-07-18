import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_card.dart';
import 'package:gp_app/ui/widgets/app_hero_header.dart';
import 'package:gp_app/ui/widgets/labeled_password_field.dart';
import 'package:gp_app/ui/widgets/labeled_text_field.dart';
import 'package:gp_app/ui/widgets/primary_button.dart';
import 'package:gp_app/ui/widgets/google_address_autocomplete_field.dart';
import 'package:gp_app/features/subscriptions/subscription_payment_helper.dart';
import 'package:gp_app/features/policy/widgets/terms_consent_checkbox.dart';

class DxRegisterPage extends ConsumerStatefulWidget {
  const DxRegisterPage({super.key});

  @override
  ConsumerState<DxRegisterPage> createState() => _DxRegisterPageState();
}

class _DxRegisterPageState extends ConsumerState<DxRegisterPage> {
  final _formKey = GlobalKey<FormState>();

  final _centerName = TextEditingController();
  final _address = TextEditingController();
  final _microArea = TextEditingController();
  final _email = TextEditingController();
  final _mobile = TextEditingController();
  final _alternate = TextEditingController();

  final _authorizedName = TextEditingController();
  final _authorizedMobile = TextEditingController();
  final _authorizedEmail = TextEditingController();

  final _password = TextEditingController();

  String? _centerType;
  int? _locationId;
  TimeOfDay? _opening;
  TimeOfDay? _closing;
  final Set<String> _availableDays = {};
  String? _weeklyOff;
  String? _authorizedRole;
  final Set<int> _serviceTypeIds = {};

  bool _loading = true;
  bool _submitting = false;
  bool _termsAccepted = false;
  bool _subscriptionPaymentsEnabled = false;
  bool _addressAutocompleteEnabled = false;
  String? _googlePlacesApiKey;
  String _googlePlacesCountryCode = 'IN';
  int? _selectedPlanId;
  List<Map<String, dynamic>> _subscriptionPlans = [];
  List<Map<String, dynamic>> _locations = [];
  List<Map<String, dynamic>> _serviceTypes = [];

  @override
  void initState() {
    super.initState();
    Future.microtask(_loadMaster);
  }

  Future<void> _loadMaster() async {
    try {
      final repo = ref.read(diagnosticCenterRepositoryProvider);
      final loc = await repo.listLocations();
      final types = await repo.listServiceTypes();
      final flags = await ref.read(publicFeatureFlagsProvider.future);
      final paymentsEnabled = flags['enable_subscription_payments'] == true;
      List<Map<String, dynamic>> plans = const [];
      if (paymentsEnabled) {
        final planPayload = await repo.fetchSubscriptionPlans('diagnostic_center');
        plans = (planPayload['plans'] as List? ?? const [])
            .whereType<Map>()
            .map((e) => e.cast<String, dynamic>())
            .toList();
      }
      if (!mounted) return;
      setState(() {
        _locations = loc;
        _serviceTypes = types;
        _subscriptionPaymentsEnabled = paymentsEnabled;
        _subscriptionPlans = plans;
        _addressAutocompleteEnabled = flags['enable_google_address_autocomplete'] == true;
        _googlePlacesApiKey = flags['google_places_api_key']?.toString();
        _googlePlacesCountryCode = (flags['google_places_country_code']?.toString().trim().isNotEmpty ?? false)
            ? flags['google_places_country_code'].toString()
            : 'IN';
        _loading = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _loading = false);
    }
  }

  void _applyGoogleAddress(GoogleAddressSelection selection) {
    _address.text = selection.fullAddress;
    if (selection.area.isNotEmpty) {
      _microArea.text = selection.area;
    }
    if (selection.city.isEmpty) return;
    final city = selection.city.toLowerCase();
    final match = _locations.cast<Map<String, dynamic>?>().firstWhere(
          (item) => (item?['name']?.toString().toLowerCase().contains(city) ?? false) || city.contains(item?['name']?.toString().toLowerCase() ?? ''),
          orElse: () => null,
        );
    final locationId = match?['id'] as int? ?? int.tryParse('${match?['id']}');
    if (locationId != null) {
      setState(() => _locationId = locationId);
    }
  }

  @override
  void dispose() {
    _centerName.dispose();
    _address.dispose();
    _microArea.dispose();
    _email.dispose();
    _mobile.dispose();
    _alternate.dispose();
    _authorizedName.dispose();
    _authorizedMobile.dispose();
    _authorizedEmail.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _pickTime({required bool isOpening}) async {
    final initial = isOpening ? (_opening ?? const TimeOfDay(hour: 9, minute: 0)) : (_closing ?? const TimeOfDay(hour: 18, minute: 0));
    final picked = await showTimePicker(
      context: context,
      initialTime: initial,
    );
    if (!mounted) return;
    if (picked == null) return;
    setState(() {
      if (isOpening) {
        _opening = picked;
      } else {
        _closing = picked;
      }
    });
  }

  String? _formatTime(TimeOfDay? t) {
    if (t == null) return null;
    final dt = DateTime(2000, 1, 1, t.hour, t.minute);
    return DateFormat('h:mm a').format(dt);
  }

  String? _time24(TimeOfDay? t) {
    if (t == null) return null;
    final hh = t.hour.toString().padLeft(2, '0');
    final mm = t.minute.toString().padLeft(2, '0');
    return '$hh:$mm';
  }

  Future<void> _pickServices() async {
    final selected = Set<int>.from(_serviceTypeIds);
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
                    Text('Select Services', style: AppStyles.heading2),
                    const SizedBox(height: 12),
                    Flexible(
                      child: ListView(
                        shrinkWrap: true,
                        children: _serviceTypes.map((t) {
                          final id = (t['id'] as int?) ?? int.tryParse('${t['id']}');
                          final label = (t['label'] ?? t['name'] ?? '').toString();
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
      _serviceTypeIds
        ..clear()
        ..addAll(result);
    });
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_opening == null || _closing == null) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Please select opening and closing time')));
      return;
    }
    if (_serviceTypeIds.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Please select at least one service')));
      return;
    }
    if (_weeklyOff != null && _availableDays.contains(_weeklyOff)) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Weekly off cannot be in available days')));
      return;
    }
    if (_subscriptionPaymentsEnabled && _selectedPlanId == null) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Please select a subscription plan')));
      return;
    }
    if (!_termsAccepted) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Please agree to the Terms & Conditions and Privacy Policy to continue.')));
      return;
    }

    setState(() => _submitting = true);
    try {
      final response = await ref.read(diagnosticCenterRepositoryProvider).register({
        'center_name': _centerName.text.trim(),
        'center_type': _centerType,
        'location_id': _locationId,
        'address': _address.text.trim(),
        'micro_area': _microArea.text.trim(),
        'email': _email.text.trim(),
        'mobile_number': _mobile.text.trim(),
        'alternate_number': _alternate.text.trim().isEmpty ? null : _alternate.text.trim(),
        'service_type_ids': _serviceTypeIds.toList(),
        'opening_time': _time24(_opening),
        'closing_time': _time24(_closing),
        'available_days': _availableDays.toList(),
        'weekly_off': _weeklyOff,
        'authorized_person_name': _authorizedName.text.trim(),
        'authorized_person_role': _authorizedRole,
        'authorized_person_mobile': _authorizedMobile.text.trim(),
        'authorized_person_email': _authorizedEmail.text.trim(),
        if (_subscriptionPaymentsEnabled) 'subscription_plan_id': _selectedPlanId,
        'password': _password.text,
        'terms_accepted': true,
      });
      final payment = response['payment'] is Map
          ? (response['payment'] as Map).cast<String, dynamic>()
          : null;
      var paymentCompleted = false;
      if (mounted && payment != null && payment.isNotEmpty) {
        paymentCompleted = await SubscriptionPaymentHelper.handlePendingPayment(
          context: context,
          payment: payment,
          fetchStatus: (statusUrl) => ref.read(diagnosticCenterRepositoryProvider).fetchPaymentStatus(statusUrl),
        );
      }
      if (!mounted) return;
      final message = payment == null || payment.isEmpty
          ? 'Registered successfully'
          : paymentCompleted
              ? 'Registration and payment completed successfully'
              : 'Registration completed. Payment is still pending';
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
      Navigator.pop(context);
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Registration failed')));
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
      body: Column(
        children: [
          AppHeroHeader(
            leading: IconButton(
              onPressed: () => Navigator.pop(context),
              icon: const Icon(Icons.arrow_back_rounded, color: AppColors.textOnDark),
            ),
            title: 'Diagnostic Center Registration',
            subtitle: 'Create your center profile',
          ),
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator(color: AppColors.secondaryTeal))
                : SingleChildScrollView(
                    padding: AppSpacing.screenPadding.copyWith(top: 18, bottom: 40),
                    child: AppCard(
                      child: Form(
                        key: _formKey,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Basic Center Details', style: AppStyles.heading2),
                            const SizedBox(height: 16),
                            LabeledTextField(
                              controller: _centerName,
                              label: 'Center Name',
                              hintText: 'Enter center name',
                              validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                            ),
                            const SizedBox(height: 16),
                            DropdownButtonFormField<String>(
                              initialValue: _centerType,
                              decoration: const InputDecoration(labelText: 'Center Type'),
                              items: const [
                                DropdownMenuItem(value: 'lab', child: Text('Lab / Pathology')),
                                DropdownMenuItem(value: 'imaging', child: Text('Radiology / Imaging')),
                                DropdownMenuItem(value: 'cardiology', child: Text('Cardiology Diagnostics')),
                                DropdownMenuItem(value: 'multi', child: Text('Multi-service Center')),
                              ],
                              onChanged: (v) => setState(() => _centerType = v),
                              validator: (v) => (v == null || v.isEmpty) ? 'Required' : null,
                            ),
                            const SizedBox(height: 16),
                            DropdownButtonFormField<int>(
                              initialValue: _locationId,
                              isExpanded: true,
                              decoration: const InputDecoration(labelText: 'City / Location'),
                              items: _locations
                                  .map((l) => DropdownMenuItem(
                                        value: (l['id'] as int?) ?? int.tryParse('${l['id']}'),
                                        child: Text(l['name']?.toString() ?? 'Location'),
                                      ))
                                  .where((e) => e.value != null)
                                  .cast<DropdownMenuItem<int>>()
                                  .toList(),
                              onChanged: (v) => setState(() => _locationId = v),
                              validator: (v) => v == null ? 'Required' : null,
                            ),
                            const SizedBox(height: 16),
                            if (_addressAutocompleteEnabled && (_googlePlacesApiKey ?? '').isNotEmpty) ...[
                              GoogleAddressAutocompleteField(
                                apiKey: _googlePlacesApiKey!,
                                countryCode: _googlePlacesCountryCode,
                                label: 'Search Address',
                                hintText: 'Search center address with Google',
                                initialValue: _address.text,
                                currentLatitude: gpsLat,
                                currentLongitude: gpsLng,
                                onSelected: _applyGoogleAddress,
                              ),
                              const SizedBox(height: 16),
                            ],
                            LabeledTextField(
                              controller: _address,
                              label: 'Address',
                              hintText: 'Enter address',
                              validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                            ),
                            const SizedBox(height: 16),
                            LabeledTextField(
                              controller: _microArea,
                              label: 'Area / Micro Area',
                              hintText: 'Enter area',
                              validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                            ),
                            const SizedBox(height: 22),
                            Text('Contact Details', style: AppStyles.heading2),
                            const SizedBox(height: 16),
                            LabeledTextField(
                              controller: _email,
                              label: 'Email',
                              hintText: 'Enter email',
                              keyboardType: TextInputType.emailAddress,
                              validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                            ),
                            const SizedBox(height: 16),
                            LabeledTextField(
                              controller: _mobile,
                              label: 'Mobile Number',
                              hintText: 'Enter mobile number',
                              keyboardType: TextInputType.phone,
                              validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                            ),
                            const SizedBox(height: 16),
                            LabeledTextField(
                              controller: _alternate,
                              label: 'Alternate Number',
                              hintText: 'Optional',
                              keyboardType: TextInputType.phone,
                            ),
                            const SizedBox(height: 22),
                            Text('Services', style: AppStyles.heading2),
                            const SizedBox(height: 12),
                            OutlinedButton.icon(
                              onPressed: _serviceTypes.isEmpty ? null : _pickServices,
                              icon: const Icon(Icons.medical_services_outlined),
                              label: Text(_serviceTypeIds.isEmpty ? 'Select services' : 'Selected: ${_serviceTypeIds.length}'),
                            ),
                            const SizedBox(height: 22),
                            Text('Working Hours', style: AppStyles.heading2),
                            const SizedBox(height: 12),
                            Row(
                              children: [
                                Expanded(
                                  child: OutlinedButton(
                                    onPressed: () => _pickTime(isOpening: true),
                                    child: Text(_opening == null ? 'Opening Time' : _formatTime(_opening)!),
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: OutlinedButton(
                                    onPressed: () => _pickTime(isOpening: false),
                                    child: Text(_closing == null ? 'Closing Time' : _formatTime(_closing)!),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 16),
                            Text('Available Days', style: AppStyles.sectionTitle),
                            const SizedBox(height: 8),
                            _DaysPicker(
                              selected: _availableDays,
                              onToggle: (day, on) {
                                setState(() {
                                  if (on) {
                                    _availableDays.add(day);
                                  } else {
                                    _availableDays.remove(day);
                                  }
                                  if (_weeklyOff != null && _availableDays.contains(_weeklyOff)) {
                                    _availableDays.remove(_weeklyOff);
                                  }
                                });
                              },
                            ),
                            const SizedBox(height: 16),
                            DropdownButtonFormField<String?>(
                              initialValue: _weeklyOff,
                              decoration: const InputDecoration(labelText: 'Weekly Off'),
                              items: const [
                                DropdownMenuItem<String?>(value: null, child: Text('None')),
                                DropdownMenuItem<String?>(value: 'monday', child: Text('Monday')),
                                DropdownMenuItem<String?>(value: 'tuesday', child: Text('Tuesday')),
                                DropdownMenuItem<String?>(value: 'wednesday', child: Text('Wednesday')),
                                DropdownMenuItem<String?>(value: 'thursday', child: Text('Thursday')),
                                DropdownMenuItem<String?>(value: 'friday', child: Text('Friday')),
                                DropdownMenuItem<String?>(value: 'saturday', child: Text('Saturday')),
                                DropdownMenuItem<String?>(value: 'sunday', child: Text('Sunday')),
                              ],
                              onChanged: (v) {
                                setState(() {
                                  _weeklyOff = v;
                                  if (v != null) _availableDays.remove(v);
                                });
                              },
                            ),
                            const SizedBox(height: 22),
                            Text('Authorized Person Details', style: AppStyles.heading2),
                            const SizedBox(height: 16),
                            LabeledTextField(
                              controller: _authorizedName,
                              label: 'Name',
                              hintText: 'Authorized person name',
                              validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                            ),
                            const SizedBox(height: 16),
                            DropdownButtonFormField<String>(
                              initialValue: _authorizedRole,
                              decoration: const InputDecoration(labelText: 'Role'),
                              items: const [
                                DropdownMenuItem(value: 'center_head', child: Text('Center Head')),
                                DropdownMenuItem(value: 'lab_director', child: Text('Lab Director')),
                                DropdownMenuItem(value: 'manager', child: Text('Manager')),
                                DropdownMenuItem(value: 'administrator', child: Text('Administrator')),
                                DropdownMenuItem(value: 'coordinator', child: Text('Coordinator')),
                                DropdownMenuItem(value: 'reception_head', child: Text('Reception Head')),
                                DropdownMenuItem(value: 'owner', child: Text('Owner')),
                                DropdownMenuItem(value: 'other', child: Text('Other')),
                              ],
                              onChanged: (v) => setState(() => _authorizedRole = v),
                              validator: (v) => (v == null || v.isEmpty) ? 'Required' : null,
                            ),
                            const SizedBox(height: 16),
                            LabeledTextField(
                              controller: _authorizedMobile,
                              label: 'Mobile',
                              hintText: 'Authorized person mobile',
                              keyboardType: TextInputType.phone,
                              validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                            ),
                            const SizedBox(height: 16),
                            LabeledTextField(
                              controller: _authorizedEmail,
                              label: 'Email',
                              hintText: 'Authorized person email',
                              keyboardType: TextInputType.emailAddress,
                              validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                            ),
                            const SizedBox(height: 22),
                            Text('Security', style: AppStyles.heading2),
                            const SizedBox(height: 16),
                            LabeledPasswordField(
                              controller: _password,
                              label: 'Create Password',
                              hintText: 'Minimum 6 characters',
                            ),
                            if (_subscriptionPaymentsEnabled) ...[
                              const SizedBox(height: 24),
                              Text('Subscription Plan', style: AppStyles.heading2),
                              const SizedBox(height: 8),
                              Text('Diagnostic center sathi available plans madhun ek plan select kara.', style: AppStyles.bodySmall),
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
                                  child: Text('Admin ne ajun plans configure kele nahi.', style: AppStyles.bodySmall),
                                ),
                              ..._subscriptionPlans.map((plan) {
                                final id = (plan['id'] as int?) ?? int.tryParse('${plan['id']}');
                                return Padding(
                                  padding: const EdgeInsets.only(bottom: 12),
                                  child: _DiagnosticPlanCard(
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
                            ],
                            const SizedBox(height: 16),
                            TermsConsentCheckbox(
                              value: _termsAccepted,
                              onChanged: (v) => setState(() => _termsAccepted = v ?? false),
                            ),
                            const SizedBox(height: 12),
                            PrimaryButton(
                              label: _submitting ? 'Creating…' : 'Create Diagnostic Profile',
                              onPressed: _submitting ? null : _submit,
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

class _DiagnosticPlanCard extends StatelessWidget {
  final String title;
  final String subtitle;
  final String priceLabel;
  final String? description;
  final bool selected;
  final VoidCallback? onTap;

  const _DiagnosticPlanCard({
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

class _DaysPicker extends StatelessWidget {
  final Set<String> selected;
  final void Function(String day, bool on) onToggle;
  const _DaysPicker({required this.selected, required this.onToggle});

  @override
  Widget build(BuildContext context) {
    const days = [
      ('monday', 'Monday'),
      ('tuesday', 'Tuesday'),
      ('wednesday', 'Wednesday'),
      ('thursday', 'Thursday'),
      ('friday', 'Friday'),
      ('saturday', 'Saturday'),
      ('sunday', 'Sunday'),
    ];
    return Column(
      children: days
          .map((d) => CheckboxListTile(
                value: selected.contains(d.$1),
                onChanged: (v) => onToggle(d.$1, v == true),
                title: Text(d.$2),
                contentPadding: EdgeInsets.zero,
              ))
          .toList(),
    );
  }
}
