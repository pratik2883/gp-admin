import 'package:dio/dio.dart';
import 'package:gp_app/core/http/dio_client.dart';
import 'package:gp_app/features/specialist/models/lead_list_response.dart';

class DiagnosticCenterRepository {
  final Dio _dio;
  DiagnosticCenterRepository(DioClient client) : _dio = client.dio;

  Future<List<Map<String, dynamic>>> listLocations() async {
    final res = await _dio.get('/api/public/locations');
    final data = res.data;
    if (data is! List) return [];
    return data.cast<Map<String, dynamic>>();
  }

  Future<List<Map<String, dynamic>>> listServiceTypes() async {
    final res = await _dio.get('/api/public/diagnostic-service-types');
    final data = res.data;
    if (data is! List) return [];
    return data.cast<Map<String, dynamic>>();
  }

  Future<Map<String, dynamic>> fetchSubscriptionPlans(String category) async {
    final res = await _dio.get('/api/public/subscription-plans', data: null, queryParameters: {
      'category': category,
    });
    final data = res.data;
    if (data is Map<String, dynamic>) return data;
    if (data is Map) return data.cast<String, dynamic>();
    return <String, dynamic>{};
  }

  Future<Map<String, dynamic>> register(Map<String, dynamic> payload) async {
    final res = await _dio.post('/api/diagnostic/register', data: payload);
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

  Future<Map<String, dynamic>> fetchProfile() async {
    final res = await _dio.get('/api/diagnostic/profile');
    final data = res.data;
    if (data is Map<String, dynamic> && data['profile'] is Map<String, dynamic>) {
      return (data['profile'] as Map<String, dynamic>);
    }
    return data is Map<String, dynamic> ? data : <String, dynamic>{};
  }

  Future<void> updateProfile(Map<String, dynamic> payload) async {
    await _dio.patch('/api/diagnostic/profile', data: payload);
  }

  Future<LeadListResponse> fetchReferrals({String? status, String? q, int page = 1, int perPage = 20}) async {
    final res = await _dio.get('/api/diagnostic/referrals', queryParameters: {
      if (status != null && status.isNotEmpty) 'status': status,
      if (q != null && q.isNotEmpty) 'q': q,
      'page': page,
      'per_page': perPage,
    });
    return LeadListResponse.fromJson(res.data as Map<String, dynamic>);
  }

  Future<Map<String, dynamic>> fetchReferral(String id) async {
    final res = await _dio.get('/api/diagnostic/referrals/$id');
    var data = res.data as Map<String, dynamic>;
    if (data.containsKey('data') && data['data'] is Map<String, dynamic>) {
      data = data['data'] as Map<String, dynamic>;
    }
    return data;
  }

  Future<Map<String, dynamic>> updateReferralStatus(String id, String status) async {
    final res = await _dio.patch('/api/diagnostic/referrals/$id/status', data: {'status': status});
    var data = res.data as Map<String, dynamic>;
    if (data.containsKey('data') && data['data'] is Map<String, dynamic>) {
      data = data['data'] as Map<String, dynamic>;
    }
    return data;
  }
}
