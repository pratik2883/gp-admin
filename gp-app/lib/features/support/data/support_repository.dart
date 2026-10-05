import 'package:dio/dio.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/foundation.dart';
import 'package:gp_app/core/http/dio_client.dart';
import 'package:gp_app/features/support/models/support_ticket.dart';

class SupportRepository {
  final Dio _dio;

  SupportRepository(DioClient client) : _dio = client.dio;

  Future<List<SupportTicket>> fetchTickets() async {
    final res = await _dio.get('/api/support-tickets');
    final data = res.data as Map<String, dynamic>;
    final items = (data['data'] as List?) ?? const [];

    return items
        .whereType<Map>()
        .map((e) => SupportTicket.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<SupportTicket> fetchTicket(int id) async {
    final res = await _dio.get('/api/support-tickets/$id');
    return SupportTicket.fromJson(Map<String, dynamic>.from(res.data as Map<String, dynamic>));
  }

  Future<MultipartFile> _attachmentMultipart(PlatformFile file) async {
    final name = file.name.isNotEmpty ? file.name : 'attachment';
    if (kIsWeb || file.bytes != null) {
      if (file.bytes != null && file.bytes!.isNotEmpty) {
        return MultipartFile.fromBytes(file.bytes!, filename: name);
      }
    }
    if (file.path != null && file.path!.isNotEmpty) {
      return MultipartFile.fromFile(file.path!, filename: name);
    }
    return MultipartFile.fromBytes(file.bytes ?? const [], filename: name);
  }

  Future<SupportTicket> createTicket({
    required String subject,
    required String category,
    required String priority,
    required String message,
    List<String> filePaths = const [],
    List<PlatformFile> attachments = const [],
  }) async {
    final form = FormData.fromMap({
      'subject': subject,
      'category': category,
      'priority': priority,
      'message': message,
    });

    for (final file in attachments) {
      form.files.add(MapEntry('attachments[]', await _attachmentMultipart(file)));
    }
    if (attachments.isEmpty) {
      for (final path in filePaths) {
        form.files.add(MapEntry('attachments[]', await MultipartFile.fromFile(path)));
      }
    }

    final res = await _dio.post('/api/support-tickets', data: form);
    return SupportTicket.fromJson(Map<String, dynamic>.from(res.data as Map<String, dynamic>));
  }

  Future<SupportTicket> replyToTicket({
    required int ticketId,
    required String message,
    List<String> filePaths = const [],
    List<PlatformFile> attachments = const [],
  }) async {
    final form = FormData.fromMap({
      'message': message,
    });

    for (final file in attachments) {
      form.files.add(MapEntry('attachments[]', await _attachmentMultipart(file)));
    }
    if (attachments.isEmpty) {
      for (final path in filePaths) {
        form.files.add(MapEntry('attachments[]', await MultipartFile.fromFile(path)));
      }
    }

    final res = await _dio.post('/api/support-tickets/$ticketId/reply', data: form);
    return SupportTicket.fromJson(Map<String, dynamic>.from(res.data as Map<String, dynamic>));
  }
}
