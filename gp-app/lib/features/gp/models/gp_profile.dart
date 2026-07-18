class GpProfile {
  final String fullName;
  final String specialityTitle;
  final String registrationNumber;
  final int activeReferralsCount;
  final String successRate;

  GpProfile({
    required this.fullName,
    required this.specialityTitle,
    required this.registrationNumber,
    required this.activeReferralsCount,
    required this.successRate,
  });

  factory GpProfile.fromJson(Map<String, dynamic> json) {
    return GpProfile(
      fullName: json['name'] ?? '',
      specialityTitle: json['speciality'] ?? 'General Practitioner',
      registrationNumber: json['reg_no'] ?? 'REG-000000',
      activeReferralsCount: json['active_referrals'] ?? 0,
      successRate: json['success_rate'] ?? '0%',
    );
  }

  // Mock data for initial UI implementation
  static GpProfile mock() {
    return GpProfile(
      fullName: 'Dr. Julian Sterling',
      specialityTitle: 'Senior Consultant Cardiologist',
      registrationNumber: 'REG-99203841',
      activeReferralsCount: 24,
      successRate: '98%',
    );
  }
}
