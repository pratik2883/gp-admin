import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:gp_app/core/http/dio_client.dart';
import 'package:gp_app/features/auth/models/user.dart';
import 'package:gp_app/features/specialist/models/lead.dart';
import 'package:gp_app/features/specialist/models/lead_list_response.dart';
import 'package:gp_app/features/specialist/models/lead_detail_response.dart';
import 'package:gp_app/features/specialist/models/specialist_profile.dart';
import 'package:gp_app/features/specialist/models/specialist_profile_envelope.dart';
import 'package:gp_app/features/specialist/models/diagnostic_locations_response.dart';

class SpecialistRepository {
  final Dio _dio;
  SpecialistRepository(DioClient client) : _dio = client.dio;

  Future<List<Map<String, dynamic>>> listSpecialties() async {
    final res = await _dio.get('/api/public/specialties');
    final data = res.data;
    if (data is! List) return [];
    if (data.isEmpty) return [];

    final first = data.first;
    if (first is String) {
      return data
          .whereType<String>()
          .map((s) => {
                'id': s,
                'slug': s,
                'label': s,
                'name': s,
              })
          .toList();
    }

    if (first is Map) {
      return data.cast<Map>().map((m) => m.cast<String, dynamic>()).toList();
    }

    return [];
  }

  Future<LeadListResponse> fetchLeads({String? status, String? q, int page = 1, int perPage = 20}) async {
    final res = await _dio.get('/api/specialist/leads', queryParameters: {
      if (status != null && status.isNotEmpty) 'status': status,
      if (q != null && q.isNotEmpty) 'q': q,
      'page': page,
      'per_page': perPage,
    });
    return LeadListResponse.fromJson(res.data as Map<String, dynamic>);
  }

  Future<LeadDetailResponse> fetchLeadDetail(String id) async {
    final res = await _dio.get('/api/specialist/leads/$id');
    
    // Safely unwrap data from Laravel Resource wrappers
    var data = res.data as Map<String, dynamic>;
    if (data.containsKey('data') && data['data'] is Map<String, dynamic>) {
      data = data['data'] as Map<String, dynamic>;
    }
    
    // If the response is the lead directly, wrap it for LeadDetailResponse
    if (!data.containsKey('lead')) {
      data = {'lead': data};
    }
    
    return LeadDetailResponse.fromJson(data);
  }

  Future<Lead> updateLeadStatus(String id, String status) async {
    final res = await _dio.patch('/api/specialist/leads/$id/status', data: {'status': status});
    
    // Safely unwrap data
    var data = res.data as Map<String, dynamic>;
    if (data.containsKey('data') && data['data'] is Map<String, dynamic>) {
      data = data['data'] as Map<String, dynamic>;
    } else if (data.containsKey('lead') && data['lead'] is Map<String, dynamic>) {
      data = data['lead'] as Map<String, dynamic>;
    }
    
    return Lead.fromJson(data);
  }

  Future<SpecialistProfile> fetchProfile() async {
    final env = await fetchProfileEnvelope();
    return env.profile;
  }

  Future<SpecialistProfileEnvelope> fetchProfileEnvelope() async {
    final res = await _dio.get('/api/specialist/profile');
    final data = res.data as Map<String, dynamic>;
    return SpecialistProfileEnvelope.fromJson(data);
  }

  void _addFormField(FormData form, String key, dynamic value) {
    if (value == null) return;
    if (value is List) {
      for (final item in value) {
        _addFormField(form, '$key[]', item);
      }
      return;
    }
    if (value is Map) {
      value.forEach((k, v) {
        _addFormField(form, '$key[$k]', v);
      });
      return;
    }
    form.fields.add(MapEntry(key, value.toString()));
  }

  Future<User> updateProfile(
    Map<String, dynamic> fields, {
    List<String> certificatePaths = const [],
    String? profilePhotoPath,
  }) async {
    final form = FormData();
    fields.forEach((k, v) => _addFormField(form, k, v));
    for (final p in certificatePaths) {
      form.files.add(MapEntry('certificates[]', await MultipartFile.fromFile(p)));
    }
    if (profilePhotoPath != null && profilePhotoPath.isNotEmpty) {
      form.files.add(MapEntry('profile_photo', await MultipartFile.fromFile(profilePhotoPath)));
    }

    final res = await _dio.patch('/api/specialist/profile', data: form);
    final data = res.data is Map<String, dynamic> && (res.data as Map<String, dynamic>)['user'] != null
        ? (res.data as Map<String, dynamic>)['user'] as Map<String, dynamic>
        : res.data as Map<String, dynamic>;
    return User.fromJson(data);
  }

  Future<Map<String, dynamic>> fetchPublicFeatureFlags() async {
    final platform = switch (defaultTargetPlatform) {
      TargetPlatform.iOS => 'ios',
      _ => 'android',
    };
    final res = await _dio.get('/api/public/feature-flags', queryParameters: {
      'platform': platform,
    });
    final data = res.data;
    if (data is Map<String, dynamic>) return data;
    return <String, dynamic>{};
  }

  Future<Map<String, dynamic>> fetchSubscriptionPlans(String category) async {
    final res = await _dio.get('/api/public/subscription-plans', queryParameters: {
      'category': category,
    });
    final data = res.data;
    if (data is Map<String, dynamic>) return data;
    if (data is Map) return data.cast<String, dynamic>();
    return <String, dynamic>{};
  }

  Future<Map<String, dynamic>> register(
    Map<String, dynamic> fields, {
    List<String> certificatePaths = const [],
    String? profilePhotoPath,
  }) async {
    final form = FormData();
    fields.forEach((k, v) => _addFormField(form, k, v));

    for (final p in certificatePaths) {
      form.files.add(MapEntry('certificates[]', await MultipartFile.fromFile(p)));
    }
    if (profilePhotoPath != null && profilePhotoPath.isNotEmpty) {
      form.files.add(MapEntry('profile_photo', await MultipartFile.fromFile(profilePhotoPath)));
    }

    final res = await _dio.post('/api/specialist/register', data: form);
    final data = res.data;
    if (data is Map<String, dynamic>) return data;
    if (data is Map) return data.cast<String, dynamic>();
    return <String, dynamic>{};
  }

  Future<Map<String, dynamic>> fetchPaymentStatus(String statusUrl) async {
    final res = await _dio.getUri(Uri.parse(statusUrl));
    final data = res.data;
    if (data is Map<String, dynamic>) return data;
    if (data is Map) return data.cast<String, dynamic>();
    return <String, dynamic>{};
  }

  Future<void> setup(Map<String, dynamic> payload) async {
    await _dio.patch('/api/specialist/setup', data: payload);
  }

  Future<DiagnosticLocationsResponse> listDiagnosticLocations() async {
    final res = await _dio.get('/api/specialist/diagnostics/locations');
    final data = res.data;
    if (data is! Map<String, dynamic>) {
      return const DiagnosticLocationsResponse(locations: [], defaultLocationId: null);
    }
    final locations = (data['locations'] as List? ?? const []).cast<Map<String, dynamic>>();
    return DiagnosticLocationsResponse(locations: locations, defaultLocationId: null);
  }

  Future<List<Map<String, dynamic>>> listDiagnosticCentersFiltered({
    required String locationId,
    List<int> serviceTypeIds = const [],
    String? day,
    String? at,
  }) async {
    final res = await _dio.get('/api/specialist/diagnostics/centers', queryParameters: {
      'location_id': locationId,
      if (serviceTypeIds.isNotEmpty) 'service_type_ids[]': serviceTypeIds,
      if (day != null && day.isNotEmpty) 'day': day,
      if (at != null && at.isNotEmpty) 'at': at,
    });
    final data = res.data;
    if (data is! Map<String, dynamic>) return [];
    final centers = data['centers'];
    if (centers is! List) return [];
    return centers.cast<Map<String, dynamic>>();
  }

  Future<List<Map<String, dynamic>>> listDiagnosticServices(String centerId) async {
    final res = await _dio.get('/api/specialist/diagnostics/services', queryParameters: {'center_id': centerId});
    final data = res.data;
    if (data is! Map<String, dynamic>) return [];
    final services = data['services'];
    if (services is! List) return [];
    return services.cast<Map<String, dynamic>>();
  }

  Future<Map<String, dynamic>> createDiagnosticReferral({
    required Map<String, dynamic> fields,
    List<String> filePaths = const [],
  }) async {
    final form = FormData();
    fields.forEach((k, v) {
      if (v == null) return;
      if (v is List) {
        for (final item in v) {
          form.fields.add(MapEntry(k, item.toString()));
        }
        return;
      }
      form.fields.add(MapEntry(k, v.toString()));
    });
    for (final path in filePaths) {
      form.files.add(MapEntry('attachments[]', await MultipartFile.fromFile(path)));
    }
    final res = await _dio.post('/api/specialist/diagnostic-referrals', data: form);
    return (res.data as Map).cast<String, dynamic>();
  }
}
