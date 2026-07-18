import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/core/storage/secure_storage.dart';

class IntroSplashPage extends StatefulWidget {
  const IntroSplashPage({super.key});

  @override
  State<IntroSplashPage> createState() => _IntroSplashPageState();
}

class _IntroSplashPageState extends State<IntroSplashPage> {
  @override
  void initState() {
    super.initState();
    _startIntro();
  }

  Future<void> _startIntro() async {
    // Show the intro for 3 seconds
    await Future.delayed(const Duration(seconds: 3));
    
    // Set the flag that the user has seen the intro
    await AppLocalStorage.instance.setBool('hasSeenIntroSplash', true);
    
    if (mounted) {
      // Navigate to the role selection screen
      context.go('/role');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: SizedBox.expand(
        child: Image.asset(
          'assets/images/intro_splash.gif',
          fit: BoxFit.cover,
        ),
      ),
    );
  }
}
