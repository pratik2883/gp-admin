import 'package:dio/dio.dart';
import 'package:geolocator/geolocator.dart';

class LocationResult {
  final double latitude;
  final double longitude;
  final String? city;
  final String? error;

  const LocationResult({required this.latitude, required this.longitude, this.city, this.error});
}

class LocationService {
  final Dio _http = Dio();

  Future<LocationResult> getCurrentLocation() async {
    try {
      bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        return const LocationResult(
          latitude: 0,
          longitude: 0,
          error: 'Location services are disabled.',
        );
      }

      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
        if (permission == LocationPermission.denied) {
          return const LocationResult(
            latitude: 0,
            longitude: 0,
            error: 'Location permission denied.',
          );
        }
      }

      if (permission == LocationPermission.deniedForever) {
        return const LocationResult(
          latitude: 0,
          longitude: 0,
          error: 'Location permission permanently denied.',
        );
      }

      final position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.low,
      );

      final city = await _reverseGeocode(position.latitude, position.longitude);

      return LocationResult(
        latitude: position.latitude,
        longitude: position.longitude,
        city: city,
      );
    } catch (e) {
      return LocationResult(
        latitude: 0,
        longitude: 0,
        error: 'Failed to get location: $e',
      );
    }
  }

  Future<String?> _reverseGeocode(double lat, double lng) async {
    try {
      final response = await _http.get(
        'https://maps.googleapis.com/maps/api/geocode/json',
        queryParameters: {
          'latlng': '$lat,$lng',
          'result_type': 'locality|administrative_area_level_2|administrative_area_level_1',
          'key': '', // Will be overridden below – we inject at call site
        },
      );

      if (response.statusCode != 200) return null;

      final data = response.data as Map<String, dynamic>;
      if (data['status'] != 'OK') return null;

      final results = data['results'] as List<dynamic>?;
      if (results == null || results.isEmpty) return null;

      for (final result in results) {
        final addressComponents = result['address_components'] as List<dynamic>?;
        if (addressComponents == null) continue;

        for (final component in addressComponents) {
          final types = component['types'] as List<dynamic>? ?? [];
          final longName = component['long_name']?.toString() ?? '';

          if (types.contains('locality')) return longName;
          if (types.contains('administrative_area_level_2')) return longName;
          if (types.contains('administrative_area_level_1')) return longName;
        }
      }

      return null;
    } catch (_) {
      return null;
    }
  }

  Future<String?> reverseGeocodeWithKey(double lat, double lng, String apiKey) async {
    try {
      final response = await _http.get(
        'https://maps.googleapis.com/maps/api/geocode/json',
        queryParameters: {
          'latlng': '$lat,$lng',
          'result_type': 'locality|administrative_area_level_2|administrative_area_level_1',
          'key': apiKey,
        },
      );

      if (response.statusCode != 200) return null;

      final data = response.data as Map<String, dynamic>;
      if (data['status'] != 'OK') return null;

      final results = data['results'] as List<dynamic>?;
      if (results == null || results.isEmpty) return null;

      for (final result in results) {
        final addressComponents = result['address_components'] as List<dynamic>?;
        if (addressComponents == null) continue;

        for (final component in addressComponents) {
          final types = component['types'] as List<dynamic>? ?? [];
          final longName = component['long_name']?.toString() ?? '';

          if (types.contains('locality')) return longName;
          if (types.contains('administrative_area_level_2')) return longName;
          if (types.contains('administrative_area_level_1')) return longName;
        }
      }

      return null;
    } catch (_) {
      return null;
    }
  }
}
