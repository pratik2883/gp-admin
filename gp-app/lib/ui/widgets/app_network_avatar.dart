import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:gp_app/ui/styles.dart';

/// Physical-pixel decode extent for an avatar drawn at [size] logical pixels.
///
/// Kept as a pure function so the M4-03 budgeting rule is unit-testable.
int avatarDecodeExtent({
  required double size,
  required double devicePixelRatio,
}) {
  return (size * devicePixelRatio).round();
}

/// A bounded, disk/memory-cached network avatar.
///
/// `CachedNetworkImage` with no decode budget lets a handful of full-resolution
/// avatars exhaust Flutter's default 100 MB / 1000-entry [ImageCache]. Passing
/// `memCacheWidth`/`memCacheHeight` (in physical pixels — logical size multiplied
/// by [MediaQueryData.devicePixelRatio]) makes the decoder produce a bitmap sized
/// to the on-screen box instead of the source resolution. This is the M4-03 fix.
class AppNetworkAvatar extends StatelessWidget {
  const AppNetworkAvatar({
    super.key,
    required this.url,
    required this.size,
    this.radius,
    this.fallback,
    this.backgroundColor,
    this.border,
    this.placeholder,
    this.fadeInDuration = const Duration(milliseconds: 150),
  });

  /// Remote image URL. A blank/null value renders [fallback].
  final String? url;

  /// Square edge length in logical pixels.
  final double size;

  /// Corner radius. When null the avatar is rendered as a circle.
  final double? radius;

  /// Shown when [url] is blank or the image fails to load.
  final Widget? fallback;

  /// Backing colour; defaults to a translucent brand tint.
  final Color? backgroundColor;

  final Border? border;

  /// Optional custom loading placeholder; defaults to [fallback].
  final Widget? placeholder;

  final Duration fadeInDuration;

  @override
  Widget build(BuildContext context) {
    final trimmed = url?.trim() ?? '';
    final borderRadius = BorderRadius.circular(radius ?? size / 2);

    // Decode at physical-pixel dimensions so the raster cache never holds a
    // full-resolution avatar (M4-03).
    final decodeExtent = avatarDecodeExtent(
      size: size,
      devicePixelRatio: MediaQuery.devicePixelRatioOf(context),
    );

    // Mirror CircleAvatar, which centres its child.
    final fallbackWidget =
        Center(child: fallback ?? const SizedBox.shrink());
    final placeholderWidget =
        placeholder == null ? fallbackWidget : Center(child: placeholder);

    return Container(
      width: size,
      height: size,
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        color: backgroundColor ?? AppColors.primaryBlue.withAlpha(30),
        borderRadius: borderRadius,
        border: border,
      ),
      child: trimmed.isEmpty
          ? fallbackWidget
          : CachedNetworkImage(
              imageUrl: trimmed,
              fit: BoxFit.cover,
              memCacheWidth: decodeExtent,
              memCacheHeight: decodeExtent,
              fadeInDuration: fadeInDuration,
              placeholder: (_, __) => placeholderWidget,
              errorWidget: (_, __, ___) => fallbackWidget,
            ),
    );
  }
}
