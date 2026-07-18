import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

typedef PaymentStatusFetcher = Future<Map<String, dynamic>> Function(String statusUrl);

class SubscriptionPaymentHelper {
  static Future<bool> handlePendingPayment({
    required BuildContext context,
    required Map<String, dynamic>? payment,
    required PaymentStatusFetcher fetchStatus,
  }) async {
    final checkoutUrl = payment?['checkout_url']?.toString();
    final statusUrl = payment?['status_url']?.toString();

    if ((checkoutUrl ?? '').isEmpty || (statusUrl ?? '').isEmpty) {
      return true;
    }

    await _openCheckout(checkoutUrl!);

    if (!context.mounted) return false;

    final mockMode = payment?['mock_mode'] == true;
    return await showDialog<bool>(
          context: context,
          barrierDismissible: false,
          builder: (dialogContext) {
            var checking = false;
            String info = mockMode
                ? 'Mock payment page open zali ahe. Browser madhye success/failure simulate kara, mag status check kara.'
                : 'Payment page browser madhye open zali ahe. Payment complete zalyavar status check kara.';

            return StatefulBuilder(
              builder: (context, setState) {
                Future<void> checkStatus() async {
                  setState(() {
                    checking = true;
                    info = 'Checking payment status...';
                  });

                  try {
                    final result = await fetchStatus(statusUrl!);
                    final subscription = result['subscription'];
                    final subscriptionMap = subscription is Map
                        ? subscription.cast<String, dynamic>()
                        : <String, dynamic>{};
                    final isActive = subscriptionMap['is_active'] == true ||
                        subscriptionMap['status']?.toString() == 'active';
                    final failureMessage =
                        result['failure_message']?.toString();
                    final orderStatus = result['order_status']?.toString();

                    if (isActive) {
                      if (context.mounted) {
                        Navigator.of(dialogContext).pop(true);
                      }
                      return;
                    }

                    setState(() {
                      info = failureMessage?.trim().isNotEmpty == true
                          ? failureMessage!
                          : (orderStatus?.trim().isNotEmpty == true
                              ? 'Current payment status: $orderStatus'
                              : 'Payment ajun complete zala nahi.');
                    });
                  } catch (_) {
                    setState(() {
                      info = 'Payment status fetch karta ala nahi. Puna try kara.';
                    });
                  } finally {
                    if (dialogContext.mounted) {
                      setState(() => checking = false);
                    }
                  }
                }

                return AlertDialog(
                  title: const Text('Complete Payment'),
                  content: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(info),
                      const SizedBox(height: 12),
                      const Text(
                        'Payment complete zalyavar "Check Status" var tap kara.',
                      ),
                    ],
                  ),
                  actions: [
                    TextButton(
                      onPressed: checking ? null : () => Navigator.of(dialogContext).pop(false),
                      child: const Text('Later'),
                    ),
                    TextButton(
                      onPressed: checking ? null : () => _openCheckout(checkoutUrl),
                      child: const Text('Open Payment Again'),
                    ),
                    FilledButton(
                      onPressed: checking ? null : checkStatus,
                      child: Text(checking ? 'Checking...' : 'Check Status'),
                    ),
                  ],
                );
              },
            );
          },
        ) ??
        false;
  }

  static Future<void> _openCheckout(String checkoutUrl) async {
    final uri = Uri.tryParse(checkoutUrl);
    if (uri == null) return;

    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }
}
