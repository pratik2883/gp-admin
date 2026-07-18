import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:gp_app/features/gp/data/gp_repository.dart';
import 'package:gp_app/features/gp/state/new_referral_state.dart';

class NewReferralController extends StateNotifier<NewReferralState> {
  final GpRepository _repo;
  NewReferralController(this._repo) : super(const NewReferralState.idle());

  String _extractErrorMessage(Object error) {
    if (error is DioException) {
      final data = error.response?.data;
      if (data is Map) {
        final errors = data['errors'];
        if (errors is Map) {
          for (final v in errors.values) {
            if (v is List && v.isNotEmpty) return v.first.toString();
            if (v is String && v.isNotEmpty) return v;
          }
        }
        final message = data['message'];
        if (message != null && message.toString().trim().isNotEmpty) return message.toString();
      }
      if (data is String && data.trim().isNotEmpty) return data;
    }
    return 'Failed to create referral';
  }

  Future<void> submit({
    required Map<String, dynamic> fields,
    List<String> filePaths = const [],
  }) async {
    state = const NewReferralState.submitting();
    try {
      await _repo.createReferral(fields: fields, filePaths: filePaths);
      state = const NewReferralState.success();
    } catch (e) {
      state = NewReferralState.error(_extractErrorMessage(e));
    }
  }

  Future<void> submitDiagnostic({
    required Map<String, dynamic> fields,
    List<String> filePaths = const [],
  }) async {
    state = const NewReferralState.submitting();
    try {
      await _repo.createDiagnosticReferral(fields: fields, filePaths: filePaths);
      state = const NewReferralState.success();
    } catch (e) {
      state = NewReferralState.error(_extractErrorMessage(e));
    }
  }

  Future<void> submitHospital({
    required Map<String, dynamic> fields,
    List<String> filePaths = const [],
    bool asSpecialist = false,
  }) async {
    state = const NewReferralState.submitting();
    try {
      await _repo.createHospitalReferral(
        fields: fields,
        filePaths: filePaths,
        asSpecialist: asSpecialist,
      );
      state = const NewReferralState.success();
    } catch (e) {
      state = NewReferralState.error(_extractErrorMessage(e));
    }
  }
}
