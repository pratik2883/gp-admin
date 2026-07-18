import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:gp_app/app/role.dart';
import 'package:gp_app/features/auth/state/auth_state.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/app/splash_page.dart';
import 'package:gp_app/features/auth/presentation/otp_phone_page.dart';
import 'package:gp_app/features/auth/presentation/otp_verify_page.dart';
import 'package:gp_app/features/intro/presentation/intro_splash_page.dart';
import 'package:gp_app/features/role/presentation/role_selection_page.dart';
import 'package:gp_app/features/gp/presentation/gp_login_page.dart';
import 'package:gp_app/features/gp/presentation/gp_home_page.dart';
import 'package:gp_app/features/gp/presentation/gp_diagnostics_page.dart';
import 'package:gp_app/features/gp/presentation/gp_referral_list_page.dart';
import 'package:gp_app/features/gp/presentation/gp_new_referral_page.dart';
import 'package:gp_app/features/gp/presentation/gp_referral_detail_page.dart';
import 'package:gp_app/features/gp/presentation/gp_profile_page.dart';
import 'package:gp_app/features/gp/presentation/gp_register_page.dart';
import 'package:gp_app/features/gp/presentation/gp_specialist_listing_page.dart';
import 'package:gp_app/features/gp/presentation/gp_hospital_list_page.dart';
import 'package:gp_app/features/gp/presentation/gp_diagnostic_center_list_page.dart';
import 'package:gp_app/features/gp/presentation/gp_categories_list_page.dart';
import 'package:gp_app/features/specialist/presentation/sp_login_page.dart';
import 'package:gp_app/features/specialist/presentation/sp_register_page.dart';
import 'package:gp_app/features/specialist/presentation/sp_setup_page.dart';
import 'package:gp_app/features/specialist/presentation/sp_home_page.dart';
import 'package:gp_app/features/specialist/presentation/sp_lead_detail_page.dart';
import 'package:gp_app/features/specialist/presentation/sp_profile_page.dart';
import 'package:gp_app/features/specialist/presentation/sp_new_diagnostic_referral_page.dart';
import 'package:gp_app/features/notifications/presentation/notifications_page.dart';
import 'package:gp_app/features/gp/presentation/account_settings_page.dart';
import 'package:gp_app/features/gp/presentation/change_password_page.dart';
import 'package:gp_app/features/gp/presentation/notification_preferences_page.dart';
import 'package:gp_app/features/support/presentation/support_page.dart';
import 'package:gp_app/features/support/presentation/support_ticket_create_page.dart';
import 'package:gp_app/features/support/presentation/support_ticket_detail_page.dart';
import 'package:gp_app/core/config.dart';
import 'package:gp_app/features/policy/presentation/policy_page.dart';

// Pages that are only for unauthenticated users
const _authOnlyPaths = [
  '/gp/login',
  '/gp/otp-login',
  '/gp/otp-verify',
  '/sp/login',
  '/sp/otp-login',
  '/sp/otp-verify',
  '/gp/register',
  '/sp/register',
  '/sp/setup',
  '/role',
  '/splash',
  '/intro',
];

const _publicPaths = [
  '/policy/terms',
  '/policy/privacy',
  '/policy/notification-consent',
  '/policy/refund',
];

/// A ChangeNotifier that notifies the GoRouter when auth state changes.
/// This prevents the GoRouter from being re-created ever-time the state changes.
class RouterNotifier extends ChangeNotifier {
  final Ref _ref;

  RouterNotifier(this._ref) {
    _ref.listen<AuthState>(authStateProvider, (_, __) => notifyListeners());
    _ref.listen<AppRole?>(selectedRoleProvider, (_, __) => notifyListeners());
  }
}

final routerProvider = Provider<GoRouter>((ref) {
  final notifier = RouterNotifier(ref);

  return GoRouter(
    initialLocation: '/splash',
    refreshListenable: notifier,
    debugLogDiagnostics: true,
    routes: [
      GoRoute(
        path: '/splash',
        builder: (context, state) => const SplashPage(),
      ),
      GoRoute(
        path: '/role',
        builder: (context, state) => const RoleSelectionPage(),
      ),
      GoRoute(
        path: '/intro',
        builder: (context, state) => const IntroSplashPage(),
      ),
      GoRoute(
        path: '/notifications',
        builder: (context, state) => const NotificationsPage(),
      ),
      GoRoute(
        path: '/gp/login',
        builder: (context, state) => const GpLoginPage(),
      ),
      GoRoute(
        path: '/gp/otp-login',
        builder: (context, state) => const OtpPhonePage(title: 'GP', roleHint: 'gp'),
      ),
      GoRoute(
        path: '/gp/otp-verify',
        builder: (context, state) {
          final data = state.extra;
          if (data is! OtpFlowData) return const OtpPhonePage(title: 'GP', roleHint: 'gp');
          return OtpVerifyPage(data: data);
        },
      ),
      GoRoute(
        path: '/gp/register',
        builder: (context, state) => const GpRegisterPage(),
      ),
      GoRoute(
        path: '/gp/home',
        builder: (context, state) => const GpHomePage(),
      ),
      GoRoute(
        path: '/gp/specialists',
        builder: (context, state) {
          final specialtyId = state.uri.queryParameters['specialty_id'];
          final locationId = state.uri.queryParameters['location_id'];
          final title = state.uri.queryParameters['title'];
          return GpSpecialistListingPage(
            specialtyId: specialtyId == null ? null : int.tryParse(specialtyId),
            locationId: locationId == null ? null : int.tryParse(locationId),
            title: title,
          );
        },
      ),
      GoRoute(
        path: '/gp/hospitals',
        builder: (context, state) => const GpHospitalListPage(),
      ),
      GoRoute(
        path: '/gp/diagnostic-centers',
        builder: (context, state) => const GpDiagnosticCenterListPage(),
      ),
      GoRoute(
        path: '/gp/categories',
        builder: (context, state) => const GpCategoriesListPage(),
      ),
      GoRoute(
        path: '/gp/referrals',
        builder: (context, state) => const GpReferralListPage(),
      ),
      GoRoute(
        path: '/gp/diagnostics',
        builder: (context, state) => const GpDiagnosticsPage(),
      ),
      GoRoute(
        path: '/gp/referrals/new',
        builder: (context, state) {
          final extra = state.extra as Map<String, dynamic>?;
          final specialistId = extra?['specialist_id']?.toString();
          final locationId = extra?['location_id'] as int?;
          final categoryCode = extra?['category_code']?.toString();
          final areaName = extra?['area_name']?.toString();
          final speciality = extra?['speciality']?.toString();
          final referralTypeRaw = (state.uri.queryParameters['type'] ?? extra?['referral_type'])?.toString().toLowerCase();
          final initialReferralType = switch (referralTypeRaw) {
            'diagnostic' => ReferralType.diagnostic,
            'specialist' => ReferralType.specialist,
            'hospital' => ReferralType.hospital,
            _ => null,
          };
          
          return GpNewReferralPage(
            preselectedSpecialistId: specialistId,
            preselectedLocationId: locationId,
            preselectedCategoryCode: categoryCode,
            preselectedAreaName: areaName,
            preselectedCategoryName: speciality,
            initialReferralType: initialReferralType,
          );
        },
      ),
      GoRoute(
        path: '/gp/referrals/:id',
        builder: (context, state) {
          final type = state.uri.queryParameters['type'];
          return GpReferralDetailPage(
            referralId: state.pathParameters['id']!,
            referralType: type,
          );
        },
      ),
      GoRoute(
        path: '/gp/profile',
        builder: (context, state) => const GpProfilePage(),
      ),
      GoRoute(
        path: '/sp/login',
        builder: (context, state) => const SpLoginPage(),
      ),
      GoRoute(
        path: '/sp/otp-login',
        builder: (context, state) => const OtpPhonePage(title: 'Specialist', roleHint: 'specialist'),
      ),
      GoRoute(
        path: '/sp/otp-verify',
        builder: (context, state) {
          final data = state.extra;
          if (data is! OtpFlowData) return const OtpPhonePage(title: 'Specialist', roleHint: 'specialist');
          return OtpVerifyPage(data: data);
        },
      ),
      GoRoute(
        path: '/sp/register',
        builder: (context, state) => const SpRegisterPage(),
      ),
      GoRoute(
        path: '/sp/setup',
        builder: (context, state) => const SpSetupPage(),
      ),
      GoRoute(
        path: '/sp/home',
        builder: (context, state) => const SpHomePage(initialIndex: 0),
      ),
      GoRoute(
        path: '/sp/leads',
        builder: (context, state) => const SpHomePage(initialIndex: 1),
      ),
      GoRoute(
        path: '/sp/leads/:id',
        builder: (context, state) => SpLeadDetailPage(leadId: state.pathParameters['id']!),
      ),
      GoRoute(
        path: '/sp/profile',
        builder: (context, state) => const SpProfilePage(),
      ),
      GoRoute(
        path: '/sp/diagnostic-referrals/new',
        builder: (context, state) => const SpNewDiagnosticReferralPage(),
      ),
      GoRoute(
        path: '/sp/hospital-referrals/new',
        builder: (context, state) => const GpNewReferralPage(
          initialReferralType: ReferralType.hospital,
          hospitalOnlyMode: true,
          submitHospitalAsSpecialist: true,
        ),
      ),
      GoRoute(
        path: '/account-settings',
        builder: (context, state) => const AccountSettingsPage(),
      ),
      GoRoute(
        path: '/change-password',
        builder: (context, state) => const ChangePasswordPage(),
      ),
      GoRoute(
        path: '/notification-preferences',
        builder: (context, state) => const NotificationPreferencesPage(),
      ),
      GoRoute(
        path: '/support',
        builder: (context, state) => const SupportPage(),
      ),
      GoRoute(
        path: '/support/new',
        builder: (context, state) => const SupportTicketCreatePage(),
      ),
      GoRoute(
        path: '/support/:id',
        builder: (context, state) => SupportTicketDetailPage(
          ticketId: int.tryParse(state.pathParameters['id'] ?? '') ?? 0,
        ),
      ),
      GoRoute(
        path: '/policy/terms',
        builder: (context, state) => PolicyPage(
          title: 'Terms & Conditions',
          webUrl: '${baseUrl}/terms-conditions',
        ),
      ),
      GoRoute(
        path: '/policy/privacy',
        builder: (context, state) => PolicyPage(
          title: 'Privacy Policy',
          webUrl: '${baseUrl}/privacy-policy',
        ),
      ),
      GoRoute(
        path: '/policy/notification-consent',
        builder: (context, state) => PolicyPage(
          title: 'Notification & Communication Consent',
          webUrl: '${baseUrl}/notification-consent',
        ),
      ),
      GoRoute(
        path: '/policy/refund',
        builder: (context, state) => PolicyPage(
          title: 'Refund & Cancellation Policy',
          webUrl: '${baseUrl}/refund-cancellation-policy',
        ),
      ),
    ],
    redirect: (context, state) {
      final location = state.matchedLocation;
      final authState = ref.read(authStateProvider);

      // While loading/bootstrapping, hold on splash
      if (authState is Loading) {
        if (_authOnlyPaths.any((p) => location.startsWith(p))) return null;
        if (_publicPaths.any((p) => location.startsWith(p))) return null;
        return location == '/splash' ? null : '/splash';
      }

      // Authenticated users
      if (authState is Authenticated) {
        final userRole = authState.user.role;
        final homeRoute = userRole == 'gp' ? '/gp/home' : '/sp/home';

        // If on an auth-only page, send to home
        if (_authOnlyPaths.contains(location)) {
          return homeRoute;
        }

        // Allow public pages
        if (_publicPaths.any((p) => location.startsWith(p))) return null;

        // Prevent GP from accessing SP routes and vice versa
        if (userRole == 'gp' && location.startsWith('/sp/')) return '/gp/home';
        if (userRole == 'specialist' && location.startsWith('/gp/')) return '/sp/home';

        return null;
      }

      // Unauthenticated users
      if (authState is Unauthenticated) {
        if (_authOnlyPaths.any((p) => location.startsWith(p))) return null;
        if (_publicPaths.any((p) => location.startsWith(p))) return null;
        return '/role';
      }

      return null;
    },
  );
});
