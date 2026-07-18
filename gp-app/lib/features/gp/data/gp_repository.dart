import 'package:dio/dio.dart';
import 'package:gp_app/core/http/dio_client.dart';
import 'package:gp_app/features/auth/models/user.dart';
import 'package:gp_app/features/gp/models/gp_dashboard_response.dart';
import 'package:gp_app/features/gp/models/referral.dart';
import 'package:gp_app/features/gp/models/referral_list_response.dart';
import 'package:gp_app/features/gp/models/recommended_specialist.dart';

class DiagnosticLocationsResponse {
  final List<Map<String, dynamic>> locations;
  final int? defaultLocationId;

  const DiagnosticLocationsResponse({
    required this.locations,
    required this.defaultLocationId,
  });
}

class SpecialtyCategoriesResponse {
  final int? locationId;
  final List<Map<String, dynamic>> categories;

  const SpecialtyCategoriesResponse({
    required this.categories,
    required this.locationId,
  });
}

class GpRepository {
  final Dio _dio;
  GpRepository(DioClient client) : _dio = client.dio;

  Future<GpDashboardResponse> fetchDashboard() async {
    final res = await _dio.get('/api/gp/dashboard');
    
    // Safely unwrap data to prevent type errors from Laravel JSON Resources
    var responseData = res.data as Map<String, dynamic>;
    if (responseData.containsKey('data') && responseData['data'] is Map<String, dynamic>) {
      responseData = responseData['data'] as Map<String, dynamic>;
    }
    
    // Also unwrap 'recent_referrals' collection if it's wrapped in a Laravel Resource Collection
    if (responseData.containsKey('recent_referrals') && responseData['recent_referrals'] is Map<String, dynamic> && (responseData['recent_referrals'] as Map<String, dynamic>).containsKey('data')) {
      responseData['recent_referrals'] = (responseData['recent_referrals'] as Map<String, dynamic>)['data'];
    }

    return GpDashboardResponse.fromJson(responseData);
  }

  Future<List<RecommendedSpecialist>> fetchRecommendedSpecialists() async {
    final res = await _dio.get('/api/gp/dashboard/recommended-specialists');

    // Safely unwrap data from multiple possible Laravel Resource wrappers
    var data = res.data;
    if (data is Map<String, dynamic>) {
      if (data.containsKey('recommended_specialists')) {
        data = data['recommended_specialists'];
      } else if (data.containsKey('data')) {
        data = data['data'];
      }
    }

    if (data is! List) return [];

    return data.map((e) {
      final map = Map<String, dynamic>.from(e as Map<String, dynamic>);
      // Laravel/MySQL Bit field safety: convert 1/0 to bool
      if (map['is_premium'] == 1) map['is_premium'] = true;
      if (map['is_premium'] == 0) map['is_premium'] = false;
      if (map['is_super_specialist'] == 1) map['is_super_specialist'] = true;
      if (map['is_super_specialist'] == 0) map['is_super_specialist'] = false;
      return RecommendedSpecialist.fromJson(map);
    }).toList();
  }

  Future<SpecialtyCategoriesResponse> fetchSpecialtyCategories({int? locationId}) async {
    final res = await _dio.get('/api/gp/dashboard/specialty-categories', queryParameters: {
      if (locationId != null) 'location_id': locationId,
    });
    final data = res.data;
    if (data is Map<String, dynamic>) {
      final location = data['location_id'] is int ? data['location_id'] as int : int.tryParse('${data['location_id']}');
      final categories = (data['categories'] is List ? data['categories'] as List : const [])
          .cast<Map<String, dynamic>>();
      return SpecialtyCategoriesResponse(categories: categories, locationId: location);
    }
    return const SpecialtyCategoriesResponse(categories: [], locationId: null);
  }

  Future<ReferralListResponse> fetchReferrals({
    String? referralType,
    String? status,
    String? from,
    String? to,
    String? q,
    int page = 1,
    int perPage = 20,
  }) async {
    final res = await _dio.get('/api/gp/referrals', queryParameters: {
      if (referralType != null && referralType.isNotEmpty) 'referral_type': referralType,
      if (status != null && status.isNotEmpty) 'status': status,
      if (from != null && from.isNotEmpty) 'from': from,
      if (to != null && to.isNotEmpty) 'to': to,
      if (q != null && q.isNotEmpty) 'q': q,
      'page': page,
      'per_page': perPage,
    });
    final map = Map<String, dynamic>.from(res.data as Map<String, dynamic>);
    final meta = map['meta'];
    if (meta is Map<String, dynamic>) {
      map['current_page'] = meta['current_page'] ?? map['current_page'];
      map['last_page'] = meta['last_page'] ?? map['last_page'];
      map['total'] = meta['total'] ?? map['total'];
    }
    return ReferralListResponse.fromJson(map);
  }

  Future<Referral> fetchReferral(String id, {String? referralType}) async {
    final res = await _dio.get('/api/gp/referrals/$id', queryParameters: {
      if (referralType != null && referralType.isNotEmpty) 'referral_type': referralType,
    });
    
    // Safely unwrap data from Laravel Resource wrappers
    var data = res.data as Map<String, dynamic>;
    if (data.containsKey('data') && data['data'] is Map<String, dynamic>) {
        data = data['data'] as Map<String, dynamic>;
    }
    return Referral.fromJson(data);
  }

  Future<Referral> createReferral({
    required Map<String, dynamic> fields,
    List<String> filePaths = const [],
  }) async {
    final form = FormData();
    fields.forEach((k, v) {
      if (v == null) return;
      if (v is Iterable) {
        for (final item in v) {
          if (item == null) continue;
          form.fields.add(MapEntry(k, item.toString()));
        }
        return;
      }
      form.fields.add(MapEntry(k, v.toString()));
    });
    for (final path in filePaths) {
      form.files.add(MapEntry('attachments[]', await MultipartFile.fromFile(path)));
    }
    final res = await _dio.post('/api/gp/referrals', data: form);
    var data = Map<String, dynamic>.from(res.data as Map<String, dynamic>);
    if (data['data'] is Map<String, dynamic>) {
      data = Map<String, dynamic>.from(data['data'] as Map<String, dynamic>);
    }
    return Referral.fromJson(data);
  }

  Future<Referral> createDiagnosticReferral({
    required Map<String, dynamic> fields,
    List<String> filePaths = const [],
  }) async {
    return createReferral(fields: fields, filePaths: filePaths);
  }

  Future<User> fetchProfile() async {
    final res = await _dio.get('/api/gp/profile');
    final data = res.data is Map<String, dynamic> && (res.data as Map<String, dynamic>)['user'] != null
        ? (res.data as Map<String, dynamic>)['user'] as Map<String, dynamic>
        : res.data as Map<String, dynamic>;
    return User.fromJson(data);
  }

  Future<User> updateProfile(Map<String, dynamic> payload) async {
    final res = await _dio.put('/api/gp/profile', data: payload);
    final data = res.data is Map<String, dynamic> && (res.data as Map<String, dynamic>)['user'] != null
        ? (res.data as Map<String, dynamic>)['user'] as Map<String, dynamic>
        : res.data as Map<String, dynamic>;
    return User.fromJson(data);
  }

  Future<void> changePassword({
    required String currentPassword,
    required String newPassword,
    required String confirmPassword,
  }) async {
    await _dio.post('/api/gp/profile/change-password', data: {
      'current_password': currentPassword,
      'new_password': newPassword,
      'new_password_confirmation': confirmPassword,
    });
  }

  Future<List<Map<String, dynamic>>> listSpecialists({String? city}) async {
    final res = await _dio.get('/api/specialists', queryParameters: {
      if (city != null && city.isNotEmpty) 'city': city,
    });
    return (res.data as List).cast<Map<String, dynamic>>();
  }

  Future<List<Map<String, dynamic>>> listHospitals({String? city}) async {
    final res = await _dio.get('/api/hospitals', queryParameters: {
      if (city != null && city.isNotEmpty) 'city': city,
    });
    return (res.data as List).cast<Map<String, dynamic>>();
  }

  // --- 3-Step Specialist Selection APIs ---

  Future<List<Map<String, dynamic>>> listLocations() async {
    final res = await _dio.get('/api/gp/referrals/locations');
    final data = res.data;
    if (data is Map<String, dynamic> && data['locations'] is List) {
      return (data['locations'] as List).cast<Map<String, dynamic>>();
    }
    if (data is List) return data.cast<Map<String, dynamic>>();
    return [];
  }

  Future<List<Map<String, dynamic>>> listPublicLocations() async {
    final res = await _dio.get('/api/public/locations');
    final data = res.data;
    if (data is List) return data.cast<Map<String, dynamic>>();
    if (data is Map<String, dynamic> && data['data'] is List) {
      return (data['data'] as List).cast<Map<String, dynamic>>();
    }
    return [];
  }

  Future<List<Map<String, dynamic>>> listHospitalLocations() async {
    return listHospitalLocationsForSender();
  }

  Future<List<Map<String, dynamic>>> listHospitalLocationsForSender({bool asSpecialist = false}) async {
    final res = await _dio.get(
      asSpecialist ? '/api/specialist/hospital-referrals/locations' : '/api/gp/referrals/hospital-locations',
    );
    final data = res.data;
    if (data is Map<String, dynamic> && data['locations'] is List) {
      return (data['locations'] as List).cast<Map<String, dynamic>>();
    }
    if (data is List) return data.cast<Map<String, dynamic>>();
    return [];
  }

  Future<List<Map<String, dynamic>>> listCategories(String locationId) async {
    final res = await _dio.get('/api/gp/referrals/categories', queryParameters: {'location_id': locationId});
    final data = res.data;
    if (data is Map<String, dynamic> && data['categories'] is List) {
      return (data['categories'] as List).cast<Map<String, dynamic>>();
    }
    if (data is List) return data.cast<Map<String, dynamic>>();
    return [];
  }

  Future<List<Map<String, dynamic>>> listSpecialistsByFilter({
    String? locationId,
    required String specialtyId,
  }) async {
    final queryParams = <String, dynamic>{'specialty_id': specialtyId};
    if (locationId != null && locationId.isNotEmpty && locationId != '0') {
      queryParams['location_id'] = locationId;
    }
    final res = await _dio.get('/api/gp/referrals/specialists', queryParameters: queryParams);
    final data = res.data;
    if (data is Map<String, dynamic> && data['specialists'] is List) {
      return (data['specialists'] as List).cast<Map<String, dynamic>>();
    }
    if (data is List) return data.cast<Map<String, dynamic>>();
    return [];
  }

  Future<List<RecommendedSpecialist>> fetchSpecialistsForSpecialty({
    required int locationId,
    required int specialtyId,
  }) async {
    final locId = locationId > 0 ? locationId.toString() : null;
    final items = await listSpecialistsByFilter(
      locationId: locId,
      specialtyId: specialtyId.toString(),
    );

    return items.map((e) {
      final map = Map<String, dynamic>.from(e);
      if (map['is_premium'] == 1) map['is_premium'] = true;
      if (map['is_premium'] == 0) map['is_premium'] = false;
      if (map['is_super_specialist'] == 1) map['is_super_specialist'] = true;
      if (map['is_super_specialist'] == 0) map['is_super_specialist'] = false;
      map['location_id'] = map['location_id'] ?? locationId;
      map['category_code'] = map['category_code'] ?? specialtyId.toString();
      map['match_type'] = map['match_type'] ?? 'category';
      return RecommendedSpecialist.fromJson(map);
    }).toList();
  }

  Future<List<Map<String, dynamic>>> listHospitalsByLocation({required String locationId}) async {
    return listHospitalsByLocationForSender(locationId: locationId);
  }

  Future<List<Map<String, dynamic>>> listHospitalsByLocationForSender({
    required String locationId,
    bool asSpecialist = false,
  }) async {
    final res = await _dio.get(asSpecialist ? '/api/specialist/hospital-referrals/hospitals' : '/api/gp/referrals/hospitals', queryParameters: {
      'location_id': locationId,
    });
    final data = res.data;
    if (data is Map<String, dynamic> && data['hospitals'] is List) {
      return (data['hospitals'] as List).cast<Map<String, dynamic>>();
    }
    if (data is List) return data.cast<Map<String, dynamic>>();
    return [];
  }

  Future<List<String>> listHospitalDepartments({required String hospitalId}) async {
    return listHospitalDepartmentsForSender(hospitalId: hospitalId);
  }

  Future<List<String>> listHospitalDepartmentsForSender({
    required String hospitalId,
    bool asSpecialist = false,
  }) async {
    final res = await _dio.get(
      asSpecialist
          ? '/api/specialist/hospital-referrals/hospitals/$hospitalId/departments'
          : '/api/gp/referrals/hospitals/$hospitalId/departments',
    );
    final data = res.data;
    if (data is Map<String, dynamic> && data['departments'] is List) {
      return (data['departments'] as List).map((e) => e.toString()).toList();
    }
    if (data is List) return data.map((e) => e.toString()).toList();
    return [];
  }

  Future<Referral> createHospitalReferral({
    required Map<String, dynamic> fields,
    List<String> filePaths = const [],
    bool asSpecialist = false,
  }) async {
    final form = FormData();
    fields.forEach((k, v) {
      if (v == null) return;
      if (v is Iterable) {
        for (final item in v) {
          if (item == null) continue;
          form.fields.add(MapEntry(k, item.toString()));
        }
        return;
      }
      form.fields.add(MapEntry(k, v.toString()));
    });
    for (final path in filePaths) {
      form.files.add(MapEntry('attachments[]', await MultipartFile.fromFile(path)));
    }
    final res = await _dio.post(
      asSpecialist ? '/api/specialist/hospital-referrals' : '/api/gp/referrals',
      data: form,
    );
    var data = Map<String, dynamic>.from(res.data as Map<String, dynamic>);
    if (data['data'] is Map<String, dynamic>) {
      data = Map<String, dynamic>.from(data['data'] as Map<String, dynamic>);
    }
    return Referral.fromJson(data);
  }

  Future<DiagnosticLocationsResponse> listDiagnosticLocations() async {
    final res = await _dio.get('/api/gp/diagnostics/locations');
    final data = res.data;
    if (data is! Map<String, dynamic>) {
      return const DiagnosticLocationsResponse(locations: [], defaultLocationId: null);
    }
    final locations = (data['locations'] as List? ?? const []).cast<Map<String, dynamic>>();
    final defaultLocationId = data['default_location_id'] is int ? data['default_location_id'] as int : int.tryParse('${data['default_location_id']}');
    return DiagnosticLocationsResponse(locations: locations, defaultLocationId: defaultLocationId);
  }

  Future<List<Map<String, dynamic>>> listDiagnosticCenters(String locationId) async {
    final res = await _dio.get('/api/gp/diagnostics/centers', queryParameters: {'location_id': locationId});
    final data = res.data;
    if (data is! Map<String, dynamic>) return [];
    final centers = data['centers'];
    if (centers is! List) return [];
    return centers.cast<Map<String, dynamic>>();
  }

  Future<List<Map<String, dynamic>>> listDiagnosticCentersFiltered({
    required String locationId,
    List<int> serviceTypeIds = const [],
    String? day,
    String? at,
  }) async {
    final res = await _dio.get('/api/gp/diagnostics/centers', queryParameters: {
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
    final res = await _dio.get('/api/gp/diagnostics/services', queryParameters: {'center_id': centerId});
    final data = res.data;
    if (data is! Map<String, dynamic>) return [];
    final services = data['services'];
    if (services is! List) return [];
    return services.cast<Map<String, dynamic>>();
  }

  Future<List<Map<String, dynamic>>> listDiagnosticServiceTypes() async {
    final res = await _dio.get('/api/public/diagnostic-service-types');
    final data = res.data;
    if (data is! List) return [];
    return data.cast<Map<String, dynamic>>();
  }
}
