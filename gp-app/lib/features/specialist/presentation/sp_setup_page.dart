import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/primary_button.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/app/role.dart';
import 'package:gp_app/ui/widgets/app_hero_header.dart';
import 'package:gp_app/ui/widgets/app_card.dart';

class SpSetupPage extends ConsumerStatefulWidget {
  const SpSetupPage({super.key});
  @override
  ConsumerState<SpSetupPage> createState() => _SpSetupPageState();
}

class _SpSetupPageState extends ConsumerState<SpSetupPage> {
  final _formKey = GlobalKey<FormState>();
  String? _speciality;
  String? _specialtyCode;
  List<Map<String, dynamic>> _specialties = [];
  bool _loadingSpecialties = false;
  bool _submitting = false;

  @override
  void initState() {
    super.initState();

    Future.microtask(() async {
      final subtype = ref.read(selectedSubtypeProvider);
      final isSpecialist = subtype != RoleSubtype.hospital && subtype != RoleSubtype.diagnostic;
      if (!isSpecialist) return;

      setState(() => _loadingSpecialties = true);
      try {
        final items = await ref.read(specialistRepositoryProvider).listSpecialties();
        if (mounted) {
          setState(() {
            _specialties = items;
            _loadingSpecialties = false;
          });
        }
      } catch (_) {
        if (mounted) {
          setState(() => _loadingSpecialties = false);
        }
      }
    });
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _submitting = true);
    try {
      await ref.read(specialistRepositoryProvider).setup({
        'speciality': _speciality,
        if (_specialtyCode != null && _specialtyCode!.isNotEmpty) 'specialty_code': _specialtyCode,
      });
      if (mounted) {
        Navigator.pop(context);
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Setup failed')));
      }
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final subtype = ref.watch(selectedSubtypeProvider);
    
    final setupTitle = switch (subtype) {
      RoleSubtype.hospital => 'Hospital Setup',
      RoleSubtype.diagnostic => 'Diagnostic Setup',
      _ => 'Specialist Setup',
    };

    final dropdownLabel = switch (subtype) {
      RoleSubtype.diagnostic => 'Primary Service Area',
      _ => 'Primary Speciality',
    };

    final dropdownHint = switch (subtype) {
      RoleSubtype.diagnostic => 'Select Category',
      _ => 'Select Speciality',
    };

    final items = switch (subtype) {
      RoleSubtype.diagnostic => [
        'Radiology & Imaging',
        'Pathology / Laboratory',
        'Cardiology Diagnostics',
        'Neurology Diagnostics',
        'Multi-Service Center',
      ],
      RoleSubtype.hospital => [
        'Multi-Specialty Hospital',
        'Surgical Center',
        'Maternity & Child Care',
        'Emergency & Critical Care',
      ],
      _ => _specialties.map((s) => (s['label'] ?? s['name'] ?? '').toString()).where((v) => v.isNotEmpty).toList(),
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
            title: setupTitle,
            subtitle: 'Complete your profile information',
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
                      Text('Account Configuration', style: AppStyles.heading2),
                      const SizedBox(height: 8),
                      Text(
                        'This information helps GPs find your services accurately.',
                        style: AppStyles.bodySmall.copyWith(color: AppColors.textSecondary),
                      ),
                      const SizedBox(height: 24),
                      DropdownButtonFormField<String>(
                        key: ValueKey(_speciality ?? 'speciality'),
                        initialValue: _speciality,
                        decoration: InputDecoration(
                          labelText: dropdownLabel,
                          hintText: dropdownHint,
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
                        items: items.map((t) => DropdownMenuItem(value: t, child: Text(t))).toList(),
                        onChanged: (v) {
                          setState(() {
                            _speciality = v;
                            final match = _specialties.cast<Map<String, dynamic>>().where((s) {
                              final label = (s['label'] ?? s['name'] ?? '').toString();
                              return label == v;
                            }).toList();
                            _specialtyCode = match.isNotEmpty ? match.first['slug']?.toString() : null;
                          });
                        },
                        validator: (v) => v == null || v.isEmpty ? 'Required' : null,
                      ),
                      if (subtype != RoleSubtype.hospital && subtype != RoleSubtype.diagnostic && _loadingSpecialties) ...[
                        const SizedBox(height: 12),
                        const Center(child: CircularProgressIndicator(color: AppColors.primaryBlue)),
                      ],
                      const SizedBox(height: 40),
                      PrimaryButton(
                        label: _submitting ? 'Setting up...' : 'Continue to Dashboard', 
                        onPressed: _submitting ? null : _submit,
                      ),
                      const SizedBox(height: 16),
                      Center(
                        child: Text(
                          'You can update these details later in Profile settings',
                          style: AppStyles.caption,
                        ),
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
