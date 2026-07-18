import 'package:dio/dio.dart';
import 'package:gp_app/core/config.dart';
import 'package:gp_app/core/storage/secure_storage.dart';

String extractApiErrorMessage(Object error, {String fallback = 'Request failed'}) {
  if (error is DioException) {
    final data = error.response?.data;
    if (data is Map) {
      final errors = data['errors'];
      if (errors is Map) {
        for (final v in errors.values) {
          if (v is List && v.isNotEmpty) return v.first.toString();
          if (v is String && v.trim().isNotEmpty) return v;
        }
      }
      final message = data['message'];
      if (message != null && message.toString().trim().isNotEmpty) return message.toString();
    }
    if (data is String && data.trim().isNotEmpty) return data;
    final msg = error.message;
    if (msg != null && msg.trim().isNotEmpty) return msg;
  }
  return fallback;
}

class DioClient {
  final Dio _dio;
  final AppSecureStorage _storage;

  DioClient(this._storage)
      : _dio = Dio(
          BaseOptions(
            baseUrl: baseUrl,
            connectTimeout: const Duration(seconds: 15),
            receiveTimeout: const Duration(seconds: 20),
            headers: {'Accept': 'application/json'},
          ),
        ) {
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await _storage.readToken();
        if (token != null && token.isNotEmpty) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        return handler.next(options);
      },
    ));
    // ✅ Add terminal logging for development
    _dio.interceptors.add(LogInterceptor(
      requestHeader: true,
      requestBody: true,
      responseHeader: true,
      responseBody: true,
      error: true,
    ));
  }

  Dio get dio => _dio;
}
