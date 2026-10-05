import 'dart:io';
import 'package:flutter_test/flutter_test.dart';
import 'package:gp_app/core/http/dio_client.dart';
import 'package:gp_app/core/storage/secure_storage.dart';

class _NullStorage extends AppSecureStorage {
  @override
  Future<String?> readToken() async => null;
  @override
  Future<String?> readRole() async => null;
}

void main() {
  setUp(() {
    // flutter_test mocks HttpClient by default; allow real network calls.
    HttpOverrides.global = null;
  });

  test('MessageCentral login OTP send via app HTTP stack', () async {
    final client = DioClient(_NullStorage());

    final res = await client.dio.post('/api/auth/send-otp', data: {
      'mobile': '9892711228',
    });

    expect(res.statusCode, 200, reason: 'response: ${res.data}');
    final data = res.data as Map<String, dynamic>;
    expect(data['message'], 'OTP sent successfully');
    final verificationId = data['verification_id'];
    expect(verificationId, isA<String>());
    expect((verificationId as String).isNotEmpty, isTrue);

    // ignore: avoid_print
    print('FLUTTER_APP_LOGIN_OTP verification_id=$verificationId');
  });
}