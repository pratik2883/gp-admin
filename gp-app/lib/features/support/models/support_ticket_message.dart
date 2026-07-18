import 'package:gp_app/features/support/models/support_ticket_attachment.dart';

class SupportTicketMessage {
  final int id;
  final String senderType;
  final int? senderId;
  final String? senderName;
  final String message;
  final List<SupportTicketAttachment> attachments;
  final DateTime? createdAt;

  const SupportTicketMessage({
    required this.id,
    required this.senderType,
    this.senderId,
    this.senderName,
    required this.message,
    this.attachments = const [],
    this.createdAt,
  });

  factory SupportTicketMessage.fromJson(Map<String, dynamic> json) {
    final attachmentsJson = (json['attachments'] as List?) ?? const [];

    return SupportTicketMessage(
      id: (json['id'] as num?)?.toInt() ?? 0,
      senderType: (json['sender_type'] ?? 'user').toString(),
      senderId: (json['sender_id'] as num?)?.toInt(),
      senderName: json['sender_name']?.toString(),
      message: (json['message'] ?? '').toString(),
      attachments: attachmentsJson
          .whereType<Map>()
          .map((e) => SupportTicketAttachment.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
      createdAt: json['created_at'] == null ? null : DateTime.tryParse(json['created_at'].toString()),
    );
  }
}
