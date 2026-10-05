import 'package:flutter_test/flutter_test.dart';
import 'package:gp_app/features/specialist/models/lead_detail_response.dart';

void main() {
  final liveDetail = {
    'lead': {
      'id': 5,
      'lead_code': 'SSC-0003',
      'referral_type': 'specialist',
      'status': 'sent',
      'appointment_type': 'opd',
      'priority': 'routine',
      'department': null,
      'patient_name': 'Lead Repro',
      'patient_mobile': '9876501234',
      'patient_age': 45,
      'patient_gender': 'male',
      'case_summary': 'Repro for specialist detail',
      'specialist': {'id': 61, 'name': 'Dummy Specialist', 'speciality': 'Cardiology'},
      'hospital': null,
      'gp': {'id': 1, 'name': 'Dr. Vihaan Mehta'},
      'gp_name': 'Dr. Vihaan Mehta',
      'notes': 'Repro for specialist detail',
      'hospital_name': null,
      'files': <dynamic>[],
      'attachments': <dynamic>[],
      'created_at': '2026-08-16T08:27:02.000000Z',
      'accepted_at': null,
      'consulted_at': null,
      'closed_at': null,
    },
  };

  test('parses specialist lead detail from live response', () {
    final res = LeadDetailResponse.fromJson(liveDetail);
    expect(res.lead.id, 5);
    expect(res.lead.lead_code, 'SSC-0003');
    expect(res.lead.status, 'sent');
    expect(res.lead.patient_name, 'Lead Repro');
    expect(res.lead.patient_age, 45);
    expect(res.lead.gp_name, 'Dr. Vihaan Mehta');
    expect(res.lead.notes, 'Repro for specialist detail');
    expect(res.lead.attachments, isEmpty);
    expect(res.lead.created_at, isNotNull);
  });

  test('fetchLeadDetail-style unwrap of Laravel resource wrapper', () {
    final raw = {
      'data': liveDetail['lead'],
    };
    var data = raw as Map<String, dynamic>;
    if (data.containsKey('data') && data['data'] is Map<String, dynamic>) {
      data = data['data'] as Map<String, dynamic>;
    }
    if (!data.containsKey('lead')) {
      data = {'lead': data};
    }
    final res = LeadDetailResponse.fromJson(data);
    expect(res.lead.id, 5);
    expect(res.lead.patient_age, 45);
  });
}