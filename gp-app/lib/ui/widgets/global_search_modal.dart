import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/app/role.dart';
import 'package:gp_app/features/search/data/global_search_repository.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_card.dart';
import 'package:gp_app/ui/widgets/states.dart';

class GlobalSearchModal extends ConsumerStatefulWidget {
  final String initialQuery;
  const GlobalSearchModal({super.key, this.initialQuery = ''});

  static Future<void> show(BuildContext context, {String initialQuery = ''}) {
    return showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => GlobalSearchModal(initialQuery: initialQuery),
    );
  }

  @override
  ConsumerState<GlobalSearchModal> createState() => _GlobalSearchModalState();
}

class _GlobalSearchModalState extends ConsumerState<GlobalSearchModal> {
  late TextEditingController _searchCtrl;
  Timer? _debounceTimer;
  bool _isLoading = false;
  String? _errorMessage;
  GlobalSearchResult? _searchResult;
  String _selectedTab = 'all';

  @override
  void initState() {
    super.initState();
    _searchCtrl = TextEditingController(text: widget.initialQuery);
    if (widget.initialQuery.trim().isNotEmpty) {
      _performSearch(widget.initialQuery.trim());
    }
  }

  @override
  void dispose() {
    _debounceTimer?.cancel();
    _searchCtrl.dispose();
    super.dispose();
  }

  void _onSearchChanged(String val) {
    _debounceTimer?.cancel();
    _debounceTimer = Timer(const Duration(milliseconds: 300), () {
      if (mounted) {
        _performSearch(val.trim());
      }
    });
  }

  Future<void> _performSearch(String query) async {
    if (query.isEmpty) {
      setState(() {
        _searchResult = null;
        _isLoading = false;
        _errorMessage = null;
      });
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final repo = ref.read(globalSearchRepositoryProvider);
      final result = await repo.search(query, type: _selectedTab);
      if (mounted) {
        setState(() {
          _searchResult = result;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _errorMessage = 'Search failed. Please check connection.';
          _isLoading = false;
        });
      }
    }
  }

  List<String> _getAllowedTypes() {
    if (_searchResult != null && _searchResult!.allowedTypes.isNotEmpty) {
      return _searchResult!.allowedTypes;
    }
    final role = ref.read(selectedRoleProvider);
    if (role == AppRole.gp) {
      return ['specialist', 'hospital', 'diagnostic_center'];
    } else if (role == AppRole.specialist) {
      return ['hospital', 'diagnostic_center'];
    }
    return ['specialist', 'hospital', 'diagnostic_center'];
  }

  @override
  Widget build(BuildContext context) {
    final mediaQuery = MediaQuery.of(context);
    final topPadding = mediaQuery.padding.top + 12;
    final allowedTypes = _getAllowedTypes();

    return Container(
      height: mediaQuery.size.height - topPadding,
      decoration: const BoxDecoration(
        color: AppColors.background,
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Column(
        children: [
          // Header Drag Handle & Top Search Bar
          Container(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
            decoration: const BoxDecoration(
              gradient: AppColors.primaryGradient,
              borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
            ),
            child: Column(
              children: [
                Container(
                  width: 36,
                  height: 4,
                  margin: const EdgeInsets.only(bottom: 12),
                  decoration: BoxDecoration(
                    color: Colors.white.withAlpha(120),
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
                Row(
                  children: [
                    IconButton(
                      icon: const Icon(Icons.arrow_back_rounded, color: AppColors.textOnDark),
                      onPressed: () => Navigator.of(context).pop(),
                    ),
                    Expanded(
                      child: TextField(
                        controller: _searchCtrl,
                        autofocus: true,
                        style: const TextStyle(color: Colors.white, fontSize: 16),
                        cursorColor: Colors.white,
                        onChanged: _onSearchChanged,
                        decoration: InputDecoration(
                          hintText: 'Search specialists, hospitals, centers...',
                          hintStyle: TextStyle(color: Colors.white.withAlpha(180), fontSize: 15),
                          filled: true,
                          fillColor: Colors.white.withAlpha(40),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(30),
                            borderSide: BorderSide.none,
                          ),
                          prefixIcon: const Icon(Icons.search, color: Colors.white),
                          suffixIcon: _searchCtrl.text.isNotEmpty
                              ? IconButton(
                                  icon: const Icon(Icons.clear, color: Colors.white),
                                  onPressed: () {
                                    _searchCtrl.clear();
                                    _onSearchChanged('');
                                  },
                                )
                              : null,
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),

          // Filter Chips Row
          if (allowedTypes.isNotEmpty)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              color: Colors.white,
              child: SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: Row(
                  children: [
                    _buildFilterChip('all', 'All Results', _searchResult?.counts['total']),
                    if (allowedTypes.contains('specialist'))
                      _buildFilterChip('specialist', 'Specialists', _searchResult?.counts['specialists']),
                    if (allowedTypes.contains('hospital'))
                      _buildFilterChip('hospital', 'Hospitals', _searchResult?.counts['hospitals']),
                    if (allowedTypes.contains('diagnostic_center'))
                      _buildFilterChip('diagnostic_center', 'Diagnostics', _searchResult?.counts['diagnostic_centers']),
                  ],
                ),
              ),
            ),

          const Divider(height: 1),

          // Search Results Body
          Expanded(
            child: _buildResultsBody(allowedTypes),
          ),
        ],
      ),
    );
  }

  Widget _buildFilterChip(String value, String label, int? count) {
    final isSelected = _selectedTab == value;
    final displayLabel = count != null && count > 0 ? '$label ($count)' : label;

    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: ChoiceChip(
        label: Text(displayLabel),
        selected: isSelected,
        onSelected: (selected) {
          if (selected) {
            setState(() {
              _selectedTab = value;
            });
            if (_searchCtrl.text.trim().isNotEmpty) {
              _performSearch(_searchCtrl.text.trim());
            }
          }
        },
        selectedColor: AppColors.primaryBlue,
        backgroundColor: Colors.grey.shade100,
        labelStyle: TextStyle(
          color: isSelected ? Colors.white : AppColors.textPrimary,
          fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
          fontSize: 13,
        ),
      ),
    );
  }

  Widget _buildResultsBody(List<String> allowedTypes) {
    if (_searchCtrl.text.trim().isEmpty) {
      return const Center(
        child: Padding(
          padding: EdgeInsets.all(32),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.search_rounded, size: 64, color: AppColors.textSecondary),
              SizedBox(height: 16),
              Text(
                'Search across Specialist Connect Pro',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
              ),
              SizedBox(height: 8),
              Text(
                'Type a name, specialty, location, or diagnostic service to begin searching.',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 13, color: AppColors.textSecondary),
              ),
            ],
          ),
        ),
      );
    }

    if (_isLoading) {
      return const Center(
        child: Padding(
          padding: EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              CircularProgressIndicator(color: AppColors.primaryBlue),
              SizedBox(height: 16),
              Text('Searching live results...', style: TextStyle(color: AppColors.textSecondary)),
            ],
          ),
        ),
      );
    }

    if (_errorMessage != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: ErrorState(
            title: 'Search Error',
            message: _errorMessage!,
            onRetry: () => _performSearch(_searchCtrl.text.trim()),
          ),
        ),
      );
    }

    if (_searchResult == null || _searchResult!.counts['total'] == 0) {
      return const Center(
        child: Padding(
          padding: EdgeInsets.all(32),
          child: EmptyState(
            message: 'No results found',
            subtitle: 'Try checking for typos or searching a different specialty or location.',
            icon: Icons.search_off_rounded,
          ),
        ),
      );
    }

    final specialists = _searchResult!.specialists;
    final hospitals = _searchResult!.hospitals;
    final diagnostics = _searchResult!.diagnosticCenters;

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        if (specialists.isNotEmpty && (_selectedTab == 'all' || _selectedTab == 'specialist')) ...[
          _buildSectionHeader('Specialists', specialists.length, Icons.person_search_rounded),
          ...specialists.map((sp) => _buildSpecialistCard(sp)),
          const SizedBox(height: 16),
        ],
        if (hospitals.isNotEmpty && (_selectedTab == 'all' || _selectedTab == 'hospital')) ...[
          _buildSectionHeader('Hospitals', hospitals.length, Icons.local_hospital_rounded),
          ...hospitals.map((h) => _buildHospitalCard(h)),
          const SizedBox(height: 16),
        ],
        if (diagnostics.isNotEmpty && (_selectedTab == 'all' || _selectedTab == 'diagnostic_center')) ...[
          _buildSectionHeader('Diagnostic Centers', diagnostics.length, Icons.biotech_rounded),
          ...diagnostics.map((dx) => _buildDiagnosticCard(dx)),
          const SizedBox(height: 16),
        ],
      ],
    );
  }

  Widget _buildSectionHeader(String title, int count, IconData icon) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10, top: 4),
      child: Row(
        children: [
          Icon(icon, size: 20, color: AppColors.primaryBlue),
          const SizedBox(width: 8),
          Text(
            title,
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
          ),
          const SizedBox(width: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
            decoration: BoxDecoration(
              color: AppColors.primaryBlue.withAlpha(20),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Text(
              '$count',
              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.primaryBlue),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSpecialistCard(Map<String, dynamic> sp) {
    final name = sp['name'] as String? ?? 'Specialist';
    final speciality = sp['speciality'] as String? ?? '';
    final area = sp['area_name'] as String? ?? '';
    final hospital = sp['hospital_name'] as String? ?? '';
    final exp = sp['years_of_experience']?.toString();
    final isPremium = sp['is_premium'] == true;
    final id = sp['id'];
    final timings = (sp['clinic_timings'] as String?) ?? (sp['hospital_visiting_hours'] as String?);

    final photo = (sp['profile_photo'] as String?) ?? (sp['photo_url'] as String?);

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: AppCard(
        onTap: () {
          Navigator.of(context).pop();
          final role = ref.read(selectedRoleProvider);
          if (role == AppRole.gp) {
            context.push('/gp/referrals/new', extra: {'specialist_id': id});
          } else {
            context.push('/gp/specialists/$id');
          }
        },
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            children: [
              CircleAvatar(
                radius: 24,
                backgroundColor: AppColors.primaryBlue.withAlpha(30),
                backgroundImage: (photo != null && photo.trim().isNotEmpty) ? NetworkImage(photo.trim()) : null,
                child: (photo == null || photo.trim().isEmpty)
                    ? Text(
                        name.isNotEmpty ? name[0].toUpperCase() : 'D',
                        style: const TextStyle(color: AppColors.primaryBlue, fontWeight: FontWeight.bold, fontSize: 18),
                      )
                    : null,
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            name,
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        if (isPremium)
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                            decoration: BoxDecoration(
                              color: Colors.amber.shade100,
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: const Text('PREMIUM', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.amber)),
                          ),
                      ],
                    ),
                    if (speciality.isNotEmpty)
                      Text(speciality, style: const TextStyle(fontSize: 13, color: AppColors.primaryBlue, fontWeight: FontWeight.w500)),
                    const SizedBox(height: 2),
                    Row(
                      children: [
                        if (area.isNotEmpty) ...[
                          const Icon(Icons.location_on_outlined, size: 14, color: AppColors.textSecondary),
                          const SizedBox(width: 2),
                          Text(area, style: const TextStyle(fontSize: 12, color: AppColors.textSecondary)),
                          const SizedBox(width: 8),
                        ],
                        if (exp != null) ...[
                          const Icon(Icons.work_outline, size: 14, color: AppColors.textSecondary),
                          const SizedBox(width: 2),
                          Text('$exp yrs exp', style: const TextStyle(fontSize: 12, color: AppColors.textSecondary)),
                        ],
                      ],
                    ),
                    if (hospital.isNotEmpty)
                      Text(hospital, style: TextStyle(fontSize: 12, color: Colors.grey.shade600), maxLines: 1, overflow: TextOverflow.ellipsis),
                    if (timings != null && timings.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Row(
                        children: [
                          const Icon(Icons.access_time_rounded, size: 12, color: AppColors.primaryBlue),
                          const SizedBox(width: 3),
                          Expanded(
                            child: Text(
                              timings,
                              style: const TextStyle(fontSize: 11, color: AppColors.primaryBlue, fontWeight: FontWeight.w600),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ],
                ),
              ),
              const Icon(Icons.chevron_right_rounded, color: AppColors.textSecondary),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildHospitalCard(Map<String, dynamic> h) {
    final name = h['name'] as String? ?? 'Hospital';
    final type = h['hospital_type'] as String? ?? 'Hospital';
    final address = h['address'] as String? ?? '';
    final area = h['area_name'] as String? ?? '';
    final id = h['id'];

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: AppCard(
        onTap: () {
          Navigator.of(context).pop();
          context.push('/gp/referrals/new', extra: {'hospital_id': id, 'referral_type': 'hospital'});
        },
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            children: [
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: AppColors.secondaryTeal.withAlpha(20),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(Icons.local_hospital_rounded, color: AppColors.secondaryTeal, size: 26),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15), maxLines: 1, overflow: TextOverflow.ellipsis),
                    Text(type, style: const TextStyle(fontSize: 13, color: AppColors.secondaryTeal, fontWeight: FontWeight.w500)),
                    if (address.isNotEmpty || area.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Row(
                        children: [
                          const Icon(Icons.location_on_outlined, size: 14, color: AppColors.textSecondary),
                          const SizedBox(width: 2),
                          Expanded(
                            child: Text(
                              area.isNotEmpty ? area : address,
                              style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ],
                ),
              ),
              const Icon(Icons.chevron_right_rounded, color: AppColors.textSecondary),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildDiagnosticCard(Map<String, dynamic> dx) {
    final name = dx['name'] as String? ?? 'Diagnostic Center';
    final type = dx['center_type'] as String? ?? 'Diagnostic Center';
    final address = dx['address'] as String? ?? '';
    final area = dx['area_name'] as String? ?? '';
    final services = (dx['service_names'] as List<dynamic>?)?.map((e) => e.toString()).toList() ?? [];
    final id = dx['id'];

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: AppCard(
        onTap: () {
          Navigator.of(context).pop();
          context.push('/gp/referrals/new', extra: {'diagnostic_center_id': id, 'referral_type': 'diagnostic'});
        },
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            children: [
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: Colors.purple.shade50,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(Icons.biotech_rounded, color: Colors.purple.shade700, size: 26),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15), maxLines: 1, overflow: TextOverflow.ellipsis),
                    Text(type, style: TextStyle(fontSize: 13, color: Colors.purple.shade700, fontWeight: FontWeight.w500)),
                    if (services.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Text('Services: ${services.join(', ')}', style: TextStyle(fontSize: 12, color: Colors.grey.shade600), maxLines: 1, overflow: TextOverflow.ellipsis),
                    ],
                    if (area.isNotEmpty || address.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Row(
                        children: [
                          const Icon(Icons.location_on_outlined, size: 14, color: AppColors.textSecondary),
                          const SizedBox(width: 2),
                          Expanded(
                            child: Text(
                              area.isNotEmpty ? area : address,
                              style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ],
                ),
              ),
              const Icon(Icons.chevron_right_rounded, color: AppColors.textSecondary),
            ],
          ),
        ),
      ),
    );
  }
}
