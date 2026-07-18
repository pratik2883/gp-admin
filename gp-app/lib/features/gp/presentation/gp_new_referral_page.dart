import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:file_picker/file_picker.dart';
import 'package:intl/intl.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/core/http/dio_client.dart';
import 'package:gp_app/core/storage/secure_storage.dart';
import 'package:gp_app/ui/widgets/labeled_text_field.dart';
import 'package:gp_app/ui/widgets/primary_button.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/features/gp/data/gp_repository.dart';
import 'package:gp_app/features/gp/state/new_referral_state.dart';
import 'package:gp_app/ui/widgets/app_hero_header.dart';
import 'package:gp_app/ui/widgets/app_card.dart';
import 'package:gp_app/ui/widgets/section_header_row.dart';
import 'package:gp_app/ui/widgets/app_segmented_control.dart';
import 'package:gp_app/ui/widgets/app_attachment_list.dart';

enum ReferralType { specialist, diagnostic, hospital }

class GpNewReferralPage extends ConsumerStatefulWidget {
  final String? preselectedSpecialistId;
  final int? preselectedLocationId;
  final String? preselectedCategoryCode;
  final String? preselectedAreaName;
  final String? preselectedCategoryName;
  final ReferralType? initialReferralType;
  final bool hospitalOnlyMode;
  final bool submitHospitalAsSpecialist;

  const GpNewReferralPage({
    super.key,
    this.preselectedSpecialistId,
    this.preselectedLocationId,
    this.preselectedCategoryCode,
    this.preselectedAreaName,
    this.preselectedCategoryName,
    this.initialReferralType,
    this.hospitalOnlyMode = false,
    this.submitHospitalAsSpecialist = false,
  });

  @override
  ConsumerState<GpNewReferralPage> createState() => _GpNewReferralPageState();
}

class _GpNewReferralPageState extends ConsumerState<GpNewReferralPage> {
  final _formKey = GlobalKey<FormState>();
  final _patientName = TextEditingController();
  final _patientMobile = TextEditingController();
  final _ageCtrl = TextEditingController();
  final _notes = TextEditingController();
  final _hospitalDepartmentCtrl = TextEditingController();

  late final ProviderSubscription<AsyncValue<DiagnosticLocationsResponse>>
      _diagnosticLocationsSub;

  ReferralType _referralType = ReferralType.specialist;
  String _gender = 'Male';
  List<Map<String, dynamic>> _locations = [];
  List<Map<String, dynamic>> _categories = [];
  List<Map<String, dynamic>> _specialists = [];

  String? _locationId;
  String? _categoryId;
  String? _specialistId;
  String? _specialistHospitalId;
  String? _specialistHospitalName;

  String? _diagnosticLocationId;
  String? _diagnosticCenterId;
  final Set<String> _diagnosticServiceIds = {};
  final List<int> _diagnosticServiceTypeFilterIds = [];
  String _diagnosticPriority = 'routine';
  bool _diagnosticDefaultApplied = false;

  String? _hospitalLocationId;
  List<Map<String, dynamic>> _hospitalLocations = [];
  List<Map<String, dynamic>> _hospitals = [];
  String? _hospitalId;
  List<String> _hospitalDepartments = [];
  String? _hospitalDepartment;
  String _hospitalPriority = 'routine';
  bool _loadingHospitalLocations = false;
  bool _loadingHospitals = false;
  bool _loadingHospitalDepartments = false;

  bool _loadingLocations = true;
  bool _loadingCategories = false;
  bool _loadingSpecialists = false;

  String _visitType = 'opd';
  String? _selectedIpdHospitalId;

  final List<PlatformFile> _selectedFiles = [];
  Timer? _draftDebounceTimer;

  String get _draftKey => widget.submitHospitalAsSpecialist
      ? 'specialist_hospital_referral_draft'
      : 'referral_draft';

  @override
  void initState() {
    super.initState();
    if (widget.initialReferralType != null) {
      _referralType = widget.initialReferralType!;
    }
    _fetchLocations().then((_) => _checkAndLoadDraft());

    // Add listeners for auto-save
    _patientName.addListener(_saveDraft);
    _patientMobile.addListener(_saveDraft);
    _ageCtrl.addListener(_saveDraft);
    _notes.addListener(_saveDraft);

    _diagnosticLocationsSub =
        ref.listenManual(diagnosticLocationsProvider, (previous, next) {
      next.whenData((locations) {
        if (!mounted) return;
        if (_referralType != ReferralType.diagnostic) return;
        if (_diagnosticDefaultApplied) return;
        _applyDiagnosticDefaultLocationIfNeeded(locations);

        _diagnosticDefaultApplied = true;
      });
    });
  }

  Future<void> _checkAndLoadDraft() async {
    final draftStr = await AppLocalStorage.instance.getString(_draftKey);
    if (draftStr == null) return;

    if (!mounted) return;

    final bool? load = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Resume draft?'),
        content: const Text(
            'We found an unfinished referral. Do you want to restore it?'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Discard')),
          ElevatedButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('Restore')),
        ],
      ),
    );

    if (load == true) {
      final Map<String, dynamic> data = jsonDecode(draftStr);

      final draftType = data['referral_type']?.toString().toLowerCase();
      final referralType = switch (draftType) {
        'diagnostic' => ReferralType.diagnostic,
        'hospital' => ReferralType.hospital,
        _ => ReferralType.specialist,
      };

      if (widget.hospitalOnlyMode && referralType != ReferralType.hospital) {
        await AppLocalStorage.instance.remove(_draftKey);
        return;
      }

      setState(() {
        _referralType = referralType;
        _patientName.text = data['patient_name'] ?? '';
        _patientMobile.text = data['patient_mobile'] ?? '';
        _ageCtrl.text = data['patient_age'] ?? '';
        _gender = data['patient_gender'] ?? 'Male';
        _notes.text = data['notes'] ?? '';
        _visitType = data['visit_type'] ?? 'opd';
        _selectedIpdHospitalId = data['selected_ipd_hospital_id']?.toString();

        _diagnosticLocationId = data['diagnostic_location_id']?.toString();
        _diagnosticCenterId = data['diagnostic_center_id']?.toString();
        _diagnosticPriority =
            data['diagnostic_priority']?.toString() ?? 'routine';
        _diagnosticServiceIds
          ..clear()
          ..addAll(((data['diagnostic_service_ids'] as List?) ?? const [])
              .map((e) => e.toString()));

        _hospitalLocationId = data['hospital_location_id']?.toString();
        _hospitalId = data['hospital_id']?.toString();
        _hospitalDepartment = data['hospital_department']?.toString();
        _hospitalPriority = data['hospital_priority']?.toString() ?? 'routine';
      });
      _hospitalDepartmentCtrl.text = _hospitalDepartment ?? '';

      if (referralType == ReferralType.specialist &&
          data['location_id'] != null) {
        await _onLocationChanged(data['location_id'].toString());
        if (data['category_id'] != null) {
          await _onCategoryChanged(data['category_id'].toString());
          if (mounted && data['specialist_id'] != null) {
            final spId = data['specialist_id'].toString();
            setState(() => _specialistId = spId);
            _syncHospitalForSpecialist(spId);
          }
        }
      }
      if (referralType == ReferralType.hospital &&
          data['hospital_location_id'] != null) {
        await _onHospitalLocationChanged(
            data['hospital_location_id'].toString());
        if (mounted && data['hospital_id'] != null) {
          await _onHospitalChanged(data['hospital_id'].toString());
        }
      }
    } else {
      await AppLocalStorage.instance.remove(_draftKey);
    }
  }

  void _saveDraft() {
    _draftDebounceTimer?.cancel();
    _draftDebounceTimer = Timer(const Duration(milliseconds: 500), () async {
      final draft = {
        'referral_type': _referralType.name,
        'patient_name': _patientName.text,
        'patient_mobile': _patientMobile.text,
        'patient_age': _ageCtrl.text,
        'patient_gender': _gender,
        'notes': _notes.text,
        'visit_type': _visitType,
        'location_id': _locationId,
        'category_id': _categoryId,
        'specialist_id': _specialistId,
        'selected_ipd_hospital_id': _selectedIpdHospitalId,
        'diagnostic_location_id': _diagnosticLocationId,
        'diagnostic_center_id': _diagnosticCenterId,
        'diagnostic_service_ids': _diagnosticServiceIds.toList(),
        'diagnostic_priority': _diagnosticPriority,
        'hospital_location_id': _hospitalLocationId,
        'hospital_id': _hospitalId,
        'hospital_department': _hospitalDepartmentCtrl.text,
        'hospital_priority': _hospitalPriority,
      };
      await AppLocalStorage.instance.setString(_draftKey, jsonEncode(draft));
    });
  }

  void _setReferralType(ReferralType type) {
    if (widget.hospitalOnlyMode) {
      _saveDraft();
      return;
    }
    setState(() => _referralType = type);
    if (type == ReferralType.diagnostic) {
      ref.read(diagnosticLocationsProvider).maybeWhen(
            data: _applyDiagnosticDefaultLocationIfNeeded,
            orElse: () {},
          );
    }
    if (type == ReferralType.hospital) {
      _ensureHospitalLocationsLoaded();
    }
    _saveDraft();
  }

  Future<void> _ensureHospitalLocationsLoaded() async {
    if (_hospitalLocations.isNotEmpty || _loadingHospitalLocations) return;
    setState(() => _loadingHospitalLocations = true);
    final repo = ref.read(gpRepositoryProvider);
    try {
      final locs = await repo.listHospitalLocationsForSender(
        asSpecialist: widget.submitHospitalAsSpecialist,
      );
      if (!mounted) return;
      setState(() {
        _hospitalLocations = locs;
        _loadingHospitalLocations = false;
      });
    } catch (_) {
      if (mounted) setState(() => _loadingHospitalLocations = false);
    }
  }

  void _applyDiagnosticDefaultLocationIfNeeded(
      DiagnosticLocationsResponse locations) {
    if (!mounted) return;
    if (_diagnosticLocationId != null && _diagnosticLocationId!.isNotEmpty)
      return;

    final defaultLocationId = locations.defaultLocationId ??
        ref.read(authStateProvider).maybeWhen(
              authenticated: (user) => user.defaultLocationId,
              orElse: () => null,
            );

    if (defaultLocationId == null) return;
    final exists = locations.locations
        .any((l) => l['id']?.toString() == defaultLocationId.toString());
    if (!exists) return;
    _onDiagnosticLocationChanged(defaultLocationId.toString());
  }

  void _onDiagnosticLocationChanged(String? id) {
    setState(() {
      _diagnosticLocationId = id;
      _diagnosticCenterId = null;
      _diagnosticServiceIds.clear();
    });
    _saveDraft();
  }

  void _onDiagnosticCenterChanged(String? id) {
    setState(() {
      _diagnosticCenterId = id;
      _diagnosticServiceIds.clear();
    });
    _saveDraft();
  }

  void _toggleDiagnosticService(String id, bool selected) {
    setState(() {
      if (selected) {
        _diagnosticServiceIds.add(id);
        return;
      }
      _diagnosticServiceIds.remove(id);
    });
    _saveDraft();
  }

  Future<void> _clearDraft() async {
    await AppLocalStorage.instance.remove(_draftKey);
  }

  Future<void> _fetchLocations() async {
    if (widget.hospitalOnlyMode) {
      if (mounted) {
        setState(() => _loadingLocations = false);
      }
      _ensureHospitalLocationsLoaded();
      return;
    }
    final repo = ref.read(gpRepositoryProvider);
    try {
      final locs = await repo.listLocations();
      if (mounted) {
        setState(() {
          _locations = locs;
          _loadingLocations = false;
        });

        // Robust Preselection Logic: Match by Name if IDs aren't known
        if (widget.preselectedSpecialistId != null) {
          _handlePreselection();
        }
      }
    } catch (_) {
      if (mounted) setState(() => _loadingLocations = false);
    }
  }

  Future<void> _handlePreselection() async {
    // 1. Find Location: Prefer ID, fallback to Name
    Map<String, dynamic>? loc;
    if (widget.preselectedLocationId != null) {
      loc = _locations.firstWhere(
        (l) => l['id'] == widget.preselectedLocationId,
        orElse: () => <String, dynamic>{},
      );
    }

    if (loc == null || loc.isEmpty) {
      if (widget.preselectedAreaName != null) {
        loc = _locations.firstWhere(
          (l) =>
              l['name']?.toString().toLowerCase() ==
              widget.preselectedAreaName!.toLowerCase(),
          orElse: () => <String, dynamic>{},
        );
      }
    }

    if (loc != null && loc.isNotEmpty) {
      final locId = loc['id'].toString();
      await _onLocationChanged(locId);

      // 2. Find Category: Prefer Code, fallback to Name
      Map<String, dynamic>? cat;
      if (widget.preselectedCategoryCode != null) {
        final code = widget.preselectedCategoryCode!.toString();
        cat = _categories.firstWhere(
          (c) => c['id']?.toString() == code || c['slug']?.toString() == code,
          orElse: () => <String, dynamic>{},
        );
      }

      if (cat == null || cat.isEmpty) {
        if (widget.preselectedCategoryName != null) {
          cat = _categories.firstWhere(
            (c) =>
                (c['label'] ?? c['name'])?.toString().toLowerCase() ==
                widget.preselectedCategoryName!.toLowerCase(),
            orElse: () => <String, dynamic>{},
          );
        }
      }

      if (cat != null && cat.isNotEmpty) {
        final catId = cat['id'].toString();
        await _onCategoryChanged(catId);

        // 3. Set Specialist
        if (mounted && widget.preselectedSpecialistId != null) {
          final spId = widget.preselectedSpecialistId!;
          setState(() => _specialistId = spId);
          _syncHospitalForSpecialist(spId);
        }
      }
    }
  }

  Future<void> _onLocationChanged(String? id) async {
    if (id == null) return;
    setState(() {
      _locationId = id;
      _categoryId = null;
      _specialistId = null;
      _specialistHospitalId = null;
      _selectedIpdHospitalId = null;
      _specialistHospitalName = null;
      _categories = [];
      _specialists = [];
      _loadingCategories = true;
    });

    final repo = ref.read(gpRepositoryProvider);
    try {
      final cats = await repo.listCategories(id);
      if (mounted) {
        setState(() {
          _categories = cats;
          _loadingCategories = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _loadingCategories = false);
    }
    _saveDraft();
  }

  Future<void> _onCategoryChanged(String? id) async {
    if (id == null || _locationId == null) return;
    setState(() {
      _categoryId = id;
      _specialistId = null;
      _specialistHospitalId = null;
      _selectedIpdHospitalId = null;
      _specialistHospitalName = null;
      _specialists = [];
      _loadingSpecialists = true;
    });

    final repo = ref.read(gpRepositoryProvider);
    try {
      final specialists = await repo.listSpecialistsByFilter(
        locationId: _locationId!,
        specialtyId: id,
      );
      if (mounted) {
        setState(() {
          var filtered = List<Map<String, dynamic>>.from(specialists);

          // Safety: Map integer bit (0/1) to boolean if needed for Laravel compatibility
          for (var s in filtered) {
            final ip = s['is_premium'];
            if (ip == 1) s['is_premium'] = true;
            if (ip == 0) s['is_premium'] = false;
          }

          filtered.sort((a, b) {
            final aP = a['is_premium'] == true;
            final bP = b['is_premium'] == true;
            if (aP && !bP) return -1;
            if (!aP && bP) return 1;

            // Secondary sort: Super Specialists
            final aS = a['is_super_specialist'] == true ||
                a['is_super_specialist'] == 1;
            final bS = b['is_super_specialist'] == true ||
                b['is_super_specialist'] == 1;
            if (aS && !bS) return -1;
            if (!aS && bS) return 1;

            return 0;
          });

          _specialists = filtered;
          _loadingSpecialists = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _loadingSpecialists = false);
    }
    _saveDraft();
  }

  Future<void> _onHospitalLocationChanged(String? id) async {
    if (id == null) return;
    setState(() {
      _hospitalLocationId = id;
      _hospitalId = null;
      _hospitalDepartment = null;
      _hospitals = [];
      _hospitalDepartments = [];
      _loadingHospitals = true;
    });

    final repo = ref.read(gpRepositoryProvider);
    try {
      final hospitals = await repo.listHospitalsByLocationForSender(
        locationId: id,
        asSpecialist: widget.submitHospitalAsSpecialist,
      );
      if (!mounted) return;
      setState(() {
        _hospitals = hospitals;
        _loadingHospitals = false;
      });
    } catch (_) {
      if (mounted) setState(() => _loadingHospitals = false);
    }
    _saveDraft();
  }

  Future<void> _onHospitalChanged(String? id) async {
    if (id == null || id.isEmpty) return;
    setState(() {
      _hospitalId = id;
      _hospitalDepartment = null;
      _hospitalDepartments = [];
      _loadingHospitalDepartments = true;
    });

    final repo = ref.read(gpRepositoryProvider);
    try {
      final deps = await repo.listHospitalDepartmentsForSender(
        hospitalId: id,
        asSpecialist: widget.submitHospitalAsSpecialist,
      );
      if (!mounted) return;
      setState(() {
        _hospitalDepartments = deps;
        _loadingHospitalDepartments = false;
      });
    } catch (_) {
      if (mounted) setState(() => _loadingHospitalDepartments = false);
    }
    _saveDraft();
  }

  void _syncHospitalForSpecialist(String? specialistId) {
    if (specialistId == null || specialistId.isEmpty) {
      setState(() {
        _specialistHospitalId = null;
        _selectedIpdHospitalId = null;
        _specialistHospitalName = null;
      });
      return;
    }

    final sp = _specialists.firstWhere(
      (s) => s['id']?.toString() == specialistId,
      orElse: () => <String, dynamic>{},
    );
    if (sp.isEmpty) {
      setState(() {
        _specialistHospitalId = null;
        _selectedIpdHospitalId = null;
        _specialistHospitalName = null;
      });
      return;
    }

    final linkedHospitals = (sp['linked_hospitals'] as List? ?? const [])
        .whereType<Map>()
        .map((e) => e.cast<String, dynamic>())
        .toList();
    final primaryHospitalId = sp['hospital_id']?.toString();
    final primaryHospitalName = sp['hospital_name']?.toString() ??
        sp['mapped_hospital_name']?.toString();
    final fallbackHospitalId = linkedHospitals.isNotEmpty
        ? linkedHospitals.first['id']?.toString()
        : primaryHospitalId;
    final fallbackHospitalName = linkedHospitals.isNotEmpty
        ? linkedHospitals.first['name']?.toString()
        : primaryHospitalName;

    setState(() {
      _specialistHospitalId = fallbackHospitalId;
      _selectedIpdHospitalId = fallbackHospitalId;
      _specialistHospitalName = fallbackHospitalName;
    });
  }

  List<Map<String, dynamic>> _linkedHospitalsForSelectedSpecialist() {
    if (_specialistId == null || _specialistId!.isEmpty) return const [];

    final sp = _specialists.firstWhere(
      (s) => s['id']?.toString() == _specialistId,
      orElse: () => <String, dynamic>{},
    );
    if (sp.isEmpty) return const [];

    final items = (sp['linked_hospitals'] as List? ?? const [])
        .whereType<Map>()
        .map((e) => e.cast<String, dynamic>())
        .where((h) => h['id'] != null)
        .toList();

    if (items.isNotEmpty) return items;

    final fallbackId = sp['hospital_id']?.toString();
    final fallbackName =
        (sp['hospital_name'] ?? sp['mapped_hospital_name'])?.toString();
    if (fallbackId == null ||
        fallbackId.isEmpty ||
        fallbackName == null ||
        fallbackName.isEmpty) {
      return const [];
    }

    return [
      {
        'id': fallbackId,
        'name': fallbackName,
      },
    ];
  }

  @override
  void dispose() {
    _draftDebounceTimer?.cancel();
    _patientName.dispose();
    _patientMobile.dispose();
    _ageCtrl.dispose();
    _notes.dispose();
    _hospitalDepartmentCtrl.dispose();
    _diagnosticLocationsSub.close();
    super.dispose();
  }

  Future<void> _pickFiles() async {
    final res = await FilePicker.platform.pickFiles(
      allowMultiple: true,
      type: FileType.custom,
      allowedExtensions: ['pdf', 'dcm', 'dicom'],
    );

    if (!mounted) return;

    if (res != null) {
      final validFiles = <PlatformFile>[];
      for (final file in res.files) {
        if (file.size <= 25 * 1024 * 1024) {
          // 25MB check
          validFiles.add(file);
        } else {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(
              content: Text('File "${file.name}" exceeds 25MB limit.')));
        }
      }
      setState(() {
        _selectedFiles.addAll(validFiles);
      });
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    final genderLower = _gender.toLowerCase();
    final fields = <String, dynamic>{
      'patient_name': _patientName.text.trim(),
      'patient_mobile': _patientMobile.text.trim(),
      'patient_age': _ageCtrl.text.trim(),
      'patient_gender': genderLower,
      'case_summary': _notes.text.trim(),
    };
    if (_referralType != ReferralType.diagnostic) {
      fields['appointment_type'] = _visitType;
    }

    if (_referralType == ReferralType.specialist) {
      if (_visitType == 'ipd') {
        final hospitalId =
            (_selectedIpdHospitalId ?? _specialistHospitalId)?.trim();
        if (hospitalId == null || hospitalId.isEmpty) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
                content: Text('Please select a hospital for IPD referral.')),
          );
          return;
        }
        fields['hospital_id'] = hospitalId;
      }
      fields.addAll({
        'specialist_id': _specialistId,
      });
    } else if (_referralType == ReferralType.hospital) {
      if (_hospitalLocationId == null || _hospitalLocationId!.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Please select a location')),
        );
        return;
      }
      if (_hospitalId == null || _hospitalId!.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Please select a hospital')),
        );
        return;
      }
      if (_hospitalDepartmentCtrl.text.trim().isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Please select a department')),
        );
        return;
      }
      fields.addAll({
        'referral_type': 'hospital',
        'hospital_id': _hospitalId,
        'department': _hospitalDepartmentCtrl.text.trim(),
        'priority': _hospitalPriority,
      });
    } else {
      if (_diagnosticLocationId == null || _diagnosticLocationId!.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Please select a diagnostic location')),
        );
        return;
      }
      if (_diagnosticCenterId == null || _diagnosticCenterId!.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Please select a diagnostic center')),
        );
        return;
      }
      if (_diagnosticServiceIds.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
              content: Text('Please select at least one diagnostic service')),
        );
        return;
      }

      fields.addAll({
        'referral_type': 'diagnostic',
        'diagnostic_center_id': _diagnosticCenterId,
        'diagnostic_service_ids[]': _diagnosticServiceIds.toList(),
        'priority': _diagnosticPriority,
      });
    }

    final filePaths = _selectedFiles.map((f) => f.path!).toList();

    final controller = ref.read(newReferralControllerProvider.notifier);
    if (_referralType == ReferralType.diagnostic) {
      await controller.submitDiagnostic(fields: fields, filePaths: filePaths);
    } else if (_referralType == ReferralType.hospital) {
      await controller.submitHospital(
        fields: fields,
        filePaths: filePaths,
        asSpecialist: widget.submitHospitalAsSpecialist,
      );
    } else {
      await controller.submit(fields: fields, filePaths: filePaths);
    }

    final state = ref.read(newReferralControllerProvider);
    if (state is NewReferralSuccess) {
      if (!mounted) return;
      final messenger = ScaffoldMessenger.of(context);
      final nav = Navigator.of(context);
      messenger.showSnackBar(
        const SnackBar(
            content: Text('Referral submitted successfully!'),
            backgroundColor: Colors.green),
      );
      if (!widget.submitHospitalAsSpecialist) {
        ref.read(gpHomeControllerProvider.notifier).load();
        ref.read(referralListControllerProvider.notifier).refresh();
      }
      await _clearDraft();
      if (!mounted) return;
      nav.pop();
    } else if (state is NewReferralError) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
            content: Text(state.message),
            backgroundColor: AppColors.statusError));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final submitState = ref.watch(newReferralControllerProvider);
    final submitting = submitState is NewReferralSubmitting;
    final submitReady = switch (_referralType) {
      ReferralType.specialist => (_locationId?.isNotEmpty ?? false) &&
          (_categoryId?.isNotEmpty ?? false) &&
          (_specialistId?.isNotEmpty ?? false) &&
          (_visitType != 'ipd' ||
              (_specialistHospitalId?.isNotEmpty ?? false) ||
              (_selectedIpdHospitalId?.isNotEmpty ?? false)),
      ReferralType.diagnostic => (_diagnosticLocationId?.isNotEmpty ?? false) &&
          (_diagnosticCenterId?.isNotEmpty ?? false) &&
          _diagnosticServiceIds.isNotEmpty,
      ReferralType.hospital => (_hospitalLocationId?.isNotEmpty ?? false) &&
          (_hospitalId?.isNotEmpty ?? false) &&
          (_hospitalDepartmentCtrl.text.trim().isNotEmpty),
    };

    return Scaffold(
      backgroundColor: AppColors.background,
      body: Column(
        children: [
          AppHeroHeader(
            leading: IconButton(
              onPressed: () => Navigator.maybePop(context),
              icon: const Icon(Icons.arrow_back_rounded,
                  color: AppColors.textOnDark),
            ),
            title:
                widget.hospitalOnlyMode ? 'Hospital Referral' : 'New Referral',
            subtitle: widget.hospitalOnlyMode
                ? 'Create a hospital referral'
                : 'Specialist, diagnostic, or hospital referral',
            bottom: widget.hospitalOnlyMode
                ? null
                : AppSegmentedControl<ReferralType>(
                    value: _referralType,
                    options: const [
                      AppSegmentOption(
                        value: ReferralType.specialist,
                        label: 'Specialist',
                        icon: Icons.person_search_outlined,
                      ),
                      AppSegmentOption(
                        value: ReferralType.diagnostic,
                        label: 'Diagnostic',
                        icon: Icons.biotech_outlined,
                      ),
                      AppSegmentOption(
                        value: ReferralType.hospital,
                        label: 'Hospital',
                        icon: Icons.local_hospital_outlined,
                      ),
                    ],
                    onChanged: _setReferralType,
                  ),
          ),
          Expanded(
            child: _loadingLocations
                ? const Center(
                    child: CircularProgressIndicator(
                        color: AppColors.secondaryTeal))
                : Form(
                    key: _formKey,
                    child: ListView(
                      padding: AppSpacing.responsiveScreenPadding(context)
                          .copyWith(top: 16, bottom: 28),
                      children: [
                        _cardSection(
                          title: 'Patient Info',
                          children: [
                            LabeledTextField(
                              controller: _patientName,
                              label: 'Full Name',
                              hintText: 'Enter patient\'s full name',
                              validator: (v) =>
                                  v == null || v.isEmpty ? 'Required' : null,
                            ),
                            const SizedBox(height: 12),
                            Row(
                              children: [
                                Expanded(
                                  child: LabeledTextField(
                                    controller: _ageCtrl,
                                    label: 'Age',
                                    hintText: 'e.g. 45',
                                    keyboardType: TextInputType.number,
                                    validator: (v) => v == null || v.isEmpty
                                        ? 'Required'
                                        : null,
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: FormField<String>(
                                    key: ValueKey('gender_$_gender'),
                                    initialValue: _gender,
                                    validator: (v) => v == null || v.isEmpty
                                        ? 'Required'
                                        : null,
                                    builder: (field) {
                                      const items = [
                                        {'id': 'Male', 'name': 'Male'},
                                        {'id': 'Female', 'name': 'Female'},
                                        {'id': 'Other', 'name': 'Other'},
                                      ];
                                      return Column(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          _PremiumSelectField(
                                            topLabel: 'Gender',
                                            value:
                                                field.value ?? 'Select gender',
                                            leadingIcon:
                                                _genderIcon(field.value),
                                            onTap: () async {
                                              final picked =
                                                  await _pickFromBottomSheet(
                                                title: 'Select Gender',
                                                items: items,
                                                selectedId: field.value,
                                                leadingIconBuilder: (e) =>
                                                    _genderIcon(
                                                        e['id']?.toString()),
                                              );
                                              if (!mounted) return;
                                              if (picked == null) return;
                                              field.didChange(picked);
                                              setState(() => _gender = picked);
                                              _saveDraft();
                                            },
                                            isPlaceholder:
                                                field.value == null ||
                                                    field.value!.isEmpty,
                                          ),
                                          if (field.hasError) ...[
                                            const SizedBox(height: 8),
                                            Text(
                                              field.errorText ?? '',
                                              style: AppStyles.caption.copyWith(
                                                  color: AppColors.statusError),
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
                              controller: _patientMobile,
                              label: 'Mobile Number',
                              hintText: 'e.g. 9876543210',
                              keyboardType: TextInputType.phone,
                              validator: (v) =>
                                  v == null || v.isEmpty ? 'Required' : null,
                            ),
                          ],
                        ),
                        const SizedBox(height: 16),
                        _cardSection(
                          title: 'Case Summary',
                          children: [
                            LabeledTextField(
                              controller: _notes,
                              label: 'Clinical observations & history',
                              hintText:
                                  'Describe condition, history, and findings...',
                              maxLines: 4,
                              validator: (v) => v == null || v.isEmpty
                                  ? 'Please enter clinical history'
                                  : null,
                            ),
                          ],
                        ),
                        const SizedBox(height: 16),
                        if (_referralType == ReferralType.specialist)
                          _cardSection(
                            title: 'Specialist Selection',
                            children: [
                              FormField<String>(
                                key: ValueKey(
                                    'sp_location_${_locationId ?? 'none'}'),
                                initialValue: _locationId,
                                validator: (v) => v == null || v.isEmpty
                                    ? 'Select a location'
                                    : null,
                                builder: (field) {
                                  final name =
                                      _nameFromId(_locations, field.value);
                                  return Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      _PremiumSelectField(
                                        topLabel: 'Location',
                                        value: name ?? 'Select location',
                                        leadingIcon: Icons.location_on_rounded,
                                        onTap: () async {
                                          final picked =
                                              await _pickFromBottomSheet(
                                            title: 'Select Location',
                                            items: _locations,
                                            selectedId: field.value,
                                          );
                                          if (!mounted) return;
                                          if (picked == null) return;
                                          field.didChange(picked);
                                          await _onLocationChanged(picked);
                                        },
                                        isPlaceholder: name == null,
                                      ),
                                      if (field.hasError) ...[
                                        const SizedBox(height: 8),
                                        Text(
                                          field.errorText ?? '',
                                          style: AppStyles.caption.copyWith(
                                              color: AppColors.statusError),
                                        ),
                                      ],
                                    ],
                                  );
                                },
                              ),
                              const SizedBox(height: 12),
                              FormField<String>(
                                key: ValueKey(
                                    'sp_category_${_locationId ?? 'none'}_${_categoryId ?? 'none'}'),
                                initialValue: _categoryId,
                                validator: (v) => v == null || v.isEmpty
                                    ? 'Select a category'
                                    : null,
                                builder: (field) {
                                  final disabled = _locationId == null ||
                                      _locationId!.isEmpty ||
                                      _loadingCategories;
                                  final selectedCat = _categories
                                      .cast<Map<String, dynamic>>()
                                      .where((c) => '${c['id']}' == field.value)
                                      .toList();
                                  final c = selectedCat.isNotEmpty
                                      ? selectedCat.first
                                      : null;
                                  final label = (c == null
                                          ? ''
                                          : (c['label'] ?? c['name'])
                                                  ?.toString() ??
                                              '')
                                      .trim();
                                  final iconKey = c?['icon_key']?.toString();
                                  final slug = c?['slug']?.toString();
                                  final description =
                                      c?['description']?.toString();
                                  final specialistCountRaw =
                                      c?['specialist_count'];
                                  final count = specialistCountRaw is int
                                      ? specialistCountRaw
                                      : int.tryParse('$specialistCountRaw') ??
                                          0;
                                  final valueHint = [
                                    if (description != null &&
                                        description.trim().isNotEmpty)
                                      description.trim(),
                                    if (count > 0) '$count specialists',
                                  ].join(' · ');

                                  return Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      Stack(
                                        alignment: Alignment.centerRight,
                                        children: [
                                          _PremiumSelectField(
                                            topLabel: 'Specialist Category',
                                            value: disabled
                                                ? (_locationId == null ||
                                                        _locationId!.isEmpty
                                                    ? 'Select a location first'
                                                    : 'Loading categories...')
                                                : (label.isNotEmpty
                                                    ? label
                                                    : (_categories.isEmpty
                                                        ? 'No categories available'
                                                        : 'Select category')),
                                            valueHint: valueHint.isEmpty
                                                ? null
                                                : valueHint,
                                            leadingIcon: label.isNotEmpty
                                                ? AppCategoryIcons.fromKey(
                                                    iconKey,
                                                    slug: slug,
                                                    name: label)
                                                : Icons.category_rounded,
                                            onTap: disabled ||
                                                    _categories.isEmpty
                                                ? null
                                                : () async {
                                                    final picked =
                                                        await _pickFromBottomSheet(
                                                      title:
                                                          'Select Specialist Category',
                                                      items: _categories,
                                                      selectedId: field.value,
                                                      labelBuilder: (e) =>
                                                          (e['label'] ??
                                                                  e['name'] ??
                                                                  '')
                                                              .toString(),
                                                      subtitleBuilder: (e) {
                                                        final desc =
                                                            e['description']
                                                                ?.toString()
                                                                .trim();
                                                        final cnt = (e[
                                                                    'specialist_count']
                                                                is int)
                                                            ? e['specialist_count']
                                                                as int
                                                            : int.tryParse(
                                                                    '${e['specialist_count']}') ??
                                                                0;
                                                        final meta = cnt > 0
                                                            ? '$cnt specialists'
                                                            : '';
                                                        return [
                                                          if (desc != null &&
                                                              desc.isNotEmpty)
                                                            desc,
                                                          if (meta.isNotEmpty)
                                                            meta
                                                        ].join(' · ');
                                                      },
                                                      leadingIconBuilder: (e) {
                                                        final label =
                                                            (e['label'] ??
                                                                    e['name'] ??
                                                                    '')
                                                                .toString();
                                                        return AppCategoryIcons
                                                            .fromKey(
                                                          e['icon_key']
                                                              ?.toString(),
                                                          slug: e['slug']
                                                              ?.toString(),
                                                          name: label,
                                                        );
                                                      },
                                                    );
                                                    if (!mounted) return;
                                                    if (picked == null) return;
                                                    field.didChange(picked);
                                                    await _onCategoryChanged(
                                                        picked);
                                                  },
                                            isPlaceholder: label.isEmpty,
                                          ),
                                          if (_loadingCategories)
                                            const Padding(
                                              padding:
                                                  EdgeInsets.only(right: 16),
                                              child: SizedBox(
                                                width: 18,
                                                height: 18,
                                                child:
                                                    CircularProgressIndicator(
                                                  strokeWidth: 2,
                                                  color:
                                                      AppColors.secondaryTeal,
                                                ),
                                              ),
                                            ),
                                        ],
                                      ),
                                      if (!disabled && _categories.isEmpty) ...[
                                        const SizedBox(height: 10),
                                        _inlineMessage(
                                            'No categories available for this location.'),
                                      ],
                                      if (field.hasError) ...[
                                        const SizedBox(height: 8),
                                        Text(
                                          field.errorText ?? '',
                                          style: AppStyles.caption.copyWith(
                                              color: AppColors.statusError),
                                        ),
                                      ],
                                    ],
                                  );
                                },
                              ),
                              const SizedBox(height: 12),
                              FormField<String>(
                                key: ValueKey(
                                    'sp_specialist_${_specialistId ?? 'none'}'),
                                initialValue: _specialistId,
                                validator: (v) => v == null || v.isEmpty
                                    ? 'Select a specialist'
                                    : null,
                                builder: (field) {
                                  final disabled = _categoryId == null ||
                                      _categoryId!.isEmpty ||
                                      _loadingSpecialists ||
                                      _specialists.isEmpty;
                                  final selected = _specialists
                                      .cast<Map<String, dynamic>>()
                                      .where((s) => '${s['id']}' == field.value)
                                      .toList();
                                  final s = selected.isNotEmpty
                                      ? selected.first
                                      : null;
                                  final name = s?['name']?.toString().trim();
                                  final hospital =
                                      (s?['hospital_name']?.toString() ?? '')
                                          .trim();
                                  final isPremium = s?['is_premium'] == true ||
                                      s?['is_premium'] == 1;
                                  final label = (name == null || name.isEmpty)
                                      ? (disabled
                                          ? (_categoryId == null
                                              ? 'Select a category first'
                                              : (_loadingSpecialists
                                                  ? 'Loading specialists...'
                                                  : 'No specialists available'))
                                          : 'Select specialist')
                                      : [
                                          name,
                                          if (isPremium) 'Premium',
                                          if (hospital.isNotEmpty) hospital,
                                        ].join(' · ');

                                  return Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      Stack(
                                        alignment: Alignment.centerRight,
                                        children: [
                                          _PremiumSelectField(
                                            topLabel: 'Specialist',
                                            value: label,
                                            leadingIcon:
                                                Icons.person_search_rounded,
                                            onTap: disabled
                                                ? null
                                                : () async {
                                                    final picked =
                                                        await _pickFromBottomSheet(
                                                      title:
                                                          'Select Specialist',
                                                      items: _specialists,
                                                      selectedId: field.value,
                                                      labelBuilder: (e) =>
                                                          (e['name'] ?? '')
                                                              .toString(),
                                                      subtitleBuilder: (e) {
                                                        final hospital =
                                                            (e['hospital_name']
                                                                        ?.toString() ??
                                                                    '')
                                                                .trim();
                                                        final speciality =
                                                            (e['speciality']
                                                                        ?.toString() ??
                                                                    '')
                                                                .trim();
                                                        final isPremium =
                                                            e['is_premium'] ==
                                                                    true ||
                                                                e['is_premium'] ==
                                                                    1;
                                                        return [
                                                          if (speciality
                                                              .isNotEmpty)
                                                            speciality,
                                                          if (hospital
                                                              .isNotEmpty)
                                                            hospital,
                                                          if (isPremium)
                                                            'Premium',
                                                        ].join(' · ');
                                                      },
                                                      leadingIcon:
                                                          Icons.person_rounded,
                                                    );
                                                    if (!mounted) return;
                                                    if (picked == null) return;
                                                    field.didChange(picked);
                                                    setState(() =>
                                                        _specialistId = picked);
                                                    _syncHospitalForSpecialist(
                                                        picked);
                                                    _saveDraft();
                                                  },
                                            isPlaceholder:
                                                name == null || name.isEmpty,
                                          ),
                                          if (_loadingSpecialists)
                                            const Padding(
                                              padding:
                                                  EdgeInsets.only(right: 16),
                                              child: SizedBox(
                                                width: 18,
                                                height: 18,
                                                child:
                                                    CircularProgressIndicator(
                                                  strokeWidth: 2,
                                                  color:
                                                      AppColors.secondaryTeal,
                                                ),
                                              ),
                                            ),
                                        ],
                                      ),
                                      if (field.hasError) ...[
                                        const SizedBox(height: 8),
                                        Text(
                                          field.errorText ?? '',
                                          style: AppStyles.caption.copyWith(
                                              color: AppColors.statusError),
                                        ),
                                      ],
                                    ],
                                  );
                                },
                              ),
                              if (_specialistId != null &&
                                  _specialistId!.isNotEmpty) ...[
                                const SizedBox(height: 10),
                                Builder(
                                  builder: (context) {
                                    final sp = _specialists.firstWhere(
                                      (s) =>
                                          s['id']?.toString() == _specialistId,
                                      orElse: () => <String, dynamic>{},
                                    );
                                    if (sp.isEmpty)
                                      return const SizedBox.shrink();

                                    final isPremium =
                                        sp['is_premium'] == true ||
                                            sp['is_premium'] == 1;
                                    final hospital =
                                        (sp['hospital_name']?.toString() ?? '')
                                            .trim();
                                    return Container(
                                      width: double.infinity,
                                      padding: const EdgeInsets.symmetric(
                                          horizontal: 12, vertical: 10),
                                      decoration: BoxDecoration(
                                        color: AppColors.inputBackground,
                                        borderRadius: AppStyles.radiusInput,
                                        border: Border.all(
                                            color: AppColors.borderLight),
                                      ),
                                      child: Row(
                                        children: [
                                          Expanded(
                                            child: Text(
                                              hospital.isNotEmpty
                                                  ? hospital
                                                  : 'Independent Practice',
                                              maxLines: 1,
                                              overflow: TextOverflow.ellipsis,
                                              style: AppStyles.bodySmall
                                                  .copyWith(
                                                      color: AppColors
                                                          .textSecondary),
                                            ),
                                          ),
                                          if (isPremium)
                                            Container(
                                              padding:
                                                  const EdgeInsets.symmetric(
                                                      horizontal: 8,
                                                      vertical: 4),
                                              decoration: BoxDecoration(
                                                color: AppColors.warningYellow
                                                    .withAlpha(26),
                                                borderRadius:
                                                    AppStyles.radiusPill,
                                                border: Border.all(
                                                    color: AppColors
                                                        .warningYellow
                                                        .withAlpha(90)),
                                              ),
                                              child: Text(
                                                'Premium',
                                                style: AppStyles.chipText
                                                    .copyWith(
                                                        color: AppColors
                                                            .warningYellow),
                                              ),
                                            ),
                                        ],
                                      ),
                                    );
                                  },
                                ),
                              ],
                            ],
                          )
                        else if (_referralType == ReferralType.hospital)
                          _cardSection(
                            title: 'Hospital Selection',
                            children: [
                              if (_loadingHospitalLocations)
                                const Center(
                                    child: CircularProgressIndicator(
                                        color: AppColors.secondaryTeal))
                              else
                                FormField<String>(
                                  key: ValueKey(
                                      'hospital_location_${_hospitalLocationId ?? 'none'}'),
                                  initialValue: _hospitalLocationId,
                                  validator: (v) => v == null || v.isEmpty
                                      ? 'Select a location'
                                      : null,
                                  builder: (field) {
                                    final name = _nameFromId(
                                        _hospitalLocations, field.value);
                                    return Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        _PremiumSelectField(
                                          topLabel: 'Location',
                                          value: name ?? 'Select location',
                                          leadingIcon:
                                              Icons.location_on_rounded,
                                          onTap: () async {
                                            final picked =
                                                await _pickFromBottomSheet(
                                              title: 'Select Location',
                                              items: _hospitalLocations,
                                              selectedId: field.value,
                                            );
                                            if (!mounted) return;
                                            if (picked == null) return;
                                            field.didChange(picked);
                                            await _onHospitalLocationChanged(
                                                picked);
                                          },
                                          isPlaceholder: name == null,
                                        ),
                                        if (field.hasError) ...[
                                          const SizedBox(height: 8),
                                          Text(
                                            field.errorText ?? '',
                                            style: AppStyles.caption.copyWith(
                                                color: AppColors.statusError),
                                          ),
                                        ],
                                      ],
                                    );
                                  },
                                ),
                              const SizedBox(height: 12),
                              FormField<String>(
                                key: ValueKey(
                                    'hospital_${_hospitalId ?? 'none'}'),
                                initialValue: _hospitalId,
                                validator: (v) => v == null || v.isEmpty
                                    ? 'Select a hospital'
                                    : null,
                                builder: (field) {
                                  final name =
                                      _nameFromId(_hospitals, field.value);
                                  final disabled =
                                      _hospitalLocationId == null ||
                                          _hospitalLocationId!.isEmpty ||
                                          _loadingHospitals;

                                  return Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      Stack(
                                        alignment: Alignment.centerRight,
                                        children: [
                                          _PremiumSelectField(
                                            topLabel: 'Hospital',
                                            value: disabled
                                                ? 'Select a location first'
                                                : (name ?? 'Select hospital'),
                                            leadingIcon:
                                                Icons.local_hospital_rounded,
                                            onTap: disabled
                                                ? null
                                                : () async {
                                                    final picked =
                                                        await _pickFromBottomSheet(
                                                      title: 'Select Hospital',
                                                      items: _hospitals,
                                                      selectedId: field.value,
                                                    );
                                                    if (!mounted) return;
                                                    if (picked == null) return;
                                                    field.didChange(picked);
                                                    await _onHospitalChanged(
                                                        picked);
                                                  },
                                            isPlaceholder:
                                                disabled || name == null,
                                          ),
                                          if (_loadingHospitals)
                                            const Padding(
                                              padding:
                                                  EdgeInsets.only(right: 16),
                                              child: SizedBox(
                                                width: 18,
                                                height: 18,
                                                child:
                                                    CircularProgressIndicator(
                                                  strokeWidth: 2,
                                                  color:
                                                      AppColors.secondaryTeal,
                                                ),
                                              ),
                                            ),
                                        ],
                                      ),
                                      if (field.hasError) ...[
                                        const SizedBox(height: 8),
                                        Text(
                                          field.errorText ?? '',
                                          style: AppStyles.caption.copyWith(
                                              color: AppColors.statusError),
                                        ),
                                      ],
                                    ],
                                  );
                                },
                              ),
                              const SizedBox(height: 12),
                              if (_loadingHospitalDepartments)
                                const Center(
                                    child: CircularProgressIndicator(
                                        color: AppColors.secondaryTeal))
                              else if (_hospitalDepartments.isNotEmpty)
                                FormField<String>(
                                  key: ValueKey(
                                      'hospital_department_${_hospitalDepartmentCtrl.text}_${_hospitalId ?? 'none'}'),
                                  initialValue:
                                      _hospitalDepartmentCtrl.text.isEmpty
                                          ? null
                                          : _hospitalDepartmentCtrl.text,
                                  validator: (v) => v == null || v.isEmpty
                                      ? 'Select a department'
                                      : null,
                                  builder: (field) {
                                    final items = _hospitalDepartments
                                        .map((d) => {'id': d, 'name': d})
                                        .toList();
                                    final disabled = _hospitalId == null ||
                                        _hospitalId!.isEmpty ||
                                        items.isEmpty;
                                    return Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        _PremiumSelectField(
                                          topLabel: 'Department',
                                          value: disabled
                                              ? 'Select a hospital first'
                                              : (field.value ??
                                                  'Select department'),
                                          leadingIcon: Icons.apartment_rounded,
                                          onTap: disabled
                                              ? null
                                              : () async {
                                                  final picked =
                                                      await _pickFromBottomSheet(
                                                    title: 'Select Department',
                                                    items: items,
                                                    selectedId: field.value,
                                                    leadingIcon:
                                                        Icons.apartment_rounded,
                                                  );
                                                  if (!mounted) return;
                                                  if (picked == null) return;
                                                  field.didChange(picked);
                                                  setState(() =>
                                                      _hospitalDepartmentCtrl
                                                          .text = picked);
                                                  _saveDraft();
                                                },
                                          isPlaceholder: field.value == null ||
                                              field.value!.isEmpty,
                                        ),
                                        if (field.hasError) ...[
                                          const SizedBox(height: 8),
                                          Text(
                                            field.errorText ?? '',
                                            style: AppStyles.caption.copyWith(
                                                color: AppColors.statusError),
                                          ),
                                        ],
                                      ],
                                    );
                                  },
                                )
                              else
                                LabeledTextField(
                                  controller: _hospitalDepartmentCtrl,
                                  label: 'Department',
                                  hintText: 'Enter department',
                                  validator: (v) =>
                                      v == null || v.trim().isEmpty
                                          ? 'Required'
                                          : null,
                                ),
                              const SizedBox(height: 12),
                              Text('Priority', style: AppStyles.sectionTitle),
                              const SizedBox(height: 10),
                              AppSegmentedControl<String>(
                                value: _hospitalPriority,
                                options: const [
                                  AppSegmentOption(
                                      value: 'routine',
                                      label: 'Routine',
                                      icon: Icons.event_available_rounded),
                                  AppSegmentOption(
                                      value: 'urgent',
                                      label: 'Urgent',
                                      icon: Icons.priority_high_rounded),
                                ],
                                onChanged: (v) {
                                  setState(() => _hospitalPriority = v);
                                  _saveDraft();
                                },
                              ),
                            ],
                          )
                        else
                          _cardSection(
                            title: 'Diagnostic Selection',
                            children: [
                              Builder(
                                builder: (context) {
                                  final locations =
                                      ref.watch(diagnosticLocationsProvider);
                                  return locations.when(
                                    data: (resp) {
                                      final items = resp.locations;
                                      if (items.isEmpty) {
                                        return _inlineMessage(
                                            'No diagnostic locations available right now.');
                                      }
                                      return FormField<String>(
                                        key: ValueKey(
                                            'dx_location_${_diagnosticLocationId ?? 'none'}'),
                                        initialValue: _diagnosticLocationId,
                                        validator: (v) => v == null || v.isEmpty
                                            ? 'Select a location'
                                            : null,
                                        builder: (field) {
                                          final name =
                                              _nameFromId(items, field.value);
                                          return Column(
                                            crossAxisAlignment:
                                                CrossAxisAlignment.start,
                                            children: [
                                              _PremiumSelectField(
                                                topLabel: 'Location',
                                                value:
                                                    name ?? 'Select location',
                                                leadingIcon:
                                                    Icons.location_on_rounded,
                                                onTap: () async {
                                                  final picked =
                                                      await _pickFromBottomSheet(
                                                    title: 'Select Location',
                                                    items: items,
                                                    selectedId: field.value,
                                                  );
                                                  if (!mounted) return;
                                                  if (picked == null) return;
                                                  field.didChange(picked);
                                                  _onDiagnosticLocationChanged(
                                                      picked);
                                                },
                                                isPlaceholder: name == null,
                                              ),
                                              if (field.hasError) ...[
                                                const SizedBox(height: 8),
                                                Text(
                                                  field.errorText ?? '',
                                                  style: AppStyles.caption
                                                      .copyWith(
                                                          color: AppColors
                                                              .statusError),
                                                ),
                                              ],
                                            ],
                                          );
                                        },
                                      );
                                    },
                                    error: (err, st) {
                                      return _retryInline(
                                        message:
                                            'We couldn’t load diagnostic locations.',
                                        onRetry: () => ref.invalidate(
                                            diagnosticLocationsProvider),
                                      );
                                    },
                                    loading: () => const Center(
                                      child: CircularProgressIndicator(
                                          color: AppColors.secondaryTeal),
                                    ),
                                  );
                                },
                              ),
                              const SizedBox(height: 12),
                              Builder(
                                builder: (context) {
                                  final types =
                                      ref.watch(diagnosticServiceTypesProvider);
                                  return types.when(
                                    data: (items) {
                                      final selectedTypes = items.where((t) {
                                        final id = (t['id'] as int?) ??
                                            int.tryParse('${t['id']}');
                                        return id != null &&
                                            _diagnosticServiceTypeFilterIds
                                                .contains(id);
                                      }).toList();

                                      return Column(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          SizedBox(
                                            width: double.infinity,
                                            child: OutlinedButton.icon(
                                              onPressed: items.isEmpty
                                                  ? null
                                                  : () =>
                                                      _pickDiagnosticServiceTypes(
                                                          items),
                                              icon: const Icon(
                                                  Icons.filter_list_rounded),
                                              label: Text(
                                                _diagnosticServiceTypeFilterIds
                                                        .isEmpty
                                                    ? 'Filter diagnostic centers by service'
                                                    : 'Service filters: ${_diagnosticServiceTypeFilterIds.length}',
                                              ),
                                              style: OutlinedButton.styleFrom(
                                                minimumSize: const Size(
                                                    double.infinity, 52),
                                                alignment: Alignment.centerLeft,
                                                side: BorderSide(
                                                    color: AppColors.primaryBlue
                                                        .withAlpha(45)),
                                                shape: RoundedRectangleBorder(
                                                  borderRadius:
                                                      BorderRadius.circular(18),
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
                                                final label = (t['label'] ??
                                                        t['name'] ??
                                                        '')
                                                    .toString();
                                                return Chip(
                                                  label: Text(label),
                                                  visualDensity:
                                                      VisualDensity.compact,
                                                  backgroundColor: AppColors
                                                      .primaryBlue
                                                      .withAlpha(12),
                                                  side: BorderSide(
                                                      color: AppColors
                                                          .primaryBlue
                                                          .withAlpha(30)),
                                                );
                                              }).toList(),
                                            ),
                                          ],
                                        ],
                                      );
                                    },
                                    error: (err, st) => _inlineMessage(
                                      extractApiErrorMessage(err,
                                          fallback:
                                              'Unable to load service filters right now.'),
                                    ),
                                    loading: () => const SizedBox.shrink(),
                                  );
                                },
                              ),
                              const SizedBox(height: 12),
                              Builder(
                                builder: (context) {
                                  if (_diagnosticLocationId == null ||
                                      _diagnosticLocationId!.isEmpty) {
                                    return const _PremiumSelectField(
                                      topLabel: 'Diagnostic Center',
                                      value: 'Select a location first',
                                      leadingIcon: Icons.biotech_rounded,
                                      onTap: null,
                                      isPlaceholder: true,
                                    );
                                  }

                                  final q = DiagnosticCenterQuery(
                                    locationId: _diagnosticLocationId!,
                                    serviceTypeIds: List<int>.from(
                                        _diagnosticServiceTypeFilterIds),
                                  );

                                  final centers = ref.watch(
                                      diagnosticCentersFilteredProvider(q));
                                  return centers.when(
                                    data: (items) {
                                      if (items.isEmpty) {
                                        return AppCard(
                                          color: AppColors.surfaceMuted,
                                          border: Border.all(
                                              color: AppColors.borderLight),
                                          padding: const EdgeInsets.all(14),
                                          child: Column(
                                            crossAxisAlignment:
                                                CrossAxisAlignment.start,
                                            children: [
                                              Text(
                                                'No diagnostic centers match the selected location and service filters.',
                                                style: AppStyles.bodySmall
                                                    .copyWith(
                                                        color: AppColors
                                                            .textSecondary),
                                              ),
                                              if (_diagnosticServiceTypeFilterIds
                                                  .isNotEmpty) ...[
                                                const SizedBox(height: 8),
                                                TextButton(
                                                  onPressed: () {
                                                    setState(() {
                                                      _diagnosticServiceTypeFilterIds
                                                          .clear();
                                                      _diagnosticCenterId =
                                                          null;
                                                      _diagnosticServiceIds
                                                          .clear();
                                                    });
                                                    _saveDraft();
                                                  },
                                                  child: const Text(
                                                      'Clear filters'),
                                                ),
                                              ],
                                            ],
                                          ),
                                        );
                                      }
                                      return FormField<String>(
                                        key: ValueKey(
                                            'dx_center_${_diagnosticCenterId ?? 'none'}'),
                                        initialValue: _diagnosticCenterId,
                                        validator: (v) => v == null || v.isEmpty
                                            ? 'Select a diagnostic center'
                                            : null,
                                        builder: (field) {
                                          final selected = items
                                              .cast<Map<String, dynamic>>()
                                              .where((c) =>
                                                  '${c['id']}' == field.value)
                                              .toList();
                                          final c = selected.isNotEmpty
                                              ? selected.first
                                              : null;
                                          final name =
                                              (c?['name']?.toString() ?? '')
                                                  .trim();
                                          final subtitle = [
                                            c?['micro_area']?.toString(),
                                            c?['address']?.toString(),
                                          ]
                                              .whereType<String>()
                                              .where((s) => s.trim().isNotEmpty)
                                              .join(' · ');

                                          final hours = _formatHours(
                                            c?['opening_time']?.toString(),
                                            c?['closing_time']?.toString(),
                                          );
                                          final days =
                                              _formatDays(c?['available_days']);
                                          final off = _formatWeeklyOff(
                                              c?['weekly_off']);
                                          final meta = [
                                            if (hours != null) hours,
                                            if (days != null) days,
                                            if (off != null) 'Off: $off',
                                          ].join(' · ');

                                          final value = name.isEmpty
                                              ? 'Select diagnostic center'
                                              : name;
                                          final valueHint = [
                                            if (subtitle.isNotEmpty) subtitle,
                                            if (meta.trim().isNotEmpty) meta,
                                          ].join(' • ');

                                          return Column(
                                            crossAxisAlignment:
                                                CrossAxisAlignment.start,
                                            children: [
                                              _PremiumSelectField(
                                                topLabel: 'Diagnostic Center',
                                                value: value,
                                                leadingIcon:
                                                    Icons.biotech_rounded,
                                                valueHint: valueHint.isEmpty
                                                    ? null
                                                    : valueHint,
                                                onTap: () async {
                                                  final picked =
                                                      await _pickFromBottomSheet(
                                                    title:
                                                        'Select Diagnostic Center',
                                                    items: items,
                                                    selectedId: field.value,
                                                    labelBuilder: (e) =>
                                                        (e['name'] ?? '')
                                                            .toString(),
                                                    subtitleBuilder: (e) {
                                                      final subtitle = [
                                                        e['micro_area']
                                                            ?.toString(),
                                                        e['address']
                                                            ?.toString(),
                                                      ]
                                                          .whereType<String>()
                                                          .where((s) => s
                                                              .trim()
                                                              .isNotEmpty)
                                                          .join(' · ');
                                                      final hours =
                                                          _formatHours(
                                                        e['opening_time']
                                                            ?.toString(),
                                                        e['closing_time']
                                                            ?.toString(),
                                                      );
                                                      final days = _formatDays(
                                                          e['available_days']);
                                                      final off =
                                                          _formatWeeklyOff(
                                                              e['weekly_off']);
                                                      final meta = [
                                                        if (hours != null)
                                                          hours,
                                                        if (days != null) days,
                                                        if (off != null)
                                                          'Off: $off',
                                                      ].join(' · ');
                                                      return [
                                                        if (subtitle.isNotEmpty)
                                                          subtitle,
                                                        if (meta
                                                            .trim()
                                                            .isNotEmpty)
                                                          meta,
                                                      ].join(' • ');
                                                    },
                                                    leadingIcon:
                                                        Icons.biotech_rounded,
                                                  );
                                                  if (!mounted) return;
                                                  if (picked == null) return;
                                                  field.didChange(picked);
                                                  _onDiagnosticCenterChanged(
                                                      picked);
                                                },
                                                isPlaceholder: name.isEmpty,
                                              ),
                                              if (field.hasError) ...[
                                                const SizedBox(height: 8),
                                                Text(
                                                  field.errorText ?? '',
                                                  style: AppStyles.caption
                                                      .copyWith(
                                                          color: AppColors
                                                              .statusError),
                                                ),
                                              ],
                                            ],
                                          );
                                        },
                                      );
                                    },
                                    error: (err, st) {
                                      return AppCard(
                                        color: AppColors.surfaceMuted,
                                        border: Border.all(
                                            color: AppColors.borderLight),
                                        padding: const EdgeInsets.all(14),
                                        child: Column(
                                          crossAxisAlignment:
                                              CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              extractApiErrorMessage(err,
                                                  fallback:
                                                      'We couldn’t load diagnostic centers.'),
                                              style: AppStyles.bodySmall
                                                  .copyWith(
                                                      color: AppColors
                                                          .textSecondary),
                                            ),
                                            const SizedBox(height: 10),
                                            OutlinedButton(
                                              onPressed: () => ref.invalidate(
                                                  diagnosticCentersFilteredProvider(
                                                      q)),
                                              child: const Text('Retry'),
                                            ),
                                          ],
                                        ),
                                      );
                                    },
                                    loading: () => const Padding(
                                      padding:
                                          EdgeInsets.symmetric(vertical: 12),
                                      child: Center(
                                        child: CircularProgressIndicator(
                                            color: AppColors.secondaryTeal),
                                      ),
                                    ),
                                  );
                                },
                              ),
                              const SizedBox(height: 14),
                              Builder(
                                builder: (context) {
                                  if (_diagnosticCenterId == null ||
                                      _diagnosticCenterId!.isEmpty) {
                                    return AppCard(
                                      color: AppColors.surfaceMuted,
                                      border: Border.all(
                                          color: AppColors.borderLight),
                                      padding: const EdgeInsets.all(14),
                                      child: Text(
                                        'Select a diagnostic center to see available services.',
                                        style: AppStyles.bodySmall.copyWith(
                                            color: AppColors.textSecondary),
                                      ),
                                    );
                                  }

                                  final services = ref.watch(
                                      diagnosticServicesProvider(
                                          _diagnosticCenterId!));
                                  return services.when(
                                    data: (items) {
                                      if (items.isEmpty) {
                                        return _inlineMessage(
                                            'No diagnostic services available for this center.');
                                      }

                                      return Column(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          Text('Services',
                                              style: AppStyles.sectionTitle),
                                          const SizedBox(height: 10),
                                          Wrap(
                                            spacing: 8,
                                            runSpacing: 8,
                                            children: items.map((s) {
                                              final id = s['id']?.toString();
                                              if (id == null)
                                                return const SizedBox.shrink();
                                              final name = (s['name'] ??
                                                          s['service_name'])
                                                      ?.toString() ??
                                                  'Service';
                                              return FilterChip(
                                                label: Text(name),
                                                selected: _diagnosticServiceIds
                                                    .contains(id),
                                                onSelected: (selected) =>
                                                    _toggleDiagnosticService(
                                                        id, selected),
                                              );
                                            }).toList(),
                                          ),
                                          const SizedBox(height: 16),
                                          Text('Priority',
                                              style: AppStyles.sectionTitle),
                                          const SizedBox(height: 10),
                                          AppSegmentedControl<String>(
                                            value: _diagnosticPriority,
                                            options: const [
                                              AppSegmentOption(
                                                  value: 'routine',
                                                  label: 'Routine',
                                                  icon: Icons
                                                      .event_available_rounded),
                                              AppSegmentOption(
                                                  value: 'urgent',
                                                  label: 'Urgent',
                                                  icon: Icons
                                                      .priority_high_rounded),
                                            ],
                                            onChanged: (v) {
                                              setState(() =>
                                                  _diagnosticPriority = v);
                                              _saveDraft();
                                            },
                                          ),
                                        ],
                                      );
                                    },
                                    error: (err, st) {
                                      return _retryInline(
                                        message:
                                            'We couldn’t load diagnostic services.',
                                        onRetry: () => ref.invalidate(
                                            diagnosticServicesProvider(
                                                _diagnosticCenterId!)),
                                      );
                                    },
                                    loading: () => const Center(
                                      child: CircularProgressIndicator(
                                          color: AppColors.secondaryTeal),
                                    ),
                                  );
                                },
                              ),
                            ],
                          ),
                        if (_referralType != ReferralType.diagnostic) ...[
                          const SizedBox(height: 16),
                          _cardSection(
                            title: 'Appointment Type',
                            children: [
                              AppSegmentedControl<String>(
                                value: _visitType,
                                options: const [
                                  AppSegmentOption(
                                      value: 'opd',
                                      label: 'OPD',
                                      icon: Icons.medical_services_outlined),
                                  AppSegmentOption(
                                      value: 'ipd',
                                      label: 'IPD',
                                      icon: Icons.hotel_outlined),
                                ],
                                onChanged: (v) {
                                  setState(() => _visitType = v);
                                  _saveDraft();
                                },
                              ),
                            ],
                          ),
                          if (_referralType == ReferralType.specialist &&
                              _visitType == 'ipd') ...[
                            const SizedBox(height: 16),
                            _cardSection(
                              title: 'Select Hospital for IPD',
                              children: [
                                Builder(
                                  builder: (context) {
                                    final hospitals =
                                        _linkedHospitalsForSelectedSpecialist();
                                    final currentValue =
                                        _selectedIpdHospitalId ??
                                            _specialistHospitalId;
                                    final displayName =
                                        _getHospitalNameFromList(
                                                hospitals, currentValue) ??
                                            (_specialistHospitalName
                                                        ?.trim()
                                                        .isNotEmpty ==
                                                    true
                                                ? _specialistHospitalName!
                                                    .trim()
                                                : null);

                                    return FormField<String>(
                                      initialValue: currentValue,
                                      validator: (v) => (v == null || v.isEmpty)
                                          ? 'Select a hospital'
                                          : null,
                                      builder: (field) {
                                        final disabled =
                                            _specialistId == null ||
                                                _specialistId!.isEmpty;
                                        return Column(
                                          crossAxisAlignment:
                                              CrossAxisAlignment.start,
                                          children: [
                                            if (disabled)
                                              _inlineMessage(
                                                  'Select a specialist first.')
                                            else if (hospitals.isEmpty)
                                              _inlineMessage(
                                                  'No linked hospitals are available for the selected specialist.'),
                                            _PremiumSelectField(
                                              topLabel: 'Hospital',
                                              value: disabled
                                                  ? 'Select a specialist first'
                                                  : (displayName ??
                                                      'Select hospital'),
                                              leadingIcon:
                                                  Icons.local_hospital_rounded,
                                              onTap: disabled ||
                                                      hospitals.isEmpty
                                                  ? null
                                                  : () async {
                                                      final picked =
                                                          await _pickFromBottomSheet(
                                                        title:
                                                            'Select Hospital',
                                                        items: hospitals,
                                                        selectedId: field.value,
                                                        subtitleBuilder: (e) {
                                                          final department =
                                                              e['department']
                                                                  ?.toString()
                                                                  .trim();
                                                          final role = e['role']
                                                              ?.toString()
                                                              .trim();
                                                          final isSuper =
                                                              e['is_super_specialist'] ==
                                                                      true ||
                                                                  e['is_super_specialist'] ==
                                                                      1;
                                                          return [
                                                            if (department !=
                                                                    null &&
                                                                department
                                                                    .isNotEmpty)
                                                              department,
                                                            if (role != null &&
                                                                role.isNotEmpty)
                                                              role,
                                                            if (isSuper)
                                                              'Super Specialist',
                                                          ].join(' · ');
                                                        },
                                                        leadingIcon: Icons
                                                            .local_hospital_rounded,
                                                      );
                                                      if (!mounted) return;
                                                      if (picked == null)
                                                        return;
                                                      setState(() =>
                                                          _selectedIpdHospitalId =
                                                              picked);
                                                      field.didChange(picked);
                                                      _saveDraft();
                                                    },
                                              isPlaceholder: disabled ||
                                                  displayName == null,
                                            ),
                                            if (field.hasError) ...[
                                              const SizedBox(height: 8),
                                              Text(
                                                field.errorText ?? '',
                                                style: AppStyles.caption
                                                    .copyWith(
                                                        color: AppColors
                                                            .statusError),
                                              ),
                                            ],
                                          ],
                                        );
                                      },
                                    );
                                  },
                                ),
                              ],
                            ),
                          ],
                        ],
                        const SizedBox(height: 16),
                        _cardSection(
                          title: 'Attachments',
                          children: [
                            AppAttachmentList(
                              files: _selectedFiles,
                              onRemove: (file) =>
                                  setState(() => _selectedFiles.remove(file)),
                            ),
                            OutlinedButton.icon(
                              onPressed: _pickFiles,
                              icon: const Icon(Icons.cloud_upload_outlined),
                              label: const Text('Upload PDF or DICOM'),
                              style: OutlinedButton.styleFrom(
                                  minimumSize: const Size(double.infinity, 52)),
                            ),
                            const SizedBox(height: 10),
                            Text('Up to 25MB per file',
                                style: AppStyles.caption),
                          ],
                        ),
                        const SizedBox(height: 22),
                        PrimaryButton(
                          label: submitting ? 'Submitting…' : 'Submit Referral',
                          onPressed:
                              submitting || !submitReady ? null : _submit,
                        ),
                      ],
                    ),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _cardSection({
    required String title,
    required List<Widget> children,
  }) {
    return AppCard(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SectionHeaderRow(title: title),
          const SizedBox(height: 12),
          ...children,
        ],
      ),
    );
  }

  Widget _inlineMessage(String message) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Text(message,
          style: AppStyles.bodySmall.copyWith(color: AppColors.textSecondary)),
    );
  }

  Widget _retryInline({
    required String message,
    required VoidCallback onRetry,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(message,
            style:
                AppStyles.bodySmall.copyWith(color: AppColors.textSecondary)),
        const SizedBox(height: 10),
        OutlinedButton(
          onPressed: onRetry,
          child: const Text('Retry'),
        ),
      ],
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

  String? _getHospitalNameFromList(
      List<Map<String, dynamic>> hospitals, String? id) {
    if (id == null || id.isEmpty) return null;
    for (final h in hospitals) {
      if ('${h['id']}' == id) {
        return h['name']?.toString().trim() ??
            h['hospital_name']?.toString().trim();
      }
    }
    return null;
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
                      Expanded(
                        child: Text(title, style: AppStyles.heading2),
                      ),
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
                        final name = (labelBuilder != null
                                ? labelBuilder(e)
                                : (e['name'] ?? '').toString())
                            .trim();
                        final sub = subtitleBuilder != null
                            ? (subtitleBuilder(e) ?? '').trim()
                            : '';
                        if (id.isEmpty || name.isEmpty)
                          return const SizedBox.shrink();
                        final selected = id == selectedId;
                        return AppCard(
                          onTap: () => Navigator.pop(context, id),
                          padding: const EdgeInsets.symmetric(
                              horizontal: 14, vertical: 12),
                          color: selected
                              ? AppColors.primaryBlue.withAlpha(14)
                              : AppColors.surfaceMuted,
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
                                  gradient:
                                      selected ? AppColors.heroGradient : null,
                                  color: selected
                                      ? null
                                      : AppColors.inputBackground,
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Icon(
                                  (leadingIconBuilder != null
                                          ? leadingIconBuilder(e)
                                          : null) ??
                                      leadingIcon ??
                                      Icons.location_on_rounded,
                                  color: selected
                                      ? Colors.white
                                      : AppColors.primaryBlue,
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
                                        color: selected
                                            ? AppColors.primaryBlue
                                            : AppColors.textPrimary,
                                      ),
                                      maxLines: 2,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                    if (sub.isNotEmpty) ...[
                                      const SizedBox(height: 2),
                                      Text(
                                        sub,
                                        style: AppStyles.caption.copyWith(
                                            color: AppColors.textSecondary),
                                        maxLines: 2,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ],
                                  ],
                                ),
                              ),
                              const SizedBox(width: 10),
                              Icon(
                                selected
                                    ? Icons.check_circle_rounded
                                    : Icons.chevron_right_rounded,
                                color: selected
                                    ? AppColors.secondaryTeal
                                    : AppColors.textMuted,
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

  String? _formatHours(String? opening, String? closing) {
    final o = _formatTime12h(opening);
    final c = _formatTime12h(closing);
    if (o == null && c == null) return null;
    if (o != null && c != null) return '$o - $c';
    return o ?? c;
  }

  String? _formatTime12h(String? time) {
    final t = (time ?? '').trim();
    if (t.isEmpty) return null;

    final parts = t.split(':');
    if (parts.length < 2) return t;
    final h = int.tryParse(parts[0]);
    final m = int.tryParse(parts[1]);
    if (h == null || m == null) return t;

    return DateFormat('h:mm a').format(DateTime(2000, 1, 1, h, m));
  }

  String? _formatWeeklyOff(dynamic off) {
    final v = (off ?? '').toString().trim().toLowerCase();
    if (v.isEmpty || v == 'none') return null;
    return _dayShort(v) ?? v;
  }

  String? _formatDays(dynamic days) {
    if (days is! List) return null;
    final mapped = days
        .map(
            (d) => _dayShort(d.toString().trim().toLowerCase()) ?? d.toString())
        .where((s) => s.trim().isNotEmpty)
        .toList();
    if (mapped.isEmpty) return null;
    return mapped.join(', ');
  }

  String? _dayShort(String code) {
    return switch (code) {
      'mon' || 'monday' => 'Mon',
      'tue' || 'tuesday' => 'Tue',
      'wed' || 'wednesday' => 'Wed',
      'thu' || 'thursday' => 'Thu',
      'fri' || 'friday' => 'Fri',
      'sat' || 'saturday' => 'Sat',
      'sun' || 'sunday' => 'Sun',
      _ => null,
    };
  }

  Future<void> _pickDiagnosticServiceTypes(
      List<Map<String, dynamic>> items) async {
    final selected = Set<int>.from(_diagnosticServiceTypeFilterIds);

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
                          final id =
                              (t['id'] as int?) ?? int.tryParse('${t['id']}');
                          final label =
                              (t['label'] ?? t['name'] ?? '').toString();
                          if (id == null || label.isEmpty)
                            return const SizedBox.shrink();
                          final isOn = selected.contains(id);
                          return CheckboxListTile(
                            value: isOn,
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
      _diagnosticServiceTypeFilterIds
        ..clear()
        ..addAll(result.toList()..sort());
      _diagnosticCenterId = null;
      _diagnosticServiceIds.clear();
    });
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
                    style: AppStyles.caption
                        .copyWith(color: AppColors.textSecondary),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 2),
                  Text(
                    value,
                    style: AppStyles.bodyLarge.copyWith(
                      fontWeight: FontWeight.w800,
                      color: isPlaceholder
                          ? AppColors.textMuted
                          : AppColors.textPrimary,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  if (valueHint != null && valueHint!.trim().isNotEmpty) ...[
                    const SizedBox(height: 2),
                    Text(
                      valueHint!,
                      style: AppStyles.caption
                          .copyWith(color: AppColors.textSecondary),
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
