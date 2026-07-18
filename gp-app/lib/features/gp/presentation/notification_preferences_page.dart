import 'package:flutter/material.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:gp_app/core/push/fcm_service.dart';
import 'package:gp_app/features/notifications/data/notification_repository.dart';
import 'package:gp_app/features/notifications/models/notification_preferences.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_bar_gradient.dart';

class NotificationPreferencesPage extends ConsumerStatefulWidget {
  const NotificationPreferencesPage({super.key});

  @override
  ConsumerState<NotificationPreferencesPage> createState() => _NotificationPreferencesPageState();
}

class _NotificationPreferencesPageState extends ConsumerState<NotificationPreferencesPage> {
  NotificationPreferences? _preferences;
  String? _error;
  bool _loading = true;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    Future.microtask(_load);
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final prefs = await ref.read(notificationRepositoryProvider).fetchPreferences();
      if (!mounted) return;
      setState(() {
        _preferences = prefs;
        _loading = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _error = 'Unable to load notification preferences.';
        _loading = false;
      });
    }
  }

  Future<void> _update(NotificationPreferences next) async {
    final previous = _preferences;
    setState(() {
      _preferences = next;
      _saving = true;
      _error = null;
    });

    try {
      final saved = await ref.read(notificationRepositoryProvider).savePreferences(next);
      await _syncPushRegistration(saved.push);
      if (!mounted) return;
      setState(() {
        _preferences = saved;
        _saving = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _preferences = previous;
        _saving = false;
        _error = 'Unable to save notification preferences.';
      });
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Failed to update notification preferences')),
      );
    }
  }

  Future<void> _syncPushRegistration(bool enabled) async {
    final repo = ref.read(notificationRepositoryProvider);
    if (!enabled) {
      final token = await FirebaseMessaging.instance.getToken();
      if (token != null && token.isNotEmpty) {
        await repo.unregisterDeviceToken(token);
      }
      return;
    }

    await ref.read(fcmServiceProvider).initialize();
    final token = await FirebaseMessaging.instance.getToken();
    if (token != null && token.isNotEmpty) {
      await repo.registerDeviceToken(token);
    }
  }

  @override
  Widget build(BuildContext context) {
    final prefs = _preferences;
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Notification Preferences', style: TextStyle(fontWeight: FontWeight.w600)),
        centerTitle: true,
        flexibleSpace: Container(decoration: appBarGradient()),
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            if (_loading)
              const Padding(
                padding: EdgeInsets.only(top: 48),
                child: CircularProgressIndicator(color: AppColors.primaryBlue),
              )
            else if (_error != null && prefs == null)
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: AppColors.cardWhite,
                  borderRadius: AppStyles.radiusCard,
                  boxShadow: AppStyles.cardShadow,
                ),
                child: Column(
                  children: [
                    Text(_error!, style: AppStyles.bodyMedium.copyWith(color: AppColors.statusError)),
                    const SizedBox(height: 12),
                    OutlinedButton(onPressed: _load, child: const Text('Retry')),
                  ],
                ),
              )
            else if (prefs != null)
              Container(
                decoration: BoxDecoration(
                  color: AppColors.cardWhite,
                  borderRadius: AppStyles.radiusCard,
                  boxShadow: AppStyles.cardShadow,
                ),
                child: Column(
                  children: [
                    _NotificationSwitch(
                      title: 'Push Notifications',
                      subtitle: 'Real-time alerts on your device',
                      value: prefs.push,
                      enabled: !_saving,
                      onChanged: (v) => _update(prefs.copyWith(push: v)),
                      icon: Icons.notifications_active_outlined,
                    ),
                    const Divider(height: 1, indent: 64),
                    _NotificationSwitch(
                      title: 'Email Alerts',
                      subtitle: 'Email updates for referral activity',
                      value: prefs.email,
                      enabled: !_saving,
                      onChanged: (v) => _update(prefs.copyWith(email: v)),
                      icon: Icons.alternate_email_rounded,
                    ),
                    const Divider(height: 1, indent: 64),
                    _NotificationSwitch(
                      title: 'SMS Updates',
                      subtitle: 'SMS alerts for referral activity',
                      value: prefs.sms,
                      enabled: !_saving,
                      onChanged: (v) => _update(prefs.copyWith(sms: v)),
                      icon: Icons.sms_outlined,
                    ),
                    const Divider(height: 1, indent: 64),
                    _NotificationSwitch(
                      title: 'WhatsApp Updates',
                      subtitle: 'WhatsApp alerts for subscription and account updates',
                      value: prefs.whatsapp,
                      enabled: !_saving,
                      onChanged: (v) => _update(prefs.copyWith(whatsapp: v)),
                      icon: Icons.chat_outlined,
                    ),
                  ],
                ),
              ),
            if (_saving) ...[
              const SizedBox(height: 12),
              const LinearProgressIndicator(color: AppColors.primaryBlue),
            ],
            if (_error != null && prefs != null) ...[
              const SizedBox(height: 12),
              Text(_error!, style: AppStyles.bodySmall.copyWith(color: AppColors.statusError)),
            ],
            const SizedBox(height: 24),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 12),
              child: Text(
                'These preferences control which channels are used for your account notifications. In-app notifications stay available inside the app timeline.',
                textAlign: TextAlign.center,
                style: AppStyles.bodySmall.copyWith(color: AppColors.textGrey, height: 1.5),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _NotificationSwitch extends StatelessWidget {
  final String title;
  final String subtitle;
  final bool value;
  final bool enabled;
  final ValueChanged<bool> onChanged;
  final IconData icon;

  const _NotificationSwitch({
    required this.title,
    required this.subtitle,
    required this.value,
    required this.enabled,
    required this.onChanged,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return SwitchListTile.adaptive(
      value: value,
      onChanged: enabled ? onChanged : null,
      secondary: Container(
        padding: const EdgeInsets.all(10),
        decoration: BoxDecoration(
          color: value
              ? AppColors.primaryBlue.withValues(alpha: 0.1)
              : Colors.grey.withValues(alpha: 0.1),
          borderRadius: BorderRadius.circular(10),
        ),
        child: Icon(icon, color: value ? AppColors.primaryBlue : AppColors.textGrey, size: 24),
      ),
      title: Text(title, style: AppStyles.bodyLarge.copyWith(fontWeight: FontWeight.w600)),
      subtitle: Text(subtitle, style: AppStyles.bodySmall),
      activeThumbColor: AppColors.primaryBlue,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
    );
  }
}
