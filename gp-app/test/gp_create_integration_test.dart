import 'dart:io';
import 'package:flutter_test/flutter_test.dart';
import 'package:gp_app/core/http/dio_client.dart';
import 'package:gp_app/core/storage/secure_storage.dart';
import 'package:gp_app/features/gp/data/gp_repository.dart';

class _TokenStorage extends AppSecureStorage {
  @override
  Future<String?> readToken() async => '12|fgbnZWMOVtrsIdsmKlBvKe1wIO4u6RYwVR5WEcNLc07703f2';
  @override
  Future<String?> readRole() async => 'gp';
}

void main() {
  setUp(() {
    HttpOverrides.global = null;
  });

  test('GP create specialist referral via app repository stack', () async {
    final repo = GpRepository(DioClient(_TokenStorage()));

    final referral = await repo.createReferral(fields: {
      'specialist_id': '61',
      'patient_name': 'App Stack Repro',
      'patient_mobile': '9876501234',
      'patient_age': '42',
      'patient_gender': 'male',
      'case_summary': 'Created through the app HTTP stack',
      'appointment_type': 'opd',
      'priority': 'routine',
    });

    expect(referral.id, isPositive);
    expect(referral.lead_code, isNotNull);
    expect(referral.patient_name, 'App Stack Repro');
    expect(referral.patient_age, 42);
    // ignore: avoid_print
    print('FLUTTER_APP_CREATE_OK id=${referral.id} lead=${referral.lead_code} age=${referral.patient_age}');
  });

  test('GP create diagnostic referral via app repository stack', () async {
    final repo = GpRepository(DioClient(_TokenStorage()));

    final referral = await repo.createReferral(fields: {
      'referral_type': 'diagnostic',
      'diagnostic_center_id': '1',
      'diagnostic_service_ids[]': ['1', '2'],
      'patient_name': 'Dx App Stack Repro',
      'patient_mobile': '9876501234',
      'patient_age': '33',
      'patient_gender': 'female',
      'case_summary': 'Diagnostic through the app HTTP stack',
      'appointment_type': 'opd',
    });

    expect(referral.id, isPositive);
    expect(referral.lead_code, isNotNull);
    expect(referral.patient_name, 'Dx App Stack Repro');
    expect(referral.patient_age, 33);
    // ignore: avoid_print
    print('FLUTTER_APP_DX_CREATE_OK id=${referral.id} lead=${referral.lead_code} age=${referral.patient_age}');
  });

  test('GP create hospital referral via app repository stack', () async {
    final repo = GpRepository(DioClient(_TokenStorage()));

    final referral = await repo.createReferral(fields: {
      'referral_type': 'hospital',
      'hospital_id': '1',
      'department': 'Cardiology',
      'priority': 'routine',
      'patient_name': 'Hosp App Stack Repro',
      'patient_mobile': '9876501234',
      'patient_age': '50',
      'patient_gender': 'male',
      'case_summary': 'Hospital through the app HTTP stack',
      'appointment_type': 'opd',
    });

    expect(referral.id, isPositive);
    expect(referral.lead_code, isNotNull);
    expect(referral.patient_name, 'Hosp App Stack Repro');
    expect(referral.patient_age, 50);
    // ignore: avoid_print
    print('FLUTTER_APP_HOSP_CREATE_OK id=${referral.id} lead=${referral.lead_code} age=${referral.patient_age}');
  });
}