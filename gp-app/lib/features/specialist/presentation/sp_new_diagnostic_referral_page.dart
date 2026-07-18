import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/core/http/dio_client.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_card.dart';
import 'package:gp_app/ui/widgets/app_hero_header.dart';
import 'package:gp_app/ui/widgets/app_segmented_control.dart';
import 'package:gp_app/ui/widgets/labeled_text_field.dart';
import 'package:gp_app/ui/widgets/primary_button.dart';
import 'package:gp_app/ui/widgets/section_header_row.dart';

class SpNewDiagnosticReferralPage extends ConsumerStatefulWidget {
  const SpNewDiagnosticReferralPage({super.key});

  @override
  ConsumerState<SpNewDiagnosticReferralPage> createState() => _SpNewDiagnosticReferralPageState();
}

class _SpNewDiagnosticReferralPageState extends ConsumerState<SpNewDiagnosticReferralPage> {
  final _formKey = GlobalKey<FormState>();
  final _patientName = TextEditingController();
  final _patientMobile = TextEditingController();
  final _ageCtrl = TextEditingController();
  final _notes = TextEditingController();

  String _gender = 'Male';
  String _visitType = 'opd';
  String _priority = 'routine';

  List<Map<String, dynamic>> _locations = [];
  String? _locationId;
  List<Map<String, dynamic>> _centers = [];
  String? _centerId;
  List<Map<String, dynamic>> _services = [];
  final Set<String> _serviceIds = {};
  final List<int> _serviceTypeFilterIds = [];

  bool _loadingLocations = true;
  bool _loadingCenters = false;
  bool _loadingServices = false;
  bool _submitting = false;
  String? _locationsError;
  String? _centersError;
  String? _servicesError;

  final List<PlatformFile> _selectedFiles = [];

  @override
  void initState() {
    super.initState();
    Future.microtask(_loadLocations);
  }

  @override
  void dispose() {
    _patientName.dispose();
    _patientMobile.dispose();
    _ageCtrl.dispose();
    _notes.dispose();
    super.dispose();
  }

  Future<void> _loadLocations() async {
    setState(() {
      _loadingLocations = true;
      _locationsError = null;
    });
    try {
      final resp = await ref.read(specialistRepositoryProvider).listDiagnosticLocations();
      if (!mounted) return;
      setState(() {
        _locations = resp.locations;
        _loadingLocations = false;
        _locationsError = null;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loadingLocations = false;
        _locationsError = extractApiErrorMessage(e, fallback: 'We couldn’t load diagnostic locations.');
      });
    }
  }

  Future<void> _loadCenters() async {
    if (_locationId == null || _locationId!.isEmpty) return;
    setState(() {
      _loadingCenters = true;
      _centersError = null;
    });
    try {
      final now = DateTime.now();
      final day = _dayKey(now);
      final at = DateFormat('HH:mm').format(now);
      final items = await ref.read(specialistRepositoryProvider).listDiagnosticCentersFiltered(
            locationId: _locationId!,
            serviceTypeIds: List<int>.from(_serviceTypeFilterIds),
            day: day,
            at: at,
          );
      if (!mounted) return;
      setState(() {
        _centers = items;
        _loadingCenters = false;
        _centersError = null;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loadingCenters = false;
        _centersError = extractApiErrorMessage(e, fallback: 'We couldn’t load diagnostic centers.');
      });
    }
  }

  Future<void> _loadServices() async {
    if (_centerId == null || _centerId!.isEmpty) return;
    setState(() {
      _loadingServices = true;
      _servicesError = null;
    });
    try {
      final items = await ref.read(specialistRepositoryProvider).listDiagnosticServices(_centerId!);
      if (!mounted) return;
      setState(() {
        _services = items;
        _loadingServices = false;
        _servicesError = null;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loadingServices = false;
        _servicesError = extractApiErrorMessage(e, fallback: 'We couldn’t load diagnostic services.');
      });
    }
  }

  String _dayKey(DateTime dt) {
    return switch (dt.weekday) {
      DateTime.monday => 'monday',
      DateTime.tuesday => 'tuesday',
      DateTime.wednesday => 'wednesday',
      DateTime.thursday => 'thursday',
      DateTime.friday => 'friday',
      DateTime.saturday => 'saturday',
      DateTime.sunday => 'sunday',
      _ => 'monday',
    };
  }

  Future<void> _pickServiceTypes(List<Map<String, dynamic>> items) async {
    final selected = Set<int>.from(_serviceTypeFilterIds);
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
                    Text('Filter by Services', style: AppStyles.heading2),
                    const SizedBox(height: 12),
                    Flexible(
                      child: ListView(
                        shrinkWrap: true,
                        children: items.map((t) {
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
      _serviceTypeFilterIds
        ..clear()
        ..addAll(result.toList()..sort());
      _centerId = null;
      _serviceIds.clear();
      _centers = [];
    });
    await _loadCenters();
  }

  Future<void> _pickFiles() async {
    final res = await FilePicker.platform.pickFiles(
      allowMultiple: true,
      type: FileType.custom,
      allowedExtensions: ['pdf', 'dcm', 'dicom'],
    );
    if (!mounted) return;
    if (res == null) return;
    setState(() {
      _selectedFiles.addAll(res.files.where((f) => f.path != null));
    });
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_locationId == null || _locationId!.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please select a diagnostic location')),
      );
      return;
    }
    if (_centerId == null || _centerId!.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please select a diagnostic center')),
      );
      return;
    }
    if (_serviceIds.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please select at least one diagnostic service')),
      );
      return;
    }

    setState(() => _submitting = true);
    try {
      final fields = <String, dynamic>{
        'diagnostic_center_id': _centerId,
        'diagnostic_service_ids': _serviceIds.toList(),
        'priority': _priority,
        'patient_name': _patientName.text.trim(),
        'patient_mobile': _patientMobile.text.trim(),
        'patient_age': _ageCtrl.text.trim(),
        'patient_gender': _gender.toLowerCase(),
        'case_summary': _notes.text.trim(),
        'appointment_type': _visitType,
      };

      final filePaths = _selectedFiles.map((f) => f.path!).toList();
      await ref.read(specialistRepositoryProvider).createDiagnosticReferral(fields: fields, filePaths: filePaths);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Referral submitted successfully!')));
      Navigator.pop(context);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(extractApiErrorMessage(e, fallback: 'Failed to submit referral'))),
      );
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final serviceTypes = ref.watch(diagnosticServiceTypesProvider);

    return Scaffold(
      backgroundColor: AppColors.background,
      body: Column(
        children: [
          AppHeroHeader(
            leading: IconButton(
              onPressed: () => Navigator.pop(context),
              icon: const Icon(Icons.arrow_back_rounded, color: AppColors.textOnDark),
            ),
            title: 'New Diagnostic Referral',
            subtitle: 'Refer patient to a diagnostic center',
          ),
          Expanded(
            child: _loadingLocations
                ? const Center(child: CircularProgressIndicator(color: AppColors.secondaryTeal))
                : SingleChildScrollView(
                    padding: AppSpacing.screenPadding.copyWith(top: 18, bottom: 40),
                    child: Form(
                      key: _formKey,
                      child: Column(
                        children: [
                          AppCard(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const SectionHeaderRow(title: 'Patient Details'),
                                const SizedBox(height: 12),
                                LabeledTextField(
                                  controller: _patientName,
                                  label: 'Patient Name',
                                  hintText: 'Enter patient name',
                                  validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                                ),
                                const SizedBox(height: 12),
                                LabeledTextField(
                                  controller: _patientMobile,
                                  label: 'Patient Mobile',
                                  hintText: 'Enter mobile number',
                                  keyboardType: TextInputType.phone,
                                ),
                                const SizedBox(height: 12),
                                Row(
                                  children: [
                                    Expanded(
                                      child: LabeledTextField(
                                        controller: _ageCtrl,
                                        label: 'Age',
                                        hintText: 'Years',
                                        keyboardType: TextInputType.number,
                                      ),
                                    ),
                                    const SizedBox(width: 12),
                                    Expanded(
                                      child: FormField<String>(
                                        key: ValueKey('gender_$_gender'),
                                        initialValue: _gender,
                                        validator: (v) => v == null || v.isEmpty ? 'Required' : null,
                                        builder: (field) {
                                          const items = [
                                            {'id': 'Male', 'name': 'Male'},
                                            {'id': 'Female', 'name': 'Female'},
                                            {'id': 'Other', 'name': 'Other'},
                                          ];
                                          return Column(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              _PremiumSelectField(
                                                topLabel: 'Gender',
                                                value: field.value ?? 'Select gender',
                                                leadingIcon: _genderIcon(field.value),
                                                onTap: () async {
                                                  final picked = await _pickFromBottomSheet(
                                                    title: 'Select Gender',
                                                    items: items,
                                                    selectedId: field.value,
                                                    leadingIconBuilder: (e) => _genderIcon(e['id']?.toString()),
                                                  );
                                                  if (!mounted) return;
                                                  if (picked == null) return;
                                                  field.didChange(picked);
                                                  setState(() => _gender = picked);
                                                },
                                                isPlaceholder: field.value == null || field.value!.isEmpty,
                                              ),
                                              if (field.hasError) ...[
                                                const SizedBox(height: 8),
                                                Text(
                                                  field.errorText ?? '',
                                                  style: AppStyles.caption.copyWith(color: AppColors.statusError),
                                                ),
                                              ],
                                            ],
                                          );
                                        },
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 12),
                                LabeledTextField(
                                  controller: _notes,
                                  label: 'Case Summary',
                                  hintText: 'Describe the case',
                                  maxLines: 4,
                                  validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                                ),
                                const SizedBox(height: 12),
                                AppSegmentedControl<String>(
                                  value: _visitType,
                                  options: const [
                                    AppSegmentOption(value: 'opd', label: 'OPD', icon: Icons.local_hospital_outlined),
                                    AppSegmentOption(value: 'ipd', label: 'IPD', icon: Icons.hotel_rounded),
                                  ],
                                  onChanged: (v) => setState(() => _visitType = v),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: 16),
                          AppCard(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const SectionHeaderRow(title: 'Diagnostic Selection'),
                                const SizedBox(height: 12),
                                if (_locationsError != null)
                                  _retryInline(
                                    message: _locationsError!,
                                    onRetry: _loadLocations,
                                  )
                                else if (_locations.isEmpty)
                                  _inlineMessage('No diagnostic locations available right now.')
                                else
                                  FormField<String>(
                                    key: ValueKey('dx_location_${_locationId ?? 'none'}'),
                                    initialValue: _locationId,
                                    validator: (v) => v == null || v.isEmpty ? 'Select a location' : null,
                                    builder: (field) {
                                      final name = _nameFromId(_locations, field.value);
                                      return Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          _PremiumSelectField(
                                            topLabel: 'Location',
                                            value: name ?? 'Select location',
                                            leadingIcon: Icons.location_on_rounded,
                                            onTap: () async {
                                              final picked = await _pickFromBottomSheet(
                                                title: 'Select Location',
                                                items: _locations,
                                                selectedId: field.value,
                                                leadingIcon: Icons.location_on_rounded,
                                              );
                                              if (!mounted) return;
                                              if (picked == null) return;
                                              field.didChange(picked);
                                              setState(() {
                                                _locationId = picked;
                                                _centerId = null;
                                                _centers = [];
                                                _services = [];
                                                _serviceIds.clear();
                                                _centersError = null;
                                                _servicesError = null;
                                              });
                                              await _loadCenters();
                                            },
                                            isPlaceholder: name == null,
                                          ),
                                          if (field.hasError) ...[
                                            const SizedBox(height: 8),
                                            Text(
                                              field.errorText ?? '',
                                              style: AppStyles.caption.copyWith(color: AppColors.statusError),
                                            ),
                                          ],
                                        ],
                                      );
                                    },
                                  ),
                                const SizedBox(height: 12),
                                serviceTypes.when(
                                  data: (items) {
                                    final selectedTypes = items.where((t) {
                                      final id = (t['id'] as int?) ?? int.tryParse('${t['id']}');
                                      return id != null && _serviceTypeFilterIds.contains(id);
                                    }).toList();

                                    return Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        SizedBox(
                                          width: double.infinity,
                                          child: OutlinedButton.icon(
                                            onPressed: items.isEmpty ? null : () => _pickServiceTypes(items),
                                            icon: const Icon(Icons.filter_list_rounded),
                                            label: Text(
                                              _serviceTypeFilterIds.isEmpty
                                                  ? 'Filter diagnostic centers by service'
                                                  : 'Service filters: ${_serviceTypeFilterIds.length}',
                                            ),
                                            style: OutlinedButton.styleFrom(
                                              minimumSize: const Size(double.infinity, 52),
                                              alignment: Alignment.centerLeft,
                                              side: BorderSide(color: AppColors.primaryBlue.withAlpha(45)),
                                              shape: RoundedRectangleBorder(
                                                borderRadius: BorderRadius.circular(18),
                                              ),
                                            ),
                                          ),
                                        ),
                                        if (selectedTypes.isNotEmpty) ...[
                                          const SizedBox(height: 10),
                                          Wrap(
                                            spacing: 8,
                                            runSpacing: 8,
                                            children: selectedTypes.map((t) {
                                              final label = (t['label'] ?? t['name'] ?? '').toString();
                                              return Chip(
                                                label: Text(label),
                                                visualDensity: VisualDensity.compact,
                                                backgroundColor: AppColors.primaryBlue.withAlpha(12),
                                                side: BorderSide(color: AppColors.primaryBlue.withAlpha(30)),
                                              );
                                            }).toList(),
                                          ),
                                        ],
                                      ],
                                    );
                                  },
                                  error: (err, __) => _inlineMessage(
                                    extractApiErrorMessage(err, fallback: 'Unable to load service filters right now.'),
                                  ),
                                  loading: () => const SizedBox.shrink(),
                                ),
                                const SizedBox(height: 12),
                                Builder(
                                  builder: (context) {
                                    if (_locationId == null || _locationId!.isEmpty) {
                                      return const _PremiumSelectField(
                                        topLabel: 'Diagnostic Center',
                                        value: 'Select a location first',
                                        leadingIcon: Icons.biotech_rounded,
                                        onTap: null,
                                        isPlaceholder: true,
                                      );
                                    }

                                    if (_centersError != null) {
                                      return _retryInline(
                                        message: _centersError!,
                                        onRetry: _loadCenters,
                                      );
                                    }

                                    if (_centers.isEmpty && !_loadingCenters) {
                                      return AppCard(
                                        color: AppColors.surfaceMuted,
                                        border: Border.all(color: AppColors.borderLight),
                                        padding: const EdgeInsets.all(14),
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              'No diagnostic centers match the selected location and service filters.',
                                              style: AppStyles.bodySmall.copyWith(color: AppColors.textSecondary),
                                            ),
                                            if (_serviceTypeFilterIds.isNotEmpty) ...[
                                              const SizedBox(height: 8),
                                              TextButton(
                                                onPressed: () async {
                                                  setState(() {
                                                    _serviceTypeFilterIds.clear();
                                                    _centerId = null;
                                                    _serviceIds.clear();
                                                    _centers = [];
                                                  });
                                                  await _loadCenters();
                                                },
                                                child: const Text('Clear filters'),
                                              ),
                                            ],
                                          ],
                                        ),
                                      );
                                    }

                                    return FormField<String>(
                                      key: ValueKey('dx_center_${_centerId ?? 'none'}'),
                                      initialValue: _centerId,
                                      validator: (v) => v == null || v.isEmpty ? 'Select a diagnostic center' : null,
                                      builder: (field) {
                                        final selected = _centers.cast<Map<String, dynamic>>().where((c) => '${c['id']}' == field.value).toList();
                                        final c = selected.isNotEmpty ? selected.first : null;
                                        final name = (c?['name']?.toString() ?? '').trim();
                                        final hint = [
                                          c?['micro_area']?.toString(),
                                          c?['address']?.toString(),
                                        ].whereType<String>().where((s) => s.trim().isNotEmpty).join(' · ');

                                        final value = _loadingCenters
                                            ? 'Loading centers...'
                                            : (name.isNotEmpty ? name : 'Select diagnostic center');

                                        return Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Stack(
                                              alignment: Alignment.centerRight,
                                              children: [
                                                _PremiumSelectField(
                                                  topLabel: 'Diagnostic Center',
                                                  value: value,
                                                  valueHint: hint.isEmpty ? null : hint,
                                                  leadingIcon: Icons.biotech_rounded,
                                                  onTap: _loadingCenters || _centers.isEmpty
                                                      ? null
                                                      : () async {
                                                          final picked = await _pickFromBottomSheet(
                                                            title: 'Select Diagnostic Center',
                                                            items: _centers,
                                                            selectedId: field.value,
                                                            labelBuilder: (e) => (e['name'] ?? '').toString(),
                                                            subtitleBuilder: (e) {
                                                              final hint = [
                                                                e['micro_area']?.toString(),
                                                                e['address']?.toString(),
                                                              ].whereType<String>().where((s) => s.trim().isNotEmpty).join(' · ');
                                                              return hint.isEmpty ? null : hint;
                                                            },
                                                            leadingIcon: Icons.biotech_rounded,
                                                          );
                                                          if (!mounted) return;
                                                          if (picked == null) return;
                                                          field.didChange(picked);
                                                          setState(() {
                                                            _centerId = picked;
                                                            _services = [];
                                                            _serviceIds.clear();
                                                            _servicesError = null;
                                                          });
                                                          await _loadServices();
                                                        },
                                                  isPlaceholder: name.isEmpty,
                                                ),
                                                if (_loadingCenters)
                                                  const Padding(
                                                    padding: EdgeInsets.only(right: 16),
                                                    child: SizedBox(
                                                      width: 18,
                                                      height: 18,
                                                      child: CircularProgressIndicator(
                                                        strokeWidth: 2,
                                                        color: AppColors.secondaryTeal,
                                                      ),
                                                    ),
                                                  ),
                                              ],
                                            ),
                                            if (field.hasError) ...[
                                              const SizedBox(height: 8),
                                              Text(
                                                field.errorText ?? '',
                                                style: AppStyles.caption.copyWith(color: AppColors.statusError),
                                              ),
                                            ],
                                          ],
                                        );
                                      },
                                    );
                                  },
                                ),
                                const SizedBox(height: 14),
                                Builder(
                                  builder: (context) {
                                    if (_centerId == null || _centerId!.isEmpty) {
                                      return _inlineMessage('Select a diagnostic center to see available services.');
                                    }
                                    if (_loadingServices) {
                                      return const Center(child: CircularProgressIndicator(color: AppColors.secondaryTeal));
                                    }
                                    if (_servicesError != null) {
                                      return _retryInline(
                                        message: _servicesError!,
                                        onRetry: _loadServices,
                                      );
                                    }
                                    if (_services.isEmpty) {
                                      return _inlineMessage('No diagnostic services available for this center.');
                                    }

                                    return Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text('Services', style: AppStyles.sectionTitle),
                                        const SizedBox(height: 10),
                                        Wrap(
                                          spacing: 8,
                                          runSpacing: 8,
                                          children: _services.map((s) {
                                            final id = s['id']?.toString();
                                            if (id == null) return const SizedBox.shrink();
                                            final name = (s['name'] ?? s['service_name'])?.toString() ?? 'Service';
                                            return FilterChip(
                                              label: Text(name),
                                              selected: _serviceIds.contains(id),
                                              onSelected: (selected) {
                                                setState(() {
                                                  if (selected) {
                                                    _serviceIds.add(id);
                                                  } else {
                                                    _serviceIds.remove(id);
                                                  }
                                                });
                                              },
                                            );
                                          }).toList(),
                                        ),
                                      ],
                                    );
                                  },
                                ),
                                const SizedBox(height: 16),
                                Text('Priority', style: AppStyles.sectionTitle),
                                const SizedBox(height: 10),
                                AppSegmentedControl<String>(
                                  value: _priority,
                                  options: const [
                                    AppSegmentOption(value: 'routine', label: 'Routine', icon: Icons.event_available_rounded),
                                    AppSegmentOption(value: 'urgent', label: 'Urgent', icon: Icons.priority_high_rounded),
                                  ],
                                  onChanged: (v) => setState(() => _priority = v),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: 16),
                          AppCard(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const SectionHeaderRow(title: 'Attachments'),
                                const SizedBox(height: 12),
                                OutlinedButton.icon(
                                  onPressed: _pickFiles,
                                  icon: const Icon(Icons.cloud_upload_outlined),
                                  label: const Text('Upload PDF or DICOM'),
                                  style: OutlinedButton.styleFrom(minimumSize: const Size(double.infinity, 52)),
                                ),
                                if (_selectedFiles.isNotEmpty) ...[
                                  const SizedBox(height: 10),
                                  Text('${_selectedFiles.length} files selected', style: AppStyles.caption),
                                  const SizedBox(height: 8),
                                  ..._selectedFiles.map(
                                    (file) => Padding(
                                      padding: const EdgeInsets.only(bottom: 6),
                                      child: Row(
                                        children: [
                                          const Icon(Icons.insert_drive_file_outlined, size: 16, color: AppColors.textGrey),
                                          const SizedBox(width: 8),
                                          Expanded(
                                            child: Text(
                                              file.name,
                                              style: AppStyles.caption.copyWith(color: AppColors.textPrimary),
                                              overflow: TextOverflow.ellipsis,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ),
                                ],
                              ],
                            ),
                          ),
                          const SizedBox(height: 22),
                          PrimaryButton(
                            label: _submitting ? 'Submitting…' : 'Submit Referral',
                            onPressed: _submitting ? null : _submit,
                          ),
                        ],
                      ),
                    ),
                  ),
          ),
        ],
      ),
    );
  }

  String? _nameFromId(List<Map<String, dynamic>> items, String? id) {
    if (id == null || id.isEmpty) return null;
    for (final e in items) {
      if ('${e['id']}' == id) {
        final name = e['name']?.toString().trim();
        if (name != null && name.isNotEmpty) return name;
      }
    }
    return null;
  }

  Widget _inlineMessage(String message) {
    return AppCard(
      color: AppColors.surfaceMuted,
      border: Border.all(color: AppColors.borderLight),
      padding: const EdgeInsets.all(14),
      child: Text(
        message,
        style: AppStyles.bodySmall.copyWith(color: AppColors.textSecondary),
      ),
    );
  }

  Widget _retryInline({
    required String message,
    required Future<void> Function() onRetry,
  }) {
    return AppCard(
      color: AppColors.surfaceMuted,
      border: Border.all(color: AppColors.borderLight),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            message,
            style: AppStyles.bodySmall.copyWith(color: AppColors.textSecondary),
          ),
          const SizedBox(height: 10),
          OutlinedButton(
            onPressed: () => onRetry(),
            child: const Text('Retry'),
          ),
        ],
      ),
    );
  }

  IconData _genderIcon(String? gender) {
    final g = (gender ?? '').trim().toLowerCase();
    if (g == 'male') return Icons.male_rounded;
    if (g == 'female') return Icons.female_rounded;
    if (g == 'other') return Icons.transgender_rounded;
    return Icons.wc_rounded;
  }

  Future<String?> _pickFromBottomSheet({
    required String title,
    required List<Map<String, dynamic>> items,
    required String? selectedId,
    String Function(Map<String, dynamic>)? labelBuilder,
    String? Function(Map<String, dynamic>)? subtitleBuilder,
    IconData? leadingIcon,
    IconData Function(Map<String, dynamic>)? leadingIconBuilder,
  }) async {
    if (items.isEmpty) return null;

    return showModalBottomSheet<String>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) {
        return SafeArea(
          child: Container(
            decoration: const BoxDecoration(
              color: AppColors.surface,
              borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
            ),
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 10, 16, 16),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 44,
                    height: 4,
                    decoration: BoxDecoration(
                      color: AppColors.border,
                      borderRadius: BorderRadius.circular(999),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(child: Text(title, style: AppStyles.heading2)),
                      IconButton(
                        onPressed: () => Navigator.pop(context),
                        icon: const Icon(Icons.close_rounded),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Flexible(
                    child: ListView.separated(
                      shrinkWrap: true,
                      itemCount: items.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 10),
                      itemBuilder: (context, i) {
                        final e = items[i];
                        final id = '${e['id']}';
                        final name = (labelBuilder != null ? labelBuilder(e) : (e['name'] ?? '').toString()).trim();
                        final sub = subtitleBuilder != null ? (subtitleBuilder(e) ?? '').trim() : '';
                        if (id.isEmpty || name.isEmpty) return const SizedBox.shrink();
                        final selected = id == selectedId;
                        return AppCard(
                          onTap: () => Navigator.pop(context, id),
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                          color: selected ? AppColors.primaryBlue.withAlpha(14) : AppColors.surfaceMuted,
                          borderRadius: AppStyles.radiusButton,
                          boxShadow: const [
                            BoxShadow(
                              color: Color(0x080E1A2B),
                              blurRadius: 16,
                              offset: Offset(0, 10),
                            ),
                          ],
                          child: Row(
                            children: [
                              Container(
                                width: 36,
                                height: 36,
                                decoration: BoxDecoration(
                                  gradient: selected ? AppColors.heroGradient : null,
                                  color: selected ? null : AppColors.inputBackground,
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Icon(
                                  (leadingIconBuilder != null ? leadingIconBuilder(e) : null) ??
                                      leadingIcon ??
                                      Icons.location_on_rounded,
                                  color: selected ? Colors.white : AppColors.primaryBlue,
                                  size: 18,
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      name,
                                      style: AppStyles.bodyLarge.copyWith(
                                        fontWeight: FontWeight.w800,
                                        color: selected ? AppColors.primaryBlue : AppColors.textPrimary,
                                      ),
                                      maxLines: 2,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                    if (sub.isNotEmpty) ...[
                                      const SizedBox(height: 2),
                                      Text(
                                        sub,
                                        style: AppStyles.caption.copyWith(color: AppColors.textSecondary),
                                        maxLines: 2,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ],
                                  ],
                                ),
                              ),
                              const SizedBox(width: 10),
                              Icon(
                                selected ? Icons.check_circle_rounded : Icons.chevron_right_rounded,
                                color: selected ? AppColors.secondaryTeal : AppColors.textMuted,
                              ),
                            ],
                          ),
                        );
                      },
                    ),
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }
}

class _PremiumSelectField extends StatelessWidget {
  final String topLabel;
  final String value;
  final String? valueHint;
  final IconData leadingIcon;
  final VoidCallback? onTap;
  final bool isPlaceholder;

  const _PremiumSelectField({
    required this.topLabel,
    required this.value,
    this.valueHint,
    required this.leadingIcon,
    required this.onTap,
    required this.isPlaceholder,
  });

  @override
  Widget build(BuildContext context) {
    final disabled = onTap == null;

    return Opacity(
      opacity: disabled ? 0.62 : 1,
      child: AppCard(
        onTap: onTap,
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        color: AppColors.surfaceMuted,
        borderRadius: AppStyles.radiusButton,
        boxShadow: const [
          BoxShadow(
            color: Color(0x080E1A2B),
            blurRadius: 18,
            offset: Offset(0, 10),
          ),
        ],
        child: Row(
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                gradient: disabled ? null : AppColors.heroGradient,
                color: disabled ? AppColors.inputBackground : null,
                borderRadius: BorderRadius.circular(14),
              ),
              child: Icon(
                leadingIcon,
                color: disabled ? AppColors.textMuted : Colors.white,
                size: 20,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    topLabel,
                    style: AppStyles.caption.copyWith(color: AppColors.textSecondary),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 2),
                  Text(
                    value,
                    style: AppStyles.bodyLarge.copyWith(
                      fontWeight: FontWeight.w800,
                      color: isPlaceholder ? AppColors.textMuted : AppColors.textPrimary,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  if (valueHint != null && valueHint!.trim().isNotEmpty) ...[
                    const SizedBox(height: 2),
                    Text(
                      valueHint!,
                      style: AppStyles.caption.copyWith(color: AppColors.textSecondary),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ],
              ),
            ),
            const SizedBox(width: 8),
            Icon(
              disabled ? Icons.lock_outline_rounded : Icons.expand_more_rounded,
              color: AppColors.textMuted,
            ),
          ],
        ),
      ),
    );
  }
}
