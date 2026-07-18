class NotificationPreferences {
  final bool push;
  final bool email;
  final bool sms;
  final bool whatsapp;

  const NotificationPreferences({
    required this.push,
    required this.email,
    required this.sms,
    required this.whatsapp,
  });

  factory NotificationPreferences.fromJson(Map<String, dynamic> json) {
    final source = json['preferences'] is Map<String, dynamic>
        ? json['preferences'] as Map<String, dynamic>
        : json;

    return NotificationPreferences(
      push: source['push'] == null ? true : source['push'] == true || source['push'] == 1,
      email: source['email'] == null ? true : source['email'] == true || source['email'] == 1,
      sms: source['sms'] == null ? true : source['sms'] == true || source['sms'] == 1,
      whatsapp: source['whatsapp'] == null ? true : source['whatsapp'] == true || source['whatsapp'] == 1,
    );
  }

  Map<String, dynamic> toJson() => {
        'push': push,
        'email': email,
        'sms': sms,
        'whatsapp': whatsapp,
      };

  NotificationPreferences copyWith({
    bool? push,
    bool? email,
    bool? sms,
    bool? whatsapp,
  }) {
    return NotificationPreferences(
      push: push ?? this.push,
      email: email ?? this.email,
      sms: sms ?? this.sms,
      whatsapp: whatsapp ?? this.whatsapp,
    );
  }
}
