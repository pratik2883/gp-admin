import 'package:dio/dio.dart';
import 'package:gp_app/core/http/dio_client.dart';
import 'package:gp_app/core/storage/secure_storage.dart';
import 'package:gp_app/features/auth/models/login_response.dart';
import 'package:gp_app/features/auth/models/user.dart';

class AuthRepository {
  final DioClient _client;
  final AppSecureStorage _storage;
  AuthRepository(this._client, this._storage);

  Future<LoginResponse> login({required String mobile, required String password}) async {
    final res = await _client.dio.post('/api/login', data: {
      'mobile': mobile,
      'password': password,
    });
    final data = LoginResponse.fromJson(res.data as Map<String, dynamic>);
    await _storage.saveToken(data.token);
    return data;
  }

  Future<LoginResponse> loginWithOtp({required String firebaseIdToken, required String roleHint, bool termsAccepted = false}) async {
    final res = await _client.dio.post('/api/auth/login-with-otp', data: {
      'firebase_id_token': firebaseIdToken,
      'role_hint': roleHint,
      'terms_accepted': termsAccepted,
    });
    final data = LoginResponse.fromJson(res.data as Map<String, dynamic>);
    await _storage.saveToken(data.token);
    return data;
  }

  Future<User> register({
    required String name,
    String? email,
    required String mobile,
    required String password,
    String role = 'gp',
    String? clinicName,
    String? addressLine,
    bool termsAccepted = false,
  }) async {
    final res = await _client.dio.post('/api/auth/register', data: {
      'name': name,
      'email': (email == null || email.trim().isEmpty) ? null : email.trim(),
      'mobile': mobile,
      'password': password,
      'role': role,
      if (clinicName != null && clinicName.trim().isNotEmpty) 'clinic_name': clinicName.trim(),
      if (addressLine != null && addressLine.trim().isNotEmpty) 'address_line': addressLine.trim(),
      'terms_accepted': termsAccepted,
    });

    final data = res.data;
    if (data is Map<String, dynamic>) {
      if (data['user'] is Map<String, dynamic>) {
        return User.fromJson(data['user'] as Map<String, dynamic>);
      }
      return User.fromJson(data);
    }

    throw DioException(
      requestOptions: res.requestOptions,
      response: res,
      error: 'Unexpected register response',
      type: DioExceptionType.unknown,
    );
  }

  Future<User> fetchMe() async {
    final res = await _client.dio.get('/api/me');
    final user = User.fromJson((res.data as Map<String, dynamic>)['user'] as Map<String, dynamic>);
    return user;
  }

  Future<void> logout() async {
    try {
      await _client.dio.post('/api/logout');
    } catch (_) {}
    await _storage.clearToken();
  }

  Future<String?> readToken() => _storage.readToken();
}
