import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:firebase_auth/firebase_auth.dart' show FirebaseAuth;
import 'package:gp_app/features/auth/data/auth_repository.dart';
import 'package:gp_app/features/auth/models/user.dart';
import 'package:gp_app/features/auth/state/auth_state.dart';
import 'package:gp_app/core/push/fcm_service.dart';
import 'package:gp_app/core/storage/secure_storage.dart';
import 'package:gp_app/features/notifications/data/notification_repository.dart';
import 'package:gp_app/app/role.dart';
import 'package:flutter/foundation.dart';

class AuthController extends StateNotifier<AuthState> {
  final AuthRepository _repo;
  final Ref _ref;
  final AppSecureStorage _storage;

  AuthController(this._repo, this._ref, this._storage)
      : super(const AuthState.unauthenticated());

  Future<void> bootstrap() async {
    debugPrint('AUTH: Starting bootstrap');
    final token = await _repo.readToken();
    if (token == null || token.isEmpty) {
      debugPrint('AUTH: No token found, unauthenticated');
      state = const AuthState.unauthenticated();
      return;
    }
    debugPrint('AUTH: Token found, fetching user...');
    state = const AuthState.loading();
    try {
      final user = await _repo.fetchMe();
      debugPrint('AUTH: User fetched: ${user.name} (${user.role})');
      state = AuthState.authenticated(user);

      // Restore saved role from disk into Riverpod
      final savedRole = await _storage.readRole();
      if (savedRole == 'gp') {
        _ref.read(selectedRoleProvider.notifier).state = AppRole.gp;
      } else if (savedRole == 'specialist') {
        _ref.read(selectedRoleProvider.notifier).state = AppRole.specialist;
      } else {
        // Fallback: derive from the user object itself if storage was lost
        if (user.role == 'gp') {
          _ref.read(selectedRoleProvider.notifier).state = AppRole.gp;
        } else if (user.role == 'specialist') {
          _ref.read(selectedRoleProvider.notifier).state = AppRole.specialist;
        }
      }

      _setupFCM();
    } catch (e, stack) {
      debugPrint('AUTH: Bootstrap error: $e');
      debugPrint('AUTH: Bootstrap stack: $stack');
      await _repo.logout();
      state = const AuthState.unauthenticated();
    }
  }

  Future<String?> login(String mobile, String password) async {
    state = const AuthState.loading();
    try {
      final res = await _repo.login(mobile: mobile, password: password);
      state = AuthState.authenticated(res.user);

      // Persist role to disk
      final userRole = res.user.role;
      await _storage.saveRole(userRole);
      if (userRole == 'gp') {
        _ref.read(selectedRoleProvider.notifier).state = AppRole.gp;
      } else if (userRole == 'specialist') {
        _ref.read(selectedRoleProvider.notifier).state = AppRole.specialist;
      }

      _setupFCM();
      return null;
    } catch (e, stack) {
      debugPrint('AUTH: Login error: $e');
      debugPrint('AUTH: Login stack: $stack');
      state = const AuthState.unauthenticated();
      return e is Exception ? e.toString() : 'Login failed';
    }
  }

  Future<String?> loginWithFirebaseOtp({required String firebaseIdToken, required String roleHint, bool termsAccepted = false}) async {
    state = const AuthState.loading();
    try {
      final res = await _repo.loginWithOtp(firebaseIdToken: firebaseIdToken, roleHint: roleHint, termsAccepted: termsAccepted);
      state = AuthState.authenticated(res.user);

      final userRole = res.user.role;
      await _storage.saveRole(userRole);
      if (userRole == 'gp') {
        _ref.read(selectedRoleProvider.notifier).state = AppRole.gp;
      } else if (userRole == 'specialist') {
        _ref.read(selectedRoleProvider.notifier).state = AppRole.specialist;
      }

      try {
        await FirebaseAuth.instance.signOut();
      } catch (_) {}

      _setupFCM();
      return null;
    } catch (e, stack) {
      debugPrint('AUTH: OTP login error: $e');
      debugPrint('AUTH: OTP login stack: $stack');
      state = const AuthState.unauthenticated();
      return e is Exception ? e.toString() : 'OTP login failed';
    }
  }

  Future<void> _setupFCM() async {
    try {
      final preferences = await _ref.read(notificationRepositoryProvider).fetchPreferences();
      if (!preferences.push) {
        final existingToken = await FirebaseMessaging.instance.getToken();
        if (existingToken != null && existingToken.isNotEmpty) {
          await _ref.read(notificationRepositoryProvider).unregisterDeviceToken(existingToken);
        }
        return;
      }

      await _ref.read(fcmServiceProvider).initialize();
      final token = await FirebaseMessaging.instance.getToken();
      if (token != null && token.isNotEmpty) {
        await _ref.read(notificationRepositoryProvider).registerDeviceToken(token);
      }
    } catch (_) {}
  }

  Future<void> logout() async {
    try {
      final token = await FirebaseMessaging.instance.getToken();
      if (token != null && token.isNotEmpty) {
        await _ref.read(notificationRepositoryProvider).unregisterDeviceToken(token);
      }
      await FirebaseMessaging.instance.deleteToken();
    } catch (_) {}
    // Clear persisted role on explicit logout only
    await _storage.clearRole();
    _ref.read(selectedRoleProvider.notifier).state = null;
    await _repo.logout();
    state = const AuthState.unauthenticated();
  }

  void updateUser(User u) {
    final s = state;
    if (s is Authenticated) {
      state = AuthState.authenticated(u);
    }
  }
}
