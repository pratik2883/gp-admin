import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_bar_gradient.dart';

class SupportPage extends StatelessWidget {
  const SupportPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Support', style: TextStyle(fontWeight: FontWeight.w600)),
        centerTitle: true,
        flexibleSpace: Container(decoration: appBarGradient()),
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: AppColors.cardWhite,
                borderRadius: AppStyles.radiusCard,
                boxShadow: AppStyles.cardShadow,
              ),
              child: Column(
                children: [
                  const Icon(Icons.support_agent_rounded, size: 64, color: AppColors.primaryBlue),
                  const SizedBox(height: 16),
                  Text('How can we help you?', style: AppStyles.heading2),
                  const SizedBox(height: 8),
                  Text(
                    'Our dedicated support team is available 24/7 to assist with your referrals and account inquiries.',
                    textAlign: TextAlign.center,
                    style: AppStyles.bodySmall.copyWith(height: 1.5),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),
            _SupportTile(
              icon: Icons.help_outline_rounded,
              title: 'Help Center & FAQ',
              subtitle: 'Common questions answered instantly',
              onTap: () {},
            ),
            const SizedBox(height: 12),
            _SupportTile(
              icon: Icons.chat_bubble_outline_rounded,
              title: 'Live Chat Support',
              subtitle: 'Average response time: 2 mins',
              onTap: () {},
            ),
            const SizedBox(height: 12),
            _SupportTile(
              icon: Icons.email_outlined,
              title: 'Email Our Desk',
              subtitle: 'support@gp-specialist.com',
              onTap: () {},
            ),
            const SizedBox(height: 12),
            _SupportTile(
              icon: Icons.policy_outlined,
              title: 'Privacy Policy',
              subtitle: 'Read our terms and usage policies',
              onTap: () => context.push('/policy/privacy'),
            ),
            const SizedBox(height: 12),
            _SupportTile(
              icon: Icons.description_outlined,
              title: 'Terms & Conditions',
              subtitle: 'Platform usage terms and conditions',
              onTap: () => context.push('/policy/terms'),
            ),
            const SizedBox(height: 12),
            _SupportTile(
              icon: Icons.notifications_outlined,
              title: 'Notification Consent',
              subtitle: 'How we communicate with you',
              onTap: () => context.push('/policy/notification-consent'),
            ),
            const SizedBox(height: 32),
            Text(
              'App Version: 1.0.2-prod (Build 904)',
              style: AppStyles.bodySmall.copyWith(color: AppColors.textGrey, fontSize: 10),
            ),
          ],
        ),
      ),
    );
  }
}

class _SupportTile extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  const _SupportTile({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: AppStyles.radiusCard,
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: AppColors.cardWhite,
          borderRadius: AppStyles.radiusCard,
          boxShadow: [
             BoxShadow(
              color: Colors.black.withOpacity(0.02),
              blurRadius: 8,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: AppColors.primaryBlue.withOpacity(0.08),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(icon, color: AppColors.primaryBlue, size: 24),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: AppStyles.bodyLarge.copyWith(fontWeight: FontWeight.w600)),
                  Text(subtitle, style: AppStyles.bodySmall),
                ],
              ),
            ),
            const Icon(Icons.chevron_right_rounded, color: AppColors.textGrey),
          ],
        ),
      ),
    );
  }
}
