import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/features/auth/state/auth_state.dart';
import 'package:gp_app/core/storage/secure_storage.dart';

class SplashPage extends ConsumerStatefulWidget {
  const SplashPage({super.key});
  @override
  ConsumerState<SplashPage> createState() => _SplashPageState();
}

class _SplashPageState extends ConsumerState<SplashPage>
    with SingleTickerProviderStateMixin {
  late final AnimationController _animCtrl;
  late final Animation<double> _opacity;
  bool _bootstrapped = false;

  @override
  void initState() {
    super.initState();
    _animCtrl = AnimationController(
        vsync: this, duration: const Duration(milliseconds: 1200));
    _opacity = CurvedAnimation(parent: _animCtrl, curve: Curves.easeIn);
    _animCtrl.forward();

    WidgetsBinding.instance.addPostFrameCallback((_) async {
      debugPrint('SPLASH: Calling bootstrap...');
      await ref.read(authStateProvider.notifier).bootstrap();
      debugPrint('SPLASH: Bootstrap finished, calling _navigate...');
      if (!mounted) return;
      _navigate();
    });

    // Safety timeout — never stay on splash beyond 8 seconds
    Future.delayed(const Duration(seconds: 8), () {
      debugPrint('SPLASH: Safety timeout reached');
      if (mounted && !_bootstrapped) {
        debugPrint('SPLASH: Navigating via safety timeout');
        _navigate();
      }
    });
  }

  Future<void> _navigate() async {
    debugPrint('SPLASH: _navigate called');
    _bootstrapped = true;
    final auth = ref.read(authStateProvider);
    debugPrint('SPLASH: Current auth state: $auth');

    // Don't navigate if auth is still resolving — wait for RouterNotifier redirect instead
    if (auth is Loading) return;

    if (auth is Authenticated) {
      final role = auth.user.role;
      if (role == 'gp') {
        context.go('/gp/home');
      } else if (role == 'specialist') {
        context.go('/sp/home');
      } else {
        context.go('/role');
      }
    } else {
      // Check if user has seen the intro splash
      final hasSeenIntro =
          (await AppLocalStorage.instance.getBool('hasSeenIntroSplash')) ??
              false;

      if (!mounted) return;

      if (hasSeenIntro) {
        context.go('/role');
      } else {
        context.go('/intro');
      }
    }
  }

  @override
  void dispose() {
    _animCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      body: Center(
        child: FadeTransition(
          opacity: _opacity,
          child: Image.asset(
            'assets/images/app_logo.png',
            width: 250,
            fit: BoxFit.contain,
          ),
        ),
      ),
    );
  }
}
