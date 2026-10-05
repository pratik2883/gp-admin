import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:gp_app/core/http/dio_client.dart';
import 'package:gp_app/core/location/location_service.dart';
import 'package:gp_app/core/storage/secure_storage.dart';
import 'package:gp_app/features/auth/data/auth_repository.dart';
import 'package:gp_app/features/auth/state/auth_controller.dart';
import 'package:gp_app/features/auth/state/auth_state.dart';
import 'package:gp_app/features/gp/data/gp_repository.dart';
import 'package:gp_app/features/gp/state/gp_home_controller.dart';
import 'package:gp_app/features/gp/state/gp_home_state.dart';
import 'package:gp_app/features/gp/state/referral_list_controller.dart';
import 'package:gp_app/features/gp/state/referral_list_state.dart';
import 'package:gp_app/features/gp/state/new_referral_controller.dart';
import 'package:gp_app/features/gp/state/new_referral_state.dart';
import 'package:gp_app/features/gp/models/recommended_specialist.dart';
import 'package:gp_app/features/specialist/data/specialist_repository.dart';
import 'package:gp_app/features/diagnostic_center/data/diagnostic_center_repository.dart';
import 'package:gp_app/features/diagnostic_center/state/dx_referral_list_controller.dart';
import 'package:gp_app/features/diagnostic_center/state/dx_referral_detail_controller.dart';
import 'package:gp_app/features/diagnostic_center/state/dx_referral_detail_state.dart';
import 'package:gp_app/features/support/data/support_repository.dart';
import 'package:gp_app/features/support/state/support_ticket_detail_controller.dart';
import 'package:gp_app/features/support/state/support_ticket_list_controller.dart';
import 'package:gp_app/features/support/state/support_ticket_list_state.dart';
import 'package:gp_app/features/support/models/support_ticket.dart';
import 'package:gp_app/features/specialist/state/lead_list_controller.dart' as sp;
import 'package:gp_app/features/specialist/state/lead_list_state.dart' as sp;
import 'package:gp_app/features/specialist/state/lead_detail_controller.dart' as sp;
import 'package:gp_app/features/specialist/state/lead_detail_state.dart' as sp;

class DiagnosticCenterQuery {
  final String locationId;
  final List<int> serviceTypeIds;
  final String? day;
  final String? at;

  const DiagnosticCenterQuery({
    required this.locationId,
    this.serviceTypeIds = const [],
    this.day,
    this.at,
  });

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is DiagnosticCenterQuery &&
        other.locationId == locationId &&
        _listEq(other.serviceTypeIds, serviceTypeIds) &&
        other.day == day &&
        other.at == at;
  }

  @override
  int get hashCode => Object.hash(locationId, Object.hashAll(serviceTypeIds), day, at);
}

class SpecialtySpecialistsQuery {
  final int locationId;
  final int specialtyId;

  const SpecialtySpecialistsQuery({
    required this.locationId,
    required this.specialtyId,
  });

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is SpecialtySpecialistsQuery && other.locationId == locationId && other.specialtyId == specialtyId;
  }

  @override
  int get hashCode => Object.hash(locationId, specialtyId);
}

bool _listEq(List<int> a, List<int> b) {
  if (a.length != b.length) return false;
  for (var i = 0; i < a.length; i++) {
    if (a[i] != b[i]) return false;
  }
  return true;
}

final secureStorageProvider = Provider<AppSecureStorage>((ref) => AppSecureStorage());
final dioClientProvider = Provider<DioClient>((ref) => DioClient(ref.read(secureStorageProvider)));
final authRepositoryProvider = Provider<AuthRepository>(
  (ref) => AuthRepository(ref.read(dioClientProvider), ref.read(secureStorageProvider)),
);
final authStateProvider = StateNotifierProvider<AuthController, AuthState>(
  (ref) => AuthController(
    ref.read(authRepositoryProvider),
    ref,
    ref.read(secureStorageProvider),
  ),
);

final publicFeatureFlagsProvider = FutureProvider<Map<String, dynamic>>((ref) async {
  final platform = switch (defaultTargetPlatform) {
    TargetPlatform.iOS => 'ios',
    _ => 'android',
  };
  final res = await ref.read(dioClientProvider).dio.get(
    '/api/public/feature-flags',
    queryParameters: {'platform': platform},
  );
  final data = res.data;
  if (data is Map<String, dynamic>) return data;
  if (data is Map) return data.cast<String, dynamic>();
  return <String, dynamic>{};
});

final gpRepositoryProvider = Provider<GpRepository>((ref) => GpRepository(ref.read(dioClientProvider)));
final gpHomeControllerProvider = StateNotifierProvider<GpHomeController, GpHomeState>(
  (ref) => GpHomeController(ref.read(gpRepositoryProvider), ref),
);
final referralListControllerProvider = StateNotifierProvider<ReferralListController, ReferralListState>(
  (ref) => ReferralListController(ref.read(gpRepositoryProvider)),
);
final newReferralControllerProvider = StateNotifierProvider<NewReferralController, NewReferralState>(
  (ref) => NewReferralController(ref.read(gpRepositoryProvider)),
);

final recommendedSpecialistsProvider = FutureProvider<List<RecommendedSpecialist>>((ref) async {
  return ref.read(gpRepositoryProvider).fetchRecommendedSpecialists();
});

final gpSpecialtyCategoriesProvider = FutureProvider<SpecialtyCategoriesResponse>((ref) async {
  return ref.read(gpRepositoryProvider).fetchSpecialtyCategories();
});

final nearbyReferralLocationIdProvider = StateProvider<int?>((ref) => null);

final nearbyReferralLocationsProvider = FutureProvider<List<Map<String, dynamic>>>((ref) async {
  return ref.read(gpRepositoryProvider).listLocations();
});

final nearbyReferralCategoriesByLocationProvider = FutureProvider.family<List<Map<String, dynamic>>, int>((ref, locationId) async {
  return ref.read(gpRepositoryProvider).listCategories(locationId.toString());
});

final specialistsBySpecialtyProvider = FutureProvider.family<List<RecommendedSpecialist>, SpecialtySpecialistsQuery>((ref, q) async {
  return ref.read(gpRepositoryProvider).fetchSpecialistsForSpecialty(
        locationId: q.locationId,
        specialtyId: q.specialtyId,
      );
});

final diagnosticLocationsProvider = FutureProvider<DiagnosticLocationsResponse>((ref) async {
  return ref.read(gpRepositoryProvider).listDiagnosticLocations();
});

final diagnosticCentersProvider = FutureProvider.family<List<Map<String, dynamic>>, String>((ref, locationId) async {
  return ref.read(gpRepositoryProvider).listDiagnosticCenters(locationId);
});

final diagnosticServiceTypesProvider = FutureProvider<List<Map<String, dynamic>>>((ref) async {
  return ref.read(gpRepositoryProvider).listDiagnosticServiceTypes();
});

final diagnosticCentersFilteredProvider = FutureProvider.family<List<Map<String, dynamic>>, DiagnosticCenterQuery>((ref, q) async {
  return ref.read(gpRepositoryProvider).listDiagnosticCentersFiltered(
        locationId: q.locationId,
        serviceTypeIds: q.serviceTypeIds,
        day: q.day,
        at: q.at,
      );
});

final diagnosticServicesProvider = FutureProvider.family<List<Map<String, dynamic>>, String>((ref, centerId) async {
  return ref.read(gpRepositoryProvider).listDiagnosticServices(centerId);
});

final hospitalLocationsProvider = FutureProvider<List<Map<String, dynamic>>>((ref) async {
  return ref.read(gpRepositoryProvider).listHospitalLocations();
});

final hospitalsByLocationProvider = FutureProvider.family<List<Map<String, dynamic>>, String>((ref, locationId) async {
  return ref.read(gpRepositoryProvider).listHospitalsByLocation(locationId: locationId);
});

final allHospitalsProvider = FutureProvider<List<Map<String, dynamic>>>((ref) async {
  final hospitals = await ref.read(gpRepositoryProvider).listHospitals();
  hospitals.sort((a, b) {
    final nameA = (a['name'] ?? a['hospital_name'] ?? '').toString().toLowerCase();
    final nameB = (b['name'] ?? b['hospital_name'] ?? '').toString().toLowerCase();
    return nameA.compareTo(nameB);
  });
  return hospitals;
});

final allDiagnosticCentersProvider = FutureProvider<List<Map<String, dynamic>>>((ref) async {
  final locsResp = await ref.read(gpRepositoryProvider).listDiagnosticLocations();
  final allCenters = <Map<String, dynamic>>[];
  for (final loc in locsResp.locations) {
    final locId = loc['id']?.toString();
    if (locId == null) continue;
    try {
      final centers = await ref.read(gpRepositoryProvider).listDiagnosticCenters(locId);
      allCenters.addAll(centers);
    } catch (_) {
      // Skip location if fails
    }
  }
  allCenters.sort((a, b) {
    final nameA = (a['name'] ?? a['center_name'] ?? '').toString().toLowerCase();
    final nameB = (b['name'] ?? b['center_name'] ?? '').toString().toLowerCase();
    return nameA.compareTo(nameB);
  });
  return allCenters;
});

final specialistRepositoryProvider = Provider<SpecialistRepository>((ref) => SpecialistRepository(ref.read(dioClientProvider)));
final diagnosticCenterRepositoryProvider = Provider<DiagnosticCenterRepository>(
  (ref) => DiagnosticCenterRepository(ref.read(dioClientProvider)),
);
final supportRepositoryProvider = Provider<SupportRepository>(
  (ref) => SupportRepository(ref.read(dioClientProvider)),
);
final supportTicketListControllerProvider = StateNotifierProvider<SupportTicketListController, SupportTicketListState>(
  (ref) => SupportTicketListController(ref.read(supportRepositoryProvider)),
);
final supportTicketDetailControllerProvider =
    StateNotifierProvider.family<SupportTicketDetailController, AsyncValue<SupportTicket?>, int>(
  (ref, ticketId) => SupportTicketDetailController(ref.read(supportRepositoryProvider)),
);
final spLeadListControllerProvider = StateNotifierProvider<sp.LeadListController, sp.LeadListState>(
  (ref) => sp.LeadListController(ref.read(specialistRepositoryProvider)),
);
final spLeadDetailControllerProvider = StateNotifierProvider<sp.LeadDetailController, sp.LeadDetailState>(
  (ref) => sp.LeadDetailController(ref.read(specialistRepositoryProvider)),
);
final dxReferralListControllerProvider = StateNotifierProvider<DxReferralListController, sp.LeadListState>(
  (ref) => DxReferralListController(ref.read(diagnosticCenterRepositoryProvider)),
);
final dxReferralDetailControllerProvider =
    StateNotifierProvider<DxReferralDetailController, DxReferralDetailState>(
  (ref) => DxReferralDetailController(ref.read(diagnosticCenterRepositoryProvider)),
);

final locationServiceProvider = Provider<LocationService>((ref) => LocationService());

final currentLocationProvider = FutureProvider<LocationResult>((ref) async {
  return ref.read(locationServiceProvider).getCurrentLocation();
});
