import 'package:flutter_riverpod/flutter_riverpod.dart';

enum AppRole { gp, specialist }

enum RoleSubtype { individual, hospital, diagnostic }

final selectedRoleProvider = StateProvider<AppRole?>((ref) => null);
final selectedSubtypeProvider = StateProvider<RoleSubtype?>((ref) => null);
