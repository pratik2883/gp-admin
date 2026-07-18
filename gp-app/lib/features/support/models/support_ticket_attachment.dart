class SupportTicketAttachment {
  final int id;
  final String originalName;
  final String? mimeType;
  final int? size;
  final String? url;
  final DateTime? createdAt;

  const SupportTicketAttachment({
    required this.id,
    required this.originalName,
    this.mimeType,
    this.size,
    this.url,
    this.createdAt,
  });

  factory SupportTicketAttachment.fromJson(Map<String, dynamic> json) {
    return SupportTicketAttachment(
      id: (json['id'] as num?)?.toInt() ?? 0,
      originalName: (json['original_name'] ?? '').toString(),
      mimeType: json['mime_type']?.toString(),
      size: (json['size'] as num?)?.toInt(),
      url: json['url']?.toString(),
      createdAt: json['created_at'] == null ? null : DateTime.tryParse(json['created_at'].toString()),
    );
  }
}
