import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:gp_app/app/role.dart';
import 'package:gp_app/ui/styles.dart';
import 'package:flutter/services.dart';

import 'package:gp_app/features/role/presentation/widgets/role_card.dart';

class RoleSelectionPage extends ConsumerWidget {
  const RoleSelectionPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) {
        if (didPop) return;
        SystemNavigator.pop();
      },
      child: Scaffold(
        backgroundColor: AppColors.background,
        body: Column(
          children: [
            // --- Hero Section ---
            Container(
              width: double.infinity,
              padding: const EdgeInsets.fromLTRB(24, 60, 24, 20),
              color: Colors.transparent,
              child: Column(
                children: [
                  Text(
                    'Welcome to Specialist Connect',
                    style: AppStyles.heading1.copyWith(
                      color: AppColors.primaryBlue, 
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                    ),
                    textAlign: TextAlign.center,
                  ),
                ],
              ),
            ),
            
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const SizedBox(height: 12),
                    Text(
                      'Choose Your Role',
                      style: AppStyles.heading2.copyWith(fontSize: 20),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Select how you want to continue with Specialist Connect Pro.',
                      style: AppStyles.bodyMedium.copyWith(color: AppColors.textSecondary),
                    ),
                    const SizedBox(height: 32),
                    
                    GridView.count(
                      crossAxisCount: 2,
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      mainAxisSpacing: 16,
                      crossAxisSpacing: 16,
                      childAspectRatio: 0.8,
                      children: [
                        RoleCard(
                          isGrid: true,
                          title: 'I am a GP',
                          description: 'Refer patients and track care',
                          icon: Icons.medical_services_outlined,
                          onTap: () {
                            ref.read(selectedRoleProvider.notifier).state = AppRole.gp;
                            ref.read(selectedSubtypeProvider.notifier).state = null;
                            context.push('/gp/login');
                          },
                        ),
                        RoleCard(
                          isGrid: true,
                          title: 'I am a Specialist',
                          description: 'Receive and manage referrals',
                          icon: Icons.medical_information_outlined,
                          iconColor: AppColors.secondaryTeal,
                          onTap: () {
                            ref.read(selectedRoleProvider.notifier).state = AppRole.specialist;
                            ref.read(selectedSubtypeProvider.notifier).state = RoleSubtype.individual;
                            context.push('/sp/login');
                          },
                        ),
                        RoleCard(
                          isGrid: true,
                          title: 'I represent a Hospital',
                          description: 'Coordinate specialty services',
                          icon: Icons.local_hospital_outlined,
                          iconColor: AppColors.primaryBlue,
                          onTap: () {
                            ref.read(selectedRoleProvider.notifier).state = AppRole.specialist;
                            ref.read(selectedSubtypeProvider.notifier).state = RoleSubtype.hospital;
                            context.push('/sp/login');
                          },
                        ),
                        RoleCard(
                          isGrid: true,
                          title: 'Diagnostic Center',
                          description: 'Manage tests and diagnostics',
                          icon: Icons.biotech_outlined,
                          iconColor: AppColors.secondaryTeal,
                          onTap: () {
                            ref.read(selectedRoleProvider.notifier).state = AppRole.specialist;
                            ref.read(selectedSubtypeProvider.notifier).state = RoleSubtype.diagnostic;
                            context.push('/sp/login');
                          },
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
