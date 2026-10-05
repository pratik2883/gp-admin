import 'package:gp_app/features/support/models/support_ticket_message.dart';

class SupportTicket {
  final int id;
  final String ticketNo;
  final String roleType;
  final String subject;
  final String? category;
  final String priority;
  final String status;
  final bool canReply;
  final DateTime? lastReplyAt;
  final DateTime? resolvedAt;
  final DateTime? closedAt;
  final DateTime? createdAt;
  final DateTime? updatedAt;
  final String? assignedAdminName;
  final List<SupportTicketMessage> messages;

  const SupportTicket({
    required this.id,
    required this.ticketNo,
    required this.roleType,
    required this.subject,
    this.category,
    required this.priority,
    required this.status,
    required this.canReply,
    this.lastReplyAt,
    this.resolvedAt,
    this.closedAt,
    this.createdAt,
    this.updatedAt,
    this.assignedAdminName,
    this.messages = const [],
  });

  factory SupportTicket.fromJson(Map<String, dynamic> rawJson) {
    final json = (rawJson['data'] is Map<String, dynamic>)
        ? rawJson['data'] as Map<String, dynamic>
        : rawJson;

    final assignedAdmin = json['assigned_admin'] is Map<String, dynamic>
        ? json['assigned_admin'] as Map<String, dynamic>
        : null;
    final messagesJson = (json['messages'] as List?) ?? const [];

    return SupportTicket(
      id: (json['id'] as num?)?.toInt() ?? 0,
      ticketNo: (json['ticket_no'] ?? '').toString(),
      roleType: (json['role_type'] ?? '').toString(),
      subject: (json['subject'] ?? '').toString(),
      category: json['category']?.toString(),
      priority: (json['priority'] ?? 'medium').toString(),
      status: (json['status'] ?? 'open').toString(),
      canReply: json['can_reply'] == true || json['can_reply'] == 1,
      lastReplyAt: json['last_reply_at'] == null ? null : DateTime.tryParse(json['last_reply_at'].toString()),
      resolvedAt: json['resolved_at'] == null ? null : DateTime.tryParse(json['resolved_at'].toString()),
      closedAt: json['closed_at'] == null ? null : DateTime.tryParse(json['closed_at'].toString()),
      createdAt: json['created_at'] == null ? null : DateTime.tryParse(json['created_at'].toString()),
      updatedAt: json['updated_at'] == null ? null : DateTime.tryParse(json['updated_at'].toString()),
      assignedAdminName: assignedAdmin?['name']?.toString(),
      messages: messagesJson
          .whereType<Map>()
          .map((e) => SupportTicketMessage.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
    );
  }
}
