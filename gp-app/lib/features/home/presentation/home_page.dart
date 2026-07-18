import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:gp_app/app/providers.dart';
import 'package:gp_app/features/auth/state/auth_state.dart';

class HomePage extends ConsumerWidget {
  const HomePage({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(authStateProvider);
    if (state is! Authenticated) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    final u = state.user;
    return Scaffold(
      appBar: AppBar(
        title: const Text('Home'),
        actions: [
          IconButton(
            onPressed: () => ref.read(authStateProvider.notifier).logout(),
            icon: const Icon(Icons.logout),
          ),
        ],
      ),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Name: ${u.name}'),
            Text('Mobile: ${u.mobile}'),
            Text('Role: ${u.role}'),
            Text('GP ID: ${u.gp_id ?? '-'}'),
            Text('Specialist ID: ${u.specialist_id ?? '-'}'),
          ],
        ),
      ),
    );
  }
}
