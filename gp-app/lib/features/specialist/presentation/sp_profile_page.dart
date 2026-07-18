import 'dart:io';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:file_picker/file_picker.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/labeled_text_field.dart';
import 'package:gp_app/ui/widgets/primary_button.dart';
import 'package:gp_app/features/diagnostic_center/presentation/dx_profile_page.dart';
import 'package:gp_app/core/http/dio_client.dart';

import 'package:gp_app/ui/widgets/app_hero_header.dart';
import 'package:gp_app/ui/widgets/app_card.dart';
import 'package:gp_app/app/role.dart';
import 'package:gp_app/ui/widgets/section_header_row.dart';
import 'package:gp_app/ui/widgets/role_bottom_nav.dart';
import 'package:gp_app/ui/widgets/google_address_autocomplete_field.dart';

class SpProfilePage extends ConsumerStatefulWidget {
  const SpProfilePage({super.key});
  @override
  ConsumerState<SpProfilePage> createState() => _SpProfilePageState();
}

class _SpProfilePageState extends ConsumerState<SpProfilePage> {
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _mobile = TextEditingController();
  final _speciality = TextEditingController();
  final _hospitalName = TextEditingController();
  final _clinicStreet = TextEditingController();
  final _clinicArea = TextEditingController();
  final _clinicCity = TextEditingController();
  final _clinicPincode = TextEditingController();
  final _registrationNo = TextEditingController();
  final _councilName = TextEditingController();
  final _qualifications = TextEditingController();
  final _yearsExperience = TextEditingController();
  final _subSpecialties = TextEditingController();
  final _keyProcedures = TextEditingController();
  final _languages = TextEditingController();
  final _bio = TextEditingController();
  final _videos = TextEditingController();
  bool _consultationInPerson = true;
  bool _consultationTeleconsult = false;
  List<Map<String, dynamic>> _specialties = [];
  bool _loadingSpecialties = false;
  String? _selectedSpecialtySlug;
  final List<int> _additionalSpecialtyIds = [];
  bool _allowVideos = false;
  bool _allowCertificates = false;
  bool _addressAutocompleteEnabled = false;
  String? _googlePlacesApiKey;
  String _googlePlacesCountryCode = 'IN';
  List<String> _certificateUrls = [];
  final List<PlatformFile> _newCertificates = [];
  bool _loading = true;
  bool _saving = false;
  String? _profilePhotoUrl;
  PlatformFile? _profilePhotoFile;

  @override
  void initState() {
    super.initState();
    Future.microtask(() async {
      try {
        final subtype = ref.read(selectedSubtypeProvider);
        final env = await ref.read(specialistRepositoryProvider).fetchProfileEnvelope();
        final flags = await ref.read(publicFeatureFlagsProvider.future);
        final p = env.profile;
        setState(() {
          _name.text = p.name ?? '';
          _email.text = p.email ?? '';
          _mobile.text = p.mobile ?? '';
          _speciality.text = p.speciality ?? '';
          _hospitalName.text = p.hospitalName ?? '';
          _clinicStreet.text = p.clinicStreet ?? '';
          _clinicArea.text = p.clinicArea ?? '';
          _clinicCity.text = p.clinicCity ?? '';
          _clinicPincode.text = p.clinicPincode ?? '';
          _registrationNo.text = p.registrationNo ?? '';
          _councilName.text = p.councilName ?? '';
          _qualifications.text = (p.qualifications ?? const []).join(', ');
          _yearsExperience.text = p.yearsOfExperience?.toString() ?? '';
          _subSpecialties.text = p.subSpecialties ?? '';
          _keyProcedures.text = p.keyProcedures ?? '';
          _languages.text = (p.languages ?? const []).join(', ');
          _bio.text = p.bio ?? '';
          _videos.text = (p.videos ?? const []).join(', ');
          _profilePhotoUrl = p.profilePhoto;
          _allowVideos = env.allowVideos;
          _allowCertificates = env.allowCertificates;
          _addressAutocompleteEnabled = flags['enable_google_address_autocomplete'] == true;
          _googlePlacesApiKey = flags['google_places_api_key']?.toString();
          _googlePlacesCountryCode = (flags['google_places_country_code']?.toString().trim().isNotEmpty ?? false)
              ? flags['google_places_country_code'].toString()
              : 'IN';
          _certificateUrls = List<String>.from(p.certificates ?? const []);
          _additionalSpecialtyIds
            ..clear()
            ..addAll(p.additionalSpecialtyIds ?? const []);
          _consultationInPerson = p.consultationInPerson ?? true;
          _consultationTeleconsult = p.consultationTeleconsult ?? false;
          _selectedSpecialtySlug = p.primarySpecialtyCode;
          _loading = false;
        });

        final isSpecialist = subtype != RoleSubtype.hospital && subtype != RoleSubtype.diagnostic;
        if (isSpecialist) {
          setState(() => _loadingSpecialties = true);
          try {
            final items = await ref.read(specialistRepositoryProvider).listSpecialties();
            if (!mounted) return;
            setState(() {
              _specialties = items;
              _loadingSpecialties = false;
              final current = _speciality.text.trim();
              if (current.isNotEmpty) {
                final match = _specialties.where((s) {
                  final label = (s['label'] ?? s['name'] ?? '').toString();
                  return label.toLowerCase() == current.toLowerCase();
                }).toList();
                if (match.isNotEmpty) {
                  _selectedSpecialtySlug = match.first['slug']?.toString();
                }
              }
            });
          } catch (_) {
            if (mounted) setState(() => _loadingSpecialties = false);
          }
        }
      } catch (_) {
        setState(() => _loading = false);
      }
    });
  }

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _mobile.dispose();
    _speciality.dispose();
    _hospitalName.dispose();
    _clinicStreet.dispose();
    _clinicArea.dispose();
    _clinicCity.dispose();
    _clinicPincode.dispose();
    _registrationNo.dispose();
    _councilName.dispose();
    _qualifications.dispose();
    _yearsExperience.dispose();
    _subSpecialties.dispose();
    _keyProcedures.dispose();
    _languages.dispose();
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
      _newCertificates.addAll(res.files.where((f) => f.path != null));
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
    if (picked.path == null || picked.path!.isEmpty) return;
    setState(() => _profilePhotoFile = picked);
  }

  Future<void> _save() async {
    final required = <String, String>{
      'Full Name': _name.text.trim(),
      'Email': _email.text.trim(),
      'Mobile': _mobile.text.trim(),
      'Specialty': _speciality.text.trim(),
      'Hospital Name': _hospitalName.text.trim(),
      'Street': _clinicStreet.text.trim(),
      'Area': _clinicArea.text.trim(),
      'City': _clinicCity.text.trim(),
      'Pincode': _clinicPincode.text.trim(),
      'Registration No': _registrationNo.text.trim(),
      'Council Name': _councilName.text.trim(),
    };
    final missing = required.entries.where((e) => e.value.isEmpty).map((e) => e.key).toList();
    if (missing.isNotEmpty) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Please fill required fields: ${missing.join(', ')}')),
        );
      }
      return;
    }

    setState(() => _saving = true);
    try {
      final subtype = ref.read(selectedSubtypeProvider);
      final isSpecialist = subtype != RoleSubtype.hospital && subtype != RoleSubtype.diagnostic;
      final selected = isSpecialist
          ? _specialties.where((s) => (s['slug']?.toString() ?? '') == (_selectedSpecialtySlug ?? '')).toList()
          : const <Map<String, dynamic>>[];
      final selectedLabel = isSpecialist && selected.isNotEmpty ? (selected.first['label'] ?? selected.first['name'] ?? '').toString() : null;

      final updated = await ref.read(specialistRepositoryProvider).updateProfile({
        'name': _name.text.trim(),
        'email': _email.text.trim(),
        'mobile': _mobile.text.trim(),
        'speciality': isSpecialist ? (selectedLabel ?? _speciality.text.trim()) : _speciality.text.trim(),
        'hospital_name': _hospitalName.text.trim(),
        'clinic_street': _clinicStreet.text.trim(),
        'clinic_area': _clinicArea.text.trim(),
        'clinic_city': _clinicCity.text.trim(),
        'clinic_pincode': _clinicPincode.text.trim(),
        'registration_no': _registrationNo.text.trim(),
        'council_name': _councilName.text.trim(),
        'qualifications': _qualifications.text
            .split(',')
            .map((e) => e.trim())
            .where((e) => e.isNotEmpty)
            .toList(),
        'years_of_experience': int.tryParse(_yearsExperience.text.trim()),
        'sub_specialties': _subSpecialties.text.trim(),
        'key_procedures': _keyProcedures.text.trim(),
        'languages': _languages.text
            .split(',')
            .map((e) => e.trim())
            .where((e) => e.isNotEmpty)
            .toList(),
        'consultation_flags': {
          'in_person': _consultationInPerson,
          'teleconsult': _consultationTeleconsult,
        },
        'bio': _bio.text.trim(),
        if (isSpecialist && (_selectedSpecialtySlug ?? '').isNotEmpty) 'specialty_code': _selectedSpecialtySlug,
        if (isSpecialist) 'additional_specialty_ids': _additionalSpecialtyIds,
        if (_allowVideos)
          'videos': _videos.text
              .split(RegExp(r'[\n,]'))
              .map((e) => e.trim())
              .where((e) => e.isNotEmpty)
              .toList(),
      },
          certificatePaths: _allowCertificates ? _newCertificates.where((f) => f.path != null).map((f) => f.path!).toList() : const [],
          profilePhotoPath: _profilePhotoFile?.path);
      ref.read(authStateProvider.notifier).updateUser(updated);
      if (mounted) {
        setState(() {
          _newCertificates.clear();
          if (_profilePhotoFile?.path != null) {
            _profilePhotoUrl = _profilePhotoFile!.path;
          }
          _profilePhotoFile = null;
        });
      }
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Profile updated')));
    } catch (_) {
      if (mounted) {
        final msg = extractApiErrorMessage(_, fallback: 'Failed to update');
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final subtype = ref.watch(selectedSubtypeProvider);
    final gps = ref.watch(currentLocationProvider).asData?.value;
    final gpsLat = gps?.latitude;
    final gpsLng = gps?.longitude;
    if (subtype == RoleSubtype.diagnostic) {
      return const DxProfilePage();
    }
    final profileTitle = switch (subtype) {
      RoleSubtype.hospital => 'Hospital Profile',
      RoleSubtype.diagnostic => 'Diagnostic Profile',
      _ => 'Specialist Profile',
    };
    final isSpecialist = subtype != RoleSubtype.hospital && subtype != RoleSubtype.diagnostic;

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) async {
        if (didPop) return;
        if (!mounted) return;
        context.go('/sp/home');
      },
      child: Scaffold(
        backgroundColor: AppColors.background,
        body: Column(
        children: [
          AppHeroHeader(
            leading: IconButton(
              onPressed: () => context.go('/sp/home'),
              icon: const Icon(Icons.arrow_back_rounded, color: AppColors.textOnDark),
            ),
            title: profileTitle,
            subtitle: 'Manage your professional details',
          ),
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator(color: AppColors.primaryBlue))
                : SingleChildScrollView(
                    padding: AppSpacing.screenPadding.copyWith(top: 18, bottom: 40),
                    child: Column(
                      children: [
                        Center(
                          child: Stack(
                            children: [
                              CircleAvatar(
                                radius: 50,
                                backgroundColor: AppColors.primaryBlue.withAlpha(20),
                                backgroundImage: _buildProfileImage(),
                                child: _buildProfileImage() == null
                                    ? Text(
                                        _name.text.isNotEmpty ? _name.text[0].toUpperCase() : 'S',
                                        style: AppStyles.heading1.copyWith(color: AppColors.primaryBlue, fontSize: 36),
                                      )
                                    : null,
                              ),
                              Positioned(
                                bottom: 0,
                                right: 0,
                                child: Material(
                                  color: Colors.transparent,
                                  child: InkWell(
                                    onTap: _pickProfilePhoto,
                                    borderRadius: BorderRadius.circular(999),
                                    child: Container(
                                      padding: const EdgeInsets.all(6),
                                      decoration: const BoxDecoration(
                                        color: AppColors.primaryBlue,
                                        shape: BoxShape.circle,
                                      ),
                                      child: const Icon(Icons.camera_alt, color: Colors.white, size: 16),
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 10),
                        TextButton.icon(
                          onPressed: _pickProfilePhoto,
                          icon: const Icon(Icons.add_a_photo_outlined),
                          label: Text(_profilePhotoFile == null && (_profilePhotoUrl ?? '').isNotEmpty ? 'Change Profile Picture' : 'Add Profile Picture'),
                        ),
                        const SizedBox(height: 32),
                        AppCard(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const SectionHeaderRow(title: 'Personal Details'),
                              const SizedBox(height: 16),
                              LabeledTextField(controller: _name, label: 'Full Name', hintText: 'Enter your name'),
                              const SizedBox(height: 16),
                              LabeledTextField(controller: _email, label: 'Email Address', hintText: 'example@mail.com', keyboardType: TextInputType.emailAddress),
                              const SizedBox(height: 16),
                              LabeledTextField(controller: _mobile, label: 'Mobile Number', hintText: '+91', keyboardType: TextInputType.phone),
                            ],
                          ),
                        ),
                        const SizedBox(height: 16),
                        AppCard(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const SectionHeaderRow(title: 'Professional Details'),
                              const SizedBox(height: 16),
                              if (isSpecialist) ...[
                                DropdownButtonFormField<String>(
                                  key: ValueKey(_selectedSpecialtySlug ?? 'specialty'),
                                  initialValue: _selectedSpecialtySlug,
                                  decoration: InputDecoration(
                                    labelText: 'Specialty',
                                    hintText: 'Select Specialty',
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
                                  style: AppStyles.bodyLarge,
                                  items: _specialties
                                      .map((s) => DropdownMenuItem(
                                            value: s['slug']?.toString(),
                                            child: Text((s['label'] ?? s['name'] ?? '').toString()),
                                          ))
                                      .toList(),
                                  onChanged: (v) {
                                    setState(() {
                                      _selectedSpecialtySlug = v;
                                      final match = _specialties.where((s) => (s['slug']?.toString() ?? '') == (v ?? '')).toList();
                                      if (match.isNotEmpty) {
                                        _speciality.text = (match.first['label'] ?? match.first['name'] ?? '').toString();
                                      }
                                    });
                                  },
                                ),
                                if (_loadingSpecialties) ...[
                                  const SizedBox(height: 12),
                                  const Center(child: CircularProgressIndicator(color: AppColors.primaryBlue)),
                                ],
                                const SizedBox(height: 12),
                                OutlinedButton.icon(
                                  onPressed: _specialties.isEmpty ? null : _pickAdditionalSpecialties,
                                  icon: const Icon(Icons.add_rounded),
                                  label: Text(
                                    _additionalSpecialtyIds.isEmpty ? 'Add Additional Specialties' : 'Additional Specialties: ${_additionalSpecialtyIds.length}',
                                  ),
                                ),
                              ] else ...[
                                LabeledTextField(controller: _speciality, label: 'Speciality / Service Range', hintText: 'e.g. Cardiology, MRI Scanning'),
                              ],
                              const SizedBox(height: 16),
                              LabeledTextField(controller: _hospitalName, label: 'Hospital Name', hintText: 'Enter hospital name'),
                              const SizedBox(height: 16),
                              LabeledTextField(controller: _registrationNo, label: 'Registration No', hintText: 'Medical council registration'),
                              const SizedBox(height: 16),
                              LabeledTextField(controller: _councilName, label: 'Council Name', hintText: 'Council name'),
                              const SizedBox(height: 16),
                              LabeledTextField(controller: _yearsExperience, label: 'Years of Experience', hintText: 'e.g. 8', keyboardType: TextInputType.number),
                              const SizedBox(height: 16),
                              LabeledTextField(controller: _qualifications, label: 'Qualifications', hintText: 'Comma separated'),
                              const SizedBox(height: 16),
                              LabeledTextField(controller: _subSpecialties, label: 'Sub-specialties', hintText: 'Comma separated / text', maxLines: 2),
                              const SizedBox(height: 16),
                              LabeledTextField(controller: _keyProcedures, label: 'Key Procedures', hintText: 'Comma separated / text', maxLines: 2),
                              const SizedBox(height: 16),
                              LabeledTextField(controller: _languages, label: 'Languages', hintText: 'Comma separated'),
                              const SizedBox(height: 16),
                              SwitchListTile(
                                value: _consultationInPerson,
                                onChanged: (v) => setState(() => _consultationInPerson = v),
                                title: const Text('In-person Consultation'),
                                dense: true,
                                contentPadding: EdgeInsets.zero,
                              ),
                              SwitchListTile(
                                value: _consultationTeleconsult,
                                onChanged: (v) => setState(() => _consultationTeleconsult = v),
                                title: const Text('Teleconsultation'),
                                dense: true,
                                contentPadding: EdgeInsets.zero,
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 16),
                        AppCard(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const SectionHeaderRow(title: 'Clinic Address'),
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
                                const SizedBox(height: 14),
                              ],
                              LabeledTextField(controller: _clinicStreet, label: 'Street', hintText: 'Street / Building'),
                              const SizedBox(height: 16),
                              LabeledTextField(controller: _clinicArea, label: 'Area', hintText: 'Area / Locality'),
                              const SizedBox(height: 16),
                              LabeledTextField(controller: _clinicCity, label: 'City', hintText: 'City'),
                              const SizedBox(height: 16),
                              LabeledTextField(controller: _clinicPincode, label: 'Pincode', hintText: 'Pincode', keyboardType: TextInputType.number),
                              const SizedBox(height: 16),
                              LabeledTextField(controller: _bio, label: 'Bio', hintText: 'Write short professional bio', maxLines: 4),
                            ],
                          ),
                        ),
                        if (_allowVideos || _allowCertificates) ...[
                          const SizedBox(height: 16),
                          AppCard(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const SectionHeaderRow(title: 'Profile Content'),
                                const SizedBox(height: 16),
                                if (_allowVideos) ...[
                                  LabeledTextField(
                                    controller: _videos,
                                    label: 'Videos (URLs)',
                                    hintText: 'Comma or new-line separated URLs',
                                    maxLines: 3,
                                  ),
                                  const SizedBox(height: 16),
                                ],
                                if (_allowCertificates) ...[
                                  OutlinedButton.icon(
                                    onPressed: _pickCertificates,
                                    icon: const Icon(Icons.upload_file_rounded),
                                    label: const Text('Upload Certificates'),
                                  ),
                                  const SizedBox(height: 10),
                                  Text(
                                    'Existing: ${_certificateUrls.length} • New: ${_newCertificates.length}',
                                    style: AppStyles.caption,
                                  ),
                                ],
                              ],
                            ),
                          ),
                        ],
                        const SizedBox(height: 32),
                        PrimaryButton(
                          label: _saving ? 'Saving...' : 'Save Changes', 
                          onPressed: _saving ? null : _save,
                        ),
                        const SizedBox(height: 12),
                        OutlinedButton.icon(
                          onPressed: () => context.push('/support'),
                          icon: const Icon(Icons.support_agent_rounded),
                          label: const Text('Support Tickets'),
                        ),
                        const SizedBox(height: 16),
                        TextButton(
                          onPressed: () => ref.read(authStateProvider.notifier).logout(),
                          child: Text('Log Out', style: AppStyles.bodyMedium.copyWith(color: AppColors.statusError, fontWeight: FontWeight.bold)),
                        ),
                      ],
                    ),
                  ),
          ),
        ],
      ),
      bottomNavigationBar: const RoleBottomNav(currentIndex: 2, role: AppRole.specialist),
      ),
    );
  }

  ImageProvider? _buildProfileImage() {
    final localPath = _profilePhotoFile?.path;
    if (localPath != null && localPath.isNotEmpty) {
      return FileImage(File(localPath));
    }
    final url = (_profilePhotoUrl ?? '').trim();
    if (url.isNotEmpty) {
      return NetworkImage(url);
    }
    return null;
  }
}
