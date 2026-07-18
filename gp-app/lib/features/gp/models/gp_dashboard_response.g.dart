// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'gp_dashboard_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_$GpDashboardResponseImpl _$$GpDashboardResponseImplFromJson(
        Map<String, dynamic> json) =>
    _$GpDashboardResponseImpl(
      totalReferrals: (json['total_referrals'] as num?)?.toInt() ?? 0,
      pendingCount: (json['pending_count'] as num?)?.toInt() ?? 0,
      acceptedCount: (json['accepted_count'] as num?)?.toInt() ?? 0,
      consultedCount: (json['consulted_count'] as num?)?.toInt() ?? 0,
      closedCount: (json['closed_count'] as num?)?.toInt() ?? 0,
      recentReferrals: (json['recent_referrals'] as List<dynamic>?)
              ?.map((e) => Referral.fromJson(e as Map<String, dynamic>))
              .toList() ??
          const [],
    );

Map<String, dynamic> _$$GpDashboardResponseImplToJson(
        _$GpDashboardResponseImpl instance) =>
    <String, dynamic>{
      'total_referrals': instance.totalReferrals,
      'pending_count': instance.pendingCount,
      'accepted_count': instance.acceptedCount,
      'consulted_count': instance.consultedCount,
      'closed_count': instance.closedCount,
      'recent_referrals': instance.recentReferrals,
    };
