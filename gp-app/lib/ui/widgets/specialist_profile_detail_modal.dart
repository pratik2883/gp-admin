import 'package:flutter/material.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:url_launcher/url_launcher.dart';

class SpecialistProfileDetailModal extends StatelessWidget {
  final Map<String, dynamic> specialist;
  final VoidCallback? onReferPressed;

  const SpecialistProfileDetailModal({
    super.key,
    required this.specialist,
    this.onReferPressed,
  });

  static Future<void> show(
    BuildContext context, {
    required Map<String, dynamic> specialist,
    VoidCallback? onReferPressed,
  }) {
    return showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => SpecialistProfileDetailModal(
        specialist: specialist,
        onReferPressed: onReferPressed,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final mediaQuery = MediaQuery.of(context);
    final name = (specialist['name'] as String?)?.trim() ?? 'Doctor Profile';
    final speciality = (specialist['speciality'] as String?)?.trim() ?? (specialist['primary_specialization'] as String?)?.trim() ?? '';
    final area = (specialist['area_name'] as String?)?.trim() ?? (specialist['clinic_area'] as String?)?.trim() ?? '';
    final hospital = (specialist['hospital_name'] as String?)?.trim() ?? (specialist['clinic_name'] as String?)?.trim() ?? '';
    final address = (specialist['clinic_address'] as String?)?.trim() ?? (specialist['address'] as String?)?.trim() ?? '';
    final exp = specialist['years_of_experience']?.toString();
    final isPremium = specialist['is_premium'] == true || specialist['is_premium'] == 1;
    final isSuper = specialist['is_super_specialist'] == true || specialist['is_super_specialist'] == 1;
    final timings = (specialist['clinic_timings'] as String?)?.trim() ?? (specialist['hospital_visiting_hours'] as String?)?.trim();
    final hospitalVisiting = (specialist['hospital_visiting_hours'] as String?)?.trim();
    final photo = (specialist['profile_photo'] as String?) ?? (specialist['photo_url'] as String?) ?? (specialist['photo'] as String?);
    final bio = (specialist['bio'] as String?)?.trim();
    final registrationNo = (specialist['registration_no'] as String?)?.trim() ?? (specialist['medical_council_registration_no'] as String?)?.trim();
    final councilName = (specialist['council_name'] as String?)?.trim() ?? (specialist['medical_council_name'] as String?)?.trim();

    final showMobile = specialist['show_mobile_number'] == true || specialist['show_mobile_number'] == 1;
    final showWhatsapp = specialist['show_whatsapp_number'] == true || specialist['show_whatsapp_number'] == 1;
    final mobile = showMobile ? (specialist['mobile'] as String?)?.trim() : null;
    final whatsapp = showWhatsapp ? ((specialist['whatsapp_number'] as String?)?.trim() ?? mobile) : null;

    final qualificationsList = specialist['qualifications'] is List
        ? (specialist['qualifications'] as List).map((e) => e.toString()).where((e) => e.isNotEmpty).join(', ')
        : (specialist['additional_qualifications'] is List
            ? (specialist['additional_qualifications'] as List).map((e) => e.toString()).where((e) => e.isNotEmpty).join(', ')
            : null);

    final languagesList = specialist['languages'] is List
        ? (specialist['languages'] as List).map((e) => e.toString()).where((e) => e.isNotEmpty).join(', ')
        : null;

    return Container(
      constraints: BoxConstraints(
        maxHeight: mediaQuery.size.height * 0.85,
      ),
      decoration: const BoxDecoration(
        color: AppColors.background,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Header Drag Handle
          Container(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
            decoration: const BoxDecoration(
              gradient: AppColors.primaryGradient,
              borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
            ),
            child: Column(
              children: [
                Container(
                  width: 40,
                  height: 4,
                  margin: const EdgeInsets.only(bottom: 12),
                  decoration: BoxDecoration(
                    color: Colors.white.withAlpha(120),
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
                Row(
                  children: [
                    CircleAvatar(
                      radius: 32,
                      backgroundColor: Colors.white.withAlpha(50),
                      backgroundImage: (photo != null && photo.trim().isNotEmpty) ? NetworkImage(photo.trim()) : null,
                      child: (photo == null || photo.trim().isEmpty)
                          ? Text(
                              name.isNotEmpty ? name[0].toUpperCase() : 'D',
                              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 24),
                            )
                          : null,
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  name,
                                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 18),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                              if (isPremium)
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                  margin: const EdgeInsets.only(left: 6),
                                  decoration: BoxDecoration(
                                    color: Colors.amber.shade300,
                                    borderRadius: BorderRadius.circular(4),
                                  ),
                                  child: const Text('PREMIUM', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.black87)),
                                ),
                              if (isSuper)
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                  margin: const EdgeInsets.only(left: 6),
                                  decoration: BoxDecoration(
                                    color: Colors.teal.shade300,
                                    borderRadius: BorderRadius.circular(4),
                                  ),
                                  child: const Text('SUPER', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.black87)),
                                ),
                            ],
                          ),
                          if (speciality.isNotEmpty) ...[
                            const SizedBox(height: 2),
                            Text(
                              speciality,
                              style: TextStyle(color: Colors.white.withAlpha(220), fontSize: 14, fontWeight: FontWeight.w500),
                            ),
                          ],
                          if (exp != null && exp.isNotEmpty) ...[
                            const SizedBox(height: 2),
                            Text(
                              '$exp Years Experience',
                              style: TextStyle(color: Colors.white.withAlpha(180), fontSize: 12),
                            ),
                          ],
                        ],
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close_rounded, color: Colors.white),
                      onPressed: () => Navigator.of(context).pop(),
                    ),
                  ],
                ),
              ],
            ),
          ),

          // Scrollable Body
          Flexible(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Quick Contact Buttons
                  if (mobile != null || whatsapp != null) ...[
                    Row(
                      children: [
                        if (mobile != null && mobile.isNotEmpty)
                          Expanded(
                            child: ElevatedButton.icon(
                              onPressed: () => launchUrl(Uri.parse('tel:$mobile')),
                              icon: const Icon(Icons.phone, size: 18),
                              label: const Text('Call Doctor'),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: AppColors.primaryBlue,
                                foregroundColor: Colors.white,
                                padding: const EdgeInsets.symmetric(vertical: 10),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                              ),
                            ),
                          ),
                        if (mobile != null && whatsapp != null) const SizedBox(width: 10),
                        if (whatsapp != null && whatsapp.isNotEmpty)
                          Expanded(
                            child: ElevatedButton.icon(
                              onPressed: () {
                                final clean = whatsapp.replaceAll(RegExp(r'[^\d+]'), '');
                                launchUrl(Uri.parse('https://wa.me/$clean'), mode: LaunchMode.externalApplication);
                              },
                              icon: const Icon(Icons.chat, size: 18),
                              label: const Text('WhatsApp'),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFF25D366),
                                foregroundColor: Colors.white,
                                padding: const EdgeInsets.symmetric(vertical: 10),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                              ),
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 16),
                  ],

                  // Clinic / Hospital Information
                  _buildSectionCard(
                    title: 'Practice & Location',
                    icon: Icons.local_hospital_rounded,
                    children: [
                      if (hospital.isNotEmpty)
                        _buildInfoRow(Icons.business_rounded, 'Hospital / Clinic', hospital),
                      if (area.isNotEmpty)
                        _buildInfoRow(Icons.location_on_outlined, 'Area', area),
                      if (address.isNotEmpty)
                        _buildInfoRow(Icons.map_outlined, 'Address', address),
                    ],
                  ),
                  const SizedBox(height: 12),

                  // Visiting Hours & Timings
                  if ((timings != null && timings.isNotEmpty) || (hospitalVisiting != null && hospitalVisiting.isNotEmpty)) ...[
                    _buildSectionCard(
                      title: 'Timings & Visiting Hours',
                      icon: Icons.access_time_rounded,
                      children: [
                        if (timings != null && timings.isNotEmpty)
                          _buildInfoRow(Icons.schedule_rounded, 'Clinic Visit Timings', timings),
                        if (hospitalVisiting != null && hospitalVisiting.isNotEmpty && hospitalVisiting != timings)
                          _buildInfoRow(Icons.store_rounded, 'Hospital Visiting Hours', hospitalVisiting),
                      ],
                    ),
                    const SizedBox(height: 12),
                  ],

                  // Qualifications & Registration
                  if (qualificationsList != null || registrationNo != null || languagesList != null) ...[
                    _buildSectionCard(
                      title: 'Qualifications & Credentials',
                      icon: Icons.verified_user_rounded,
                      children: [
                        if (qualificationsList != null)
                          _buildInfoRow(Icons.school_rounded, 'Qualifications', qualificationsList),
                        if (registrationNo != null)
                          _buildInfoRow(Icons.badge_rounded, 'Registration No.', '$registrationNo${councilName != null ? ' ($councilName)' : ''}'),
                        if (languagesList != null)
                          _buildInfoRow(Icons.translate_rounded, 'Languages', languagesList),
                      ],
                    ),
                    const SizedBox(height: 12),
                  ],

                  // About / Bio
                  if (bio != null && bio.isNotEmpty) ...[
                    _buildSectionCard(
                      title: 'About Specialist',
                      icon: Icons.info_outline_rounded,
                      children: [
                        Text(bio, style: const TextStyle(fontSize: 13, color: AppColors.textPrimary, height: 1.4)),
                      ],
                    ),
                    const SizedBox(height: 12),
                  ],
                ],
              ),
            ),
          ),

          // Bottom Action Button
          if (onReferPressed != null)
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withAlpha(15),
                    blurRadius: 10,
                    offset: const Offset(0, -4),
                  ),
                ],
              ),
              child: SafeArea(
                child: SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: () {
                      Navigator.of(context).pop();
                      onReferPressed!();
                    },
                    icon: const Icon(Icons.send_rounded),
                    label: Text('Refer Patient to $name'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primaryBlue,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildSectionCard({
    required String title,
    required IconData icon,
    required List<Widget> children,
  }) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.borderLight),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 18, color: AppColors.primaryBlue),
              const SizedBox(width: 8),
              Text(
                title,
                style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
              ),
            ],
          ),
          const Divider(height: 16),
          ...children,
        ],
      ),
    );
  }

  Widget _buildInfoRow(IconData icon, String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 15, color: AppColors.textSecondary),
          const SizedBox(width: 8),
          Expanded(
            child: RichText(
              text: TextSpan(
                style: const TextStyle(fontSize: 13, color: AppColors.textPrimary),
                children: [
                  TextSpan(text: '$label: ', style: const TextStyle(fontWeight: FontWeight.w600, color: AppColors.textSecondary)),
                  TextSpan(text: value),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
