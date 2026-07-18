import 'package:flutter/material.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_bar_gradient.dart';
import 'package:url_launcher/url_launcher.dart';

class PolicyPage extends StatelessWidget {
  final String title;
  final String webUrl;
  final String? markdownBody;

  const PolicyPage({
    super.key,
    required this.title,
    required this.webUrl,
    this.markdownBody,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.w600)),
        centerTitle: true,
        flexibleSpace: Container(decoration: appBarGradient()),
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Container(
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            color: AppColors.cardWhite,
            borderRadius: AppStyles.radiusCard,
            boxShadow: AppStyles.cardShadow,
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: AppStyles.heading2),
              const SizedBox(height: 16),
              if (markdownBody != null && markdownBody!.isNotEmpty)
                ..._parseMarkdown(markdownBody!)
              else
                Text('Content not available.', style: AppStyles.bodyMedium),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: () async {
                    final uri = Uri.tryParse(webUrl);
                    if (uri != null && await canLaunchUrl(uri)) {
                      await launchUrl(uri, mode: LaunchMode.externalApplication);
                    }
                  },
                  icon: const Icon(Icons.open_in_new_rounded, size: 18),
                  label: const Text('View full page on website'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  List<Widget> _parseMarkdown(String text) {
    final lines = text.split('\n').where((l) => l.trim().isNotEmpty).toList();
    final widgets = <Widget>[];
    bool inList = false;

    for (final line in lines) {
      final trimmed = line.trimLeft();

      if (trimmed.startsWith('## ')) {
        if (inList) { inList = false; }
        widgets.add(const SizedBox(height: 16));
        widgets.add(Text(
          trimmed.substring(3),
          style: AppStyles.heading3.copyWith(fontWeight: FontWeight.w700),
        ));
        widgets.add(const SizedBox(height: 8));
      } else if (trimmed.startsWith('# ')) {
        if (inList) { inList = false; }
        widgets.add(Text(
          trimmed.substring(2),
          style: AppStyles.heading2,
        ));
        widgets.add(const SizedBox(height: 12));
      } else if (trimmed.startsWith('- ') || trimmed.startsWith('* ')) {
        if (!inList) { inList = true; }
        widgets.add(Padding(
          padding: const EdgeInsets.only(left: 8, bottom: 4),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('  •  ', style: TextStyle(fontWeight: FontWeight.bold)),
              Expanded(child: Text(trimmed.substring(2), style: AppStyles.bodyMedium.copyWith(height: 1.5))),
            ],
          ),
        ));
      } else {
        if (inList) { inList = false; }
        widgets.add(Text(trimmed, style: AppStyles.bodyMedium.copyWith(height: 1.6)));
        widgets.add(const SizedBox(height: 8));
      }
    }

    return widgets;
  }
}
