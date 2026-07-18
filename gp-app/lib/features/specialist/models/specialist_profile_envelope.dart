import 'package:gp_app/features/specialist/models/specialist_profile.dart';

class SpecialistProfileEnvelope {
  final SpecialistProfile profile;
  final bool allowVideos;
  final bool allowCertificates;

  const SpecialistProfileEnvelope({
    required this.profile,
    required this.allowVideos,
    required this.allowCertificates,
  });

  factory SpecialistProfileEnvelope.fromJson(Map<String, dynamic> json) {
    final profileJson = (json['profile'] is Map<String, dynamic>)
        ? (json['profile'] as Map<String, dynamic>)
        : (json);
    final flags = (json['feature_flags'] is Map<String, dynamic>)
        ? (json['feature_flags'] as Map<String, dynamic>)
        : <String, dynamic>{};

    return SpecialistProfileEnvelope(
      profile: SpecialistProfile.fromJson(profileJson),
      allowVideos: flags['allow_profile_videos_for_specialists'] == true,
      allowCertificates: flags['allow_profile_certificates_for_specialists'] == true,
    );
  }
}

