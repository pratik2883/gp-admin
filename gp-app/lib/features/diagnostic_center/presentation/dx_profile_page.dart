import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_card.dart';
import 'package:gp_app/ui/widgets/app_hero_header.dart';
import 'package:gp_app/ui/widgets/labeled_text_field.dart';
import 'package:gp_app/ui/widgets/primary_button.dart';
import 'package:gp_app/ui/widgets/google_address_autocomplete_field.dart';

class DxProfilePage extends ConsumerStatefulWidget {
  const DxProfilePage({super.key});

  @override
  ConsumerState<DxProfilePage> createState() => _DxProfilePageState();
}

class _DxProfilePageState extends ConsumerState<DxProfilePage> {
  final _centerName = TextEditingController();
  final _address = TextEditingController();
  final _microArea = TextEditingController();
  final _email = TextEditingController();
  final _mobile = TextEditingController();
  final _alternate = TextEditingController();

  final _authorizedName = TextEditingController();
  final _authorizedMobile = TextEditingController();
  final _authorizedEmail = TextEditingController();

  String? _centerType;
  int? _locationId;
  TimeOfDay? _opening;
  TimeOfDay? _closing;
  final Set<String> _availableDays = {};
  String? _weeklyOff;
  String? _authorizedRole;
  final Set<int> _serviceTypeIds = {};

  bool _loading = true;
  bool _saving = false;
  bool _addressAutocompleteEnabled = false;
  String? _googlePlacesApiKey;
  String _googlePlacesCountryCode = 'IN';
  List<Map<String, dynamic>> _locations = [];
  List<Map<String, dynamic>> _serviceTypes = [];

  @override
  void initState() {
    super.initState();
    Future.microtask(_loadAll);
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
    super.dispose();
  }

  Future<void> _loadAll() async {
    try {
      final repo = ref.read(diagnosticCenterRepositoryProvider);
      final loc = await repo.listLocations();
      final types = await repo.listServiceTypes();
      final profile = await repo.fetchProfile();
      final flags = await ref.read(publicFeatureFlagsProvider.future);
      if (!mounted) return;

      setState(() {
        _locations = loc;
        _serviceTypes = types;
        _addressAutocompleteEnabled = flags['enable_google_address_autocomplete'] == true;
        _googlePlacesApiKey = flags['google_places_api_key']?.toString();
        _googlePlacesCountryCode = (flags['google_places_country_code']?.toString().trim().isNotEmpty ?? false)
            ? flags['google_places_country_code'].toString()
            : 'IN';
        _centerName.text = (profile['center_name'] ?? profile['name'] ?? '').toString();
        _centerType = profile['center_type']?.toString();
        _locationId = profile['location_id'] is int ? profile['location_id'] as int : int.tryParse('${profile['location_id']}');
        _address.text = (profile['address'] ?? '').toString();
        _microArea.text = (profile['micro_area'] ?? '').toString();
        _email.text = (profile['email'] ?? '').toString();
        _mobile.text = (profile['mobile_number'] ?? '').toString();
        _alternate.text = (profile['alternate_number'] ?? '').toString();

        _weeklyOff = profile['weekly_off']?.toString();
        _availableDays
          ..clear()
          ..addAll(((profile['available_days'] as List?) ?? const []).map((e) => e.toString()));

        _authorizedName.text = (profile['authorized_person_name'] ?? '').toString();
        _authorizedRole = profile['authorized_person_role']?.toString();
        _authorizedMobile.text = (profile['authorized_person_mobile'] ?? '').toString();
        _authorizedEmail.text = (profile['authorized_person_email'] ?? '').toString();

        final services = ((profile['service_type_ids'] as List?) ?? (profile['services'] as List?) ?? const [])
            .map((e) => int.tryParse('$e'))
            .whereType<int>()
            .toSet();
        _serviceTypeIds
          ..clear()
          ..addAll(services);

        _opening = _parseTimeOfDay(profile['opening_time']?.toString());
        _closing = _parseTimeOfDay(profile['closing_time']?.toString());
        _loading = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _loading = false);
    }
  }

  TimeOfDay? _parseTimeOfDay(String? hhmm) {
    final t = (hhmm ?? '').trim();
    if (t.isEmpty) return null;
    final parts = t.split(':');
    if (parts.length < 2) return null;
    final h = int.tryParse(parts[0]);
    final m = int.tryParse(parts[1]);
    if (h == null || m == null) return null;
    return TimeOfDay(hour: h, minute: m);
  }

  Future<void> _pickTime({required bool isOpening}) async {
    final initial = isOpening ? (_opening ?? const TimeOfDay(hour: 9, minute: 0)) : (_closing ?? const TimeOfDay(hour: 18, minute: 0));
    final picked = await showTimePicker(context: context, initialTime: initial);
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

  Future<void> _save() async {
    final required = <String, String>{
      'Center Name': _centerName.text.trim(),
      'Address': _address.text.trim(),
      'Micro Area': _microArea.text.trim(),
      'Email': _email.text.trim(),
      'Mobile Number': _mobile.text.trim(),
      'Authorized Name': _authorizedName.text.trim(),
      'Authorized Mobile': _authorizedMobile.text.trim(),
      'Authorized Email': _authorizedEmail.text.trim(),
    };
    final missing = required.entries.where((e) => e.value.isEmpty).map((e) => e.key).toList();
    if (_centerType == null) missing.add('Center Type');
    if (_locationId == null) missing.add('City / Location');
    if (_opening == null) missing.add('Opening Time');
    if (_closing == null) missing.add('Closing Time');
    if (_authorizedRole == null) missing.add('Authorized Role');
    if (_serviceTypeIds.isEmpty) missing.add('Services');
    if (_weeklyOff != null && _availableDays.contains(_weeklyOff)) missing.add('Weekly Off');
    if (missing.isNotEmpty) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Please fill: ${missing.join(', ')}')));
      }
      return;
    }

    setState(() => _saving = true);
    try {
      await ref.read(diagnosticCenterRepositoryProvider).updateProfile({
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
      });
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Profile updated')));
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Failed to update')));
    } finally {
      if (mounted) setState(() => _saving = false);
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
            title: 'Diagnostic Center Profile',
            subtitle: 'Manage your center details',
          ),
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator(color: AppColors.secondaryTeal))
                : SingleChildScrollView(
                    padding: AppSpacing.screenPadding.copyWith(top: 18, bottom: 40),
                    child: Column(
                      children: [
                        AppCard(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('Basic Center Details', style: AppStyles.heading2),
                              const SizedBox(height: 16),
                              LabeledTextField(controller: _centerName, label: 'Center Name', hintText: 'Center name'),
                              const SizedBox(height: 16),
                              DropdownButtonFormField<String>(
                                key: ValueKey(_centerType ?? 'center_type'),
                                initialValue: _centerType,
                                decoration: const InputDecoration(labelText: 'Center Type'),
                                items: const [
                                  DropdownMenuItem(value: 'lab', child: Text('Lab / Pathology')),
                                  DropdownMenuItem(value: 'imaging', child: Text('Radiology / Imaging')),
                                  DropdownMenuItem(value: 'cardiology', child: Text('Cardiology Diagnostics')),
                                  DropdownMenuItem(value: 'multi', child: Text('Multi-service Center')),
                                ],
                                onChanged: (v) => setState(() => _centerType = v),
                              ),
                              const SizedBox(height: 16),
                              DropdownButtonFormField<int>(
                                key: ValueKey(_locationId ?? 0),
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
                              LabeledTextField(controller: _address, label: 'Address', hintText: 'Address'),
                              const SizedBox(height: 16),
                              LabeledTextField(controller: _microArea, label: 'Area / Micro Area', hintText: 'Area'),
                              const SizedBox(height: 22),
                              Text('Contact Details', style: AppStyles.heading2),
                              const SizedBox(height: 16),
                              LabeledTextField(controller: _email, label: 'Email', hintText: 'Email', keyboardType: TextInputType.emailAddress),
                              const SizedBox(height: 16),
                              LabeledTextField(controller: _mobile, label: 'Mobile Number', hintText: 'Mobile', keyboardType: TextInputType.phone),
                              const SizedBox(height: 16),
                              LabeledTextField(controller: _alternate, label: 'Alternate Number', hintText: 'Optional', keyboardType: TextInputType.phone),
                            ],
                          ),
                        ),
                        const SizedBox(height: 16),
                        AppCard(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
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
                                key: ValueKey(_weeklyOff ?? 'weekly_off'),
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
                            ],
                          ),
                        ),
                        const SizedBox(height: 16),
                        AppCard(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('Authorized Person Details', style: AppStyles.heading2),
                              const SizedBox(height: 16),
                              LabeledTextField(controller: _authorizedName, label: 'Name', hintText: 'Name'),
                              const SizedBox(height: 16),
                              DropdownButtonFormField<String>(
                                key: ValueKey(_authorizedRole ?? 'authorized_role'),
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
                              ),
                              const SizedBox(height: 16),
                              LabeledTextField(controller: _authorizedMobile, label: 'Mobile', hintText: 'Mobile', keyboardType: TextInputType.phone),
                              const SizedBox(height: 16),
                              LabeledTextField(controller: _authorizedEmail, label: 'Email', hintText: 'Email', keyboardType: TextInputType.emailAddress),
                            ],
                          ),
                        ),
                        const SizedBox(height: 22),
                        PrimaryButton(
                          label: _saving ? 'Saving…' : 'Save Changes',
                          onPressed: _saving ? null : _save,
                        ),
                        const SizedBox(height: 12),
                        OutlinedButton.icon(
                          onPressed: () => context.push('/support'),
                          icon: const Icon(Icons.support_agent_rounded),
                          label: const Text('Support Tickets'),
                        ),
                      ],
                    ),
                  ),
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
