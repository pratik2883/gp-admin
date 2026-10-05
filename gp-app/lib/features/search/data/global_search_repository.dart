import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:gp_app/core/http/dio_client.dart';
import 'package:gp_app/app/providers.dart';

class GlobalSearchResult {
  final String query;
  final List<String> allowedTypes;
  final Map<String, int> counts;
  final List<Map<String, dynamic>> specialists;
  final List<Map<String, dynamic>> hospitals;
  final List<Map<String, dynamic>> diagnosticCenters;

  GlobalSearchResult({
    required this.query,
    required this.allowedTypes,
    required this.counts,
    required this.specialists,
    required this.hospitals,
    required this.diagnosticCenters,
  });

  factory GlobalSearchResult.fromJson(Map<String, dynamic> json) {
    final allowed = (json['allowed_types'] as List<dynamic>?)
            ?.map((e) => e.toString())
            .toList() ??
        [];
    final countsMap = (json['counts'] as Map<String, dynamic>?)?.map(
          (k, v) => MapEntry(k, (v as num).toInt()),
        ) ??
        {};

    final resObj = json['results'] as Map<String, dynamic>? ?? {};

    final spList = (resObj['specialists'] as List<dynamic>?)
            ?.map((e) => Map<String, dynamic>.from(e as Map))
            .toList() ??
        [];
    final hospList = (resObj['hospitals'] as List<dynamic>?)
            ?.map((e) => Map<String, dynamic>.from(e as Map))
            .toList() ??
        [];
    final dxList = (resObj['diagnostic_centers'] as List<dynamic>?)
            ?.map((e) => Map<String, dynamic>.from(e as Map))
            .toList() ??
        [];

    return GlobalSearchResult(
      query: json['query'] as String? ?? '',
      allowedTypes: allowed,
      counts: countsMap,
      specialists: spList,
      hospitals: hospList,
      diagnosticCenters: dxList,
    );
  }
}

final globalSearchRepositoryProvider = Provider<GlobalSearchRepository>((ref) {
  return GlobalSearchRepository(ref.read(dioClientProvider));
});

class GlobalSearchRepository {
  final Dio _dio;
  GlobalSearchRepository(DioClient client) : _dio = client.dio;

  Future<GlobalSearchResult> search(String query, {String type = 'all'}) async {
    final res = await _dio.get('/api/search', queryParameters: {
      'q': query,
      'type': type,
    });
    return GlobalSearchResult.fromJson(res.data as Map<String, dynamic>);
  }
}
