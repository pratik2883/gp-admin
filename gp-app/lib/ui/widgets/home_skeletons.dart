import 'package:flutter/material.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:gp_app/ui/widgets/app_card.dart';

class AppShimmer extends StatefulWidget {
  final Widget child;
  final bool enabled;
  final Duration period;
  final Color baseColor;
  final Color highlightColor;

  const AppShimmer({
    super.key,
    required this.child,
    this.enabled = true,
    this.period = const Duration(milliseconds: 1400),
    this.baseColor = AppColors.surfaceMuted,
    this.highlightColor = AppColors.surface,
  });

  @override
  State<AppShimmer> createState() => _AppShimmerState();
}

class _AppShimmerState extends State<AppShimmer> with SingleTickerProviderStateMixin {
  late final AnimationController _controller;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(vsync: this, duration: widget.period)..repeat();
  }

  @override
  void didUpdateWidget(covariant AppShimmer oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.period != widget.period) {
      _controller.duration = widget.period;
      if (_controller.isAnimating) {
        _controller
          ..reset()
          ..repeat();
      }
    }
    if (oldWidget.enabled != widget.enabled) {
      if (widget.enabled) {
        _controller.repeat();
      } else {
        _controller.stop();
      }
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (!widget.enabled) return widget.child;

    return RepaintBoundary(
      child: AnimatedBuilder(
        animation: _controller,
        child: widget.child,
        builder: (context, child) {
          return ShaderMask(
            blendMode: BlendMode.srcATop,
            shaderCallback: (rect) {
              final t = _controller.value;
              return LinearGradient(
                begin: const Alignment(-1.0, -0.2),
                end: const Alignment(1.0, 0.2),
                colors: [
                  widget.baseColor,
                  widget.highlightColor,
                  widget.baseColor,
                ],
                stops: const [0.1, 0.5, 0.9],
                transform: _ShimmerTransform(slidePercent: t),
              ).createShader(rect);
            },
            child: child,
          );
        },
      ),
    );
  }
}

class _ShimmerTransform extends GradientTransform {
  final double slidePercent;

  const _ShimmerTransform({required this.slidePercent});

  @override
  Matrix4 transform(Rect bounds, {TextDirection? textDirection}) {
    final dx = (bounds.width * 2) * (slidePercent - 0.5);
    return Matrix4.translationValues(dx, 0.0, 0.0);
  }
}

class AppSkeletonBox extends StatelessWidget {
  final double? width;
  final double height;
  final BorderRadiusGeometry borderRadius;
  final Color color;

  const AppSkeletonBox({
    super.key,
    this.width,
    required this.height,
    this.borderRadius = const BorderRadius.all(Radius.circular(14)),
    this.color = AppColors.surfaceMuted,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: width,
      height: height,
      decoration: BoxDecoration(color: color, borderRadius: borderRadius),
    );
  }
}

class StatSummaryCardSkeleton extends StatelessWidget {
  const StatSummaryCardSkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: AppStyles.radiusCard,
        border: Border.all(color: AppColors.border),
        boxShadow: AppStyles.cardShadow,
      ),
      child: Row(
        children: const [
          AppSkeletonBox(
            width: 44,
            height: 44,
            borderRadius: BorderRadius.all(Radius.circular(14)),
          ),
          SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                AppSkeletonBox(width: 110, height: 10, borderRadius: BorderRadius.all(Radius.circular(999))),
                SizedBox(height: 8),
                AppSkeletonBox(width: 72, height: 14, borderRadius: BorderRadius.all(Radius.circular(999))),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class ReferralPatientCardSkeleton extends StatelessWidget {
  final bool showChip;

  const ReferralPatientCardSkeleton({super.key, this.showChip = true});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: AppStyles.radiusCard,
        border: Border.all(color: AppColors.border),
        boxShadow: AppStyles.cardShadow,
      ),
      child: Row(
        children: [
          const AppSkeletonBox(
            width: 44,
            height: 44,
            borderRadius: BorderRadius.all(Radius.circular(16)),
          ),
          const SizedBox(width: 12),
          const Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                AppSkeletonBox(width: 140, height: 12, borderRadius: BorderRadius.all(Radius.circular(999))),
                SizedBox(height: 8),
                AppSkeletonBox(width: 190, height: 10, borderRadius: BorderRadius.all(Radius.circular(999))),
                SizedBox(height: 8),
                AppSkeletonBox(width: 110, height: 10, borderRadius: BorderRadius.all(Radius.circular(999))),
              ],
            ),
          ),
          if (showChip) ...[
            const SizedBox(width: 10),
            const AppSkeletonBox(width: 64, height: 26, borderRadius: BorderRadius.all(Radius.circular(999))),
          ],
        ],
      ),
    );
  }
}

class QuickActionTileSkeleton extends StatelessWidget {
  const QuickActionTileSkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: AppStyles.radiusCard,
        border: Border.all(color: AppColors.border),
        boxShadow: AppStyles.cardShadow,
      ),
      child: const Row(
        children: [
          AppSkeletonBox(width: 44, height: 44, borderRadius: BorderRadius.all(Radius.circular(14))),
          SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                AppSkeletonBox(width: 120, height: 12, borderRadius: BorderRadius.all(Radius.circular(999))),
                SizedBox(height: 8),
                AppSkeletonBox(width: 90, height: 10, borderRadius: BorderRadius.all(Radius.circular(999))),
              ],
            ),
          ),
          SizedBox(width: 8),
          AppSkeletonBox(width: 18, height: 18, borderRadius: BorderRadius.all(Radius.circular(6))),
        ],
      ),
    );
  }
}

class SpecialistSuggestionCardSkeleton extends StatelessWidget {
  const SpecialistSuggestionCardSkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 240,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: AppStyles.radiusCard,
        border: Border.all(color: AppColors.border),
        boxShadow: AppStyles.cardShadow,
      ),
      child: const Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          AppSkeletonBox(width: 52, height: 52, borderRadius: BorderRadius.all(Radius.circular(18))),
          SizedBox(height: 12),
          AppSkeletonBox(width: 160, height: 12, borderRadius: BorderRadius.all(Radius.circular(999))),
          SizedBox(height: 8),
          AppSkeletonBox(width: 130, height: 10, borderRadius: BorderRadius.all(Radius.circular(999))),
          SizedBox(height: 10),
          AppSkeletonBox(width: 90, height: 10, borderRadius: BorderRadius.all(Radius.circular(999))),
        ],
      ),
    );
  }
}

class GpHomeSkeleton extends StatelessWidget {
  final bool shimmer;

  const GpHomeSkeleton({super.key, this.shimmer = true});

  @override
  Widget build(BuildContext context) {
    final content = Column(
      children: [
        const _GpHomeHeroSkeleton(),
        Expanded(
          child: ListView(
            padding: AppSpacing.screenPadding.copyWith(top: 16, bottom: 110),
            children: const [
              QuickActionTileSkeleton(),
              SizedBox(height: 12),
              QuickActionTileSkeleton(),
              SizedBox(height: 12),
              QuickActionTileSkeleton(),
              SizedBox(height: 18),
              SizedBox(
                height: 92,
                child: _StatRowSkeleton(),
              ),
              SizedBox(height: 22),
              _SectionHeaderSkeleton(),
              SizedBox(height: 12),
              ReferralPatientCardSkeleton(),
              SizedBox(height: 12),
              ReferralPatientCardSkeleton(),
              SizedBox(height: 12),
              ReferralPatientCardSkeleton(),
              SizedBox(height: 14),
              _SectionHeaderSkeleton(),
              SizedBox(height: 12),
              SizedBox(height: 190, child: _SpecialistsRowSkeleton()),
            ],
          ),
        ),
      ],
    );

    return shimmer ? AppShimmer(child: content) : content;
  }
}

class _GpHomeHeroSkeleton extends StatelessWidget {
  const _GpHomeHeroSkeleton();

  @override
  Widget build(BuildContext context) {
    final safe = MediaQuery.paddingOf(context).top;

    return Container(
      decoration: const BoxDecoration(
        gradient: AppColors.heroGradient,
        borderRadius: BorderRadius.only(
          bottomLeft: Radius.circular(28),
          bottomRight: Radius.circular(28),
        ),
      ),
      padding: EdgeInsets.only(top: safe + 12, left: 16, right: 16, bottom: 18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: const [
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    AppSkeletonBox(width: 210, height: 16, borderRadius: BorderRadius.all(Radius.circular(999)), color: Color(0x33FFFFFF)),
                    SizedBox(height: 8),
                    AppSkeletonBox(width: 160, height: 12, borderRadius: BorderRadius.all(Radius.circular(999)), color: Color(0x26FFFFFF)),
                  ],
                ),
              ),
              SizedBox(width: 10),
              AppSkeletonBox(width: 38, height: 38, borderRadius: BorderRadius.all(Radius.circular(14)), color: Color(0x26FFFFFF)),
            ],
          ),
          SizedBox(height: 14),
          AppSkeletonBox(height: 50, borderRadius: BorderRadius.all(Radius.circular(18)), color: Color(0x26FFFFFF)),
        ],
      ),
    );
  }
}

class _SectionHeaderSkeleton extends StatelessWidget {
  const _SectionHeaderSkeleton();

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: const [
        AppSkeletonBox(width: 190, height: 14, borderRadius: BorderRadius.all(Radius.circular(999))),
        AppSkeletonBox(width: 64, height: 12, borderRadius: BorderRadius.all(Radius.circular(999))),
      ],
    );
  }
}

class _StatRowSkeleton extends StatelessWidget {
  const _StatRowSkeleton();

  @override
  Widget build(BuildContext context) {
    return ListView.separated(
      scrollDirection: Axis.horizontal,
      itemBuilder: (_, i) => const SizedBox(width: 220, child: StatSummaryCardSkeleton()),
      separatorBuilder: (_, __) => const SizedBox(width: 12),
      itemCount: 3,
    );
  }
}

class _SpecialistsRowSkeleton extends StatelessWidget {
  const _SpecialistsRowSkeleton();

  @override
  Widget build(BuildContext context) {
    return ListView.separated(
      scrollDirection: Axis.horizontal,
      padding: const EdgeInsets.only(right: 4),
      itemBuilder: (_, i) => const SpecialistSuggestionCardSkeleton(),
      separatorBuilder: (_, __) => const SizedBox(width: 12),
      itemCount: 3,
    );
  }
}

class MetaRowSkeleton extends StatelessWidget {
  const MetaRowSkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Row(
        children: const [
          AppSkeletonBox(
            width: 40,
            height: 40,
            borderRadius: BorderRadius.all(Radius.circular(16)),
          ),
          SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                AppSkeletonBox(width: 72, height: 10, borderRadius: BorderRadius.all(Radius.circular(999))),
                SizedBox(height: 8),
                AppSkeletonBox(width: 140, height: 12, borderRadius: BorderRadius.all(Radius.circular(999))),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class DetailSectionSkeleton extends StatelessWidget {
  final double titleWidth;
  final Widget child;

  const DetailSectionSkeleton({
    super.key,
    required this.child,
    this.titleWidth = 160,
  });

  @override
  Widget build(BuildContext context) {
    return AppCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          AppSkeletonBox(width: titleWidth, height: 14, borderRadius: const BorderRadius.all(Radius.circular(999))),
          const SizedBox(height: 12),
          child,
        ],
      ),
    );
  }
}

class TimelineSkeletonRow extends StatelessWidget {
  const TimelineSkeletonRow({super.key});

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: const [
        Column(
          children: [
            AppSkeletonBox(width: 10, height: 10, borderRadius: BorderRadius.all(Radius.circular(999))),
            SizedBox(height: 8),
            AppSkeletonBox(width: 2, height: 34, borderRadius: BorderRadius.all(Radius.circular(999))),
          ],
        ),
        SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              AppSkeletonBox(width: 160, height: 12, borderRadius: BorderRadius.all(Radius.circular(999))),
              SizedBox(height: 8),
              AppSkeletonBox(width: 110, height: 10, borderRadius: BorderRadius.all(Radius.circular(999))),
            ],
          ),
        ),
      ],
    );
  }
}

class ReferralDetailSkeleton extends StatelessWidget {
  final bool shimmer;

  const ReferralDetailSkeleton({super.key, this.shimmer = true});

  @override
  Widget build(BuildContext context) {
    final content = ListView(
      padding: AppSpacing.screenPadding.copyWith(top: 18, bottom: 28),
      children: [
        Row(
          children: const [
            Expanded(
              child: AppSkeletonBox(
                width: 220,
                height: 16,
                borderRadius: BorderRadius.all(Radius.circular(999)),
              ),
            ),
            SizedBox(width: 10),
            AppSkeletonBox(width: 72, height: 26, borderRadius: BorderRadius.all(Radius.circular(999))),
          ],
        ),
        const SizedBox(height: 12),
        const ReferralPatientCardSkeleton(),
        const SizedBox(height: 12),
        AppCard(
          child: Row(
            children: const [
              MetaRowSkeleton(),
              SizedBox(width: 12),
              MetaRowSkeleton(),
            ],
          ),
        ),
        const SizedBox(height: 16),
        DetailSectionSkeleton(
          titleWidth: 140,
          child: Column(
            children: const [
              _InfoPairSkeleton(),
              SizedBox(height: 10),
              _InfoPairSkeleton(),
              SizedBox(height: 10),
              _InfoPairSkeleton(),
            ],
          ),
        ),
        const SizedBox(height: 12),
        DetailSectionSkeleton(
          titleWidth: 150,
          child: Column(
            children: const [
              _InfoPairSkeleton(),
              SizedBox(height: 10),
              _InfoPairSkeleton(),
              SizedBox(height: 10),
              _InfoPairSkeleton(),
            ],
          ),
        ),
        const SizedBox(height: 12),
        DetailSectionSkeleton(
          titleWidth: 120,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: const [
              AppSkeletonBox(width: double.infinity, height: 12, borderRadius: BorderRadius.all(Radius.circular(999))),
              SizedBox(height: 10),
              AppSkeletonBox(width: double.infinity, height: 12, borderRadius: BorderRadius.all(Radius.circular(999))),
              SizedBox(height: 10),
              AppSkeletonBox(width: 220, height: 12, borderRadius: BorderRadius.all(Radius.circular(999))),
            ],
          ),
        ),
        const SizedBox(height: 12),
        DetailSectionSkeleton(
          titleWidth: 150,
          child: Column(
            children: const [
              TimelineSkeletonRow(),
              SizedBox(height: 12),
              TimelineSkeletonRow(),
              SizedBox(height: 12),
              TimelineSkeletonRow(),
            ],
          ),
        ),
      ],
    );

    return shimmer ? AppShimmer(child: content) : content;
  }
}

class _InfoPairSkeleton extends StatelessWidget {
  const _InfoPairSkeleton();

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: const [
        Expanded(
          child: AppSkeletonBox(width: 120, height: 10, borderRadius: BorderRadius.all(Radius.circular(999))),
        ),
        SizedBox(width: 12),
        Expanded(
          child: Align(
            alignment: Alignment.centerRight,
            child: AppSkeletonBox(width: 140, height: 12, borderRadius: BorderRadius.all(Radius.circular(999))),
          ),
        ),
      ],
    );
  }
}

class NotificationCardSkeleton extends StatelessWidget {
  final bool showTrailingDot;

  const NotificationCardSkeleton({super.key, this.showTrailingDot = true});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: AppStyles.radiusCard,
        boxShadow: AppStyles.cardShadow,
        border: Border.all(color: AppColors.borderLight, width: 0.5),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const AppSkeletonBox(
            width: 36,
            height: 36,
            borderRadius: BorderRadius.all(Radius.circular(999)),
          ),
          const SizedBox(width: AppSpacing.md),
          const Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                AppSkeletonBox(width: 190, height: 14, borderRadius: BorderRadius.all(Radius.circular(999))),
                SizedBox(height: AppSpacing.xs),
                AppSkeletonBox(width: double.infinity, height: 10, borderRadius: BorderRadius.all(Radius.circular(999))),
                SizedBox(height: 8),
                AppSkeletonBox(width: 140, height: 10, borderRadius: BorderRadius.all(Radius.circular(999))),
              ],
            ),
          ),
          if (showTrailingDot) ...[
            const SizedBox(width: 10),
            const AppSkeletonBox(width: 8, height: 8, borderRadius: BorderRadius.all(Radius.circular(999))),
          ],
        ],
      ),
    );
  }
}

class NotificationsListSkeleton extends StatelessWidget {
  final int count;
  final bool shimmer;

  const NotificationsListSkeleton({
    super.key,
    this.count = 6,
    this.shimmer = true,
  });

  @override
  Widget build(BuildContext context) {
    final list = ListView.separated(
      padding: const EdgeInsets.all(AppSpacing.md),
      physics: const AlwaysScrollableScrollPhysics(),
      itemBuilder: (_, i) => const NotificationCardSkeleton(),
      separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.md),
      itemCount: count,
    );

    return shimmer ? AppShimmer(child: list) : list;
  }
}
