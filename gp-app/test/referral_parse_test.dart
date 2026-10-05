import 'package:flutter_test/flutter_test.dart';
import 'package:gp_app/features/gp/models/referral.dart';

void main() {
  final base = <String, dynamic>{
    'id': 4,
    'lead_code': 'SSC-0004',
    'referral_type': 'specialist',
    'status': 'sent',
    'appointment_type': 'opd',
    'priority': 'routine',
    'patient_name': 'Repro Patient 2',
    'patient_mobile': '9876501234',
    'patient_gender': 'male',
    'case_summary': 'Second repro',
  };

  test('parses create-response referral with int patient_age', () {
    final r = Referral.fromJson({...base, 'patient_age': 38});
    expect(r.patient_age, 38);
    expect(r.id, 4);
    expect(r.visit_type, 'opd');
  });

  test('parses referral when patient_age arrives as string', () {
    final r = Referral.fromJson({...base, 'patient_age': '45'});
    expect(r.patient_age, 45);
  });

  test('parses referral when patient_age is null', () {
    final r = Referral.fromJson({...base, 'patient_age': null});
    expect(r.patient_age, isNull);
  });
}