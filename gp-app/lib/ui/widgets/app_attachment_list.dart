import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:gp_app/ui/styles.dart';

class AppAttachmentList extends StatelessWidget {
  final List<PlatformFile> files;
  final ValueChanged<PlatformFile> onRemove;

  const AppAttachmentList({
    super.key,
    required this.files,
    required this.onRemove,
  });

  @override
  Widget build(BuildContext context) {
    if (files.isEmpty) return const SizedBox.shrink();

    return Column(
      children: [
        for (final file in files)
          Container(
            margin: const EdgeInsets.only(bottom: 10),
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: AppStyles.radiusInput,
              border: Border.all(color: AppColors.border),
            ),
            child: Row(
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    color: AppColors.primaryBlue.withAlpha(18),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(
                    file.extension == 'pdf' ? Icons.description_outlined : Icons.biotech_outlined,
                    color: AppColors.primaryBlue,
                    size: 18,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        file.name,
                        style: AppStyles.bodySmall.copyWith(fontWeight: FontWeight.w800, color: AppColors.textPrimary),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 2),
                      Text(
                        '${(file.size / (1024 * 1024)).toStringAsFixed(2)} MB',
                        style: AppStyles.caption,
                      ),
                    ],
                  ),
                ),
                IconButton(
                  onPressed: () => onRemove(file),
                  icon: const Icon(Icons.close_rounded, color: AppColors.statusError),
                ),
              ],
            ),
          ),
      ],
    );
  }
}

