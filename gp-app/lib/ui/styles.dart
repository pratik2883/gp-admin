import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AppColors {
  static const Color primaryBlue = Color(0xFF0B3B8B);
  static const Color secondaryBlue = Color(0xFF0A2F73);
  static const Color secondaryTeal = Color(0xFF08B7A6);
  static const Color accentAqua = Color(0xFF7AE7DC);

  static const Color successGreen = Color(0xFF24B47E);
  static const Color warningYellow = Color(0xFFF5B942);
  static const Color errorRed = Color(0xFFEA4E3D);

  static const Color background = Color(0xFFF6F8FC);
  static const Color surface = Colors.white;
  static const Color surfaceMuted = Color(0xFFF3F6FB);
  static const Color inputBackground = Color(0xFFF7FAFF);

  static const Color textPrimary = Color(0xFF0E1A2B);
  static const Color textSecondary = Color(0xFF56657A);
  static const Color textMuted = Color(0xFF8C98AA);
  static const Color textOnDark = Colors.white;

  static const Color border = Color(0xFFE3E9F4);
  static const Color divider = Color(0xFFEEF2F8);

  static const Color statusPending = warningYellow;
  static const Color statusAccepted = secondaryTeal;
  static const Color statusSuccess = successGreen;
  static const Color statusError = errorRed;

  static const Color cardWhite = surface;
  static const Color textDark = textPrimary;
  static const Color textGrey = textSecondary;
  static const Color textLight = textOnDark;
  static const Color borderLight = border;
  static const Color accentBlue = accentAqua;

  static const LinearGradient heroGradient = LinearGradient(
    colors: [primaryBlue, secondaryBlue, secondaryTeal],
    stops: [0.0, 0.55, 1.0],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient primaryGradient = LinearGradient(
    colors: [primaryBlue, secondaryBlue],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
}

class AppStyles {
  static TextStyle get heading1 => GoogleFonts.inter(
        fontSize: 22,
        fontWeight: FontWeight.w700,
        color: AppColors.textPrimary,
        letterSpacing: -0.3,
      );

  static TextStyle get heading2 => GoogleFonts.inter(
        fontSize: 16,
        fontWeight: FontWeight.w700,
        color: AppColors.textPrimary,
        letterSpacing: -0.2,
      );

  static TextStyle get heading3 => GoogleFonts.inter(
        fontSize: 14,
        fontWeight: FontWeight.w700,
        color: AppColors.textPrimary,
        letterSpacing: -0.1,
      );

  static TextStyle get sectionTitle => GoogleFonts.inter(
        fontSize: 13,
        fontWeight: FontWeight.w700,
        color: AppColors.textPrimary,
        letterSpacing: -0.1,
      );

  static TextStyle get cardTitle => GoogleFonts.inter(
        fontSize: 13,
        fontWeight: FontWeight.w700,
        color: AppColors.textPrimary,
      );

  static TextStyle get bodyLarge => GoogleFonts.inter(
        fontSize: 14,
        fontWeight: FontWeight.w600,
        color: AppColors.textPrimary,
      );

  static TextStyle get bodyMedium => GoogleFonts.inter(
        fontSize: 12,
        fontWeight: FontWeight.w500,
        color: AppColors.textPrimary,
      );

  static TextStyle get bodySmall => GoogleFonts.inter(
        fontSize: 11,
        fontWeight: FontWeight.w500,
        color: AppColors.textSecondary,
      );

  static TextStyle get caption => GoogleFonts.inter(
        fontSize: 10,
        fontWeight: FontWeight.w500,
        color: AppColors.textMuted,
      );

  static TextStyle get chipText => GoogleFonts.inter(
        fontSize: 10,
        fontWeight: FontWeight.w700,
        color: AppColors.textPrimary,
      );

  static TextStyle get buttonText => GoogleFonts.inter(
        fontSize: 13,
        fontWeight: FontWeight.w700,
        color: AppColors.textOnDark,
        letterSpacing: 0.1,
      );

  static TextStyle get labelText => GoogleFonts.inter(
        fontSize: 12,
        fontWeight: FontWeight.w600,
        color: AppColors.textPrimary,
      );

  static BorderRadius radiusCard = BorderRadius.circular(18.0);
  static BorderRadius radiusButton = BorderRadius.circular(14.0);
  static BorderRadius radiusInput = BorderRadius.circular(14.0);
  static BorderRadius radiusPill = BorderRadius.circular(999);

  static List<BoxShadow> cardShadow = [
    const BoxShadow(
      color: Color(0x0A0E1A2B),
      blurRadius: 18,
      offset: Offset(0, 10),
    ),
  ];

  static List<BoxShadow> navShadow = [
    const BoxShadow(
      color: Color(0x140E1A2B),
      blurRadius: 22,
      offset: Offset(0, -10),
    ),
  ];
}

class AppSpacing {
  static const double xxs = 4;
  static const double xs = 8;
  static const double sm = 12;
  static const double md = 16;
  static const double lg = 20;
  static const double xl = 24;
  static const double xxl = 32;

  static const EdgeInsets screenPadding = EdgeInsets.symmetric(horizontal: 16, vertical: 16);
  static const EdgeInsets screenPaddingCompact = EdgeInsets.symmetric(horizontal: 12, vertical: 12);
  static const EdgeInsets screenPaddingLarge = EdgeInsets.symmetric(horizontal: 20, vertical: 20);

  static EdgeInsets responsiveScreenPadding(BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    if (width < 360) return screenPaddingCompact;
    if (width > 420) return screenPaddingLarge;
    return screenPadding;
  }

  static double responsiveHorizontalPadding(BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    if (width < 360) return 12;
    if (width > 420) return 20;
    return 16;
  }

  static double responsiveIconSize(double base, BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    if (width < 360) return base * 0.82;
    if (width > 420) return base * 1.1;
    return base;
  }

  static double responsiveHorizontal(BuildContext context, {double? small, double? medium, double? large}) {
    final width = MediaQuery.sizeOf(context).width;
    if (width < 360) return small ?? 12;
    if (width > 420) return large ?? 20;
    return medium ?? 16;
  }
}

class AppTheme {
  static ThemeData light() {
    final base = ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: AppColors.primaryBlue,
        primary: AppColors.primaryBlue,
        secondary: AppColors.secondaryTeal,
        surface: AppColors.surface,
        error: AppColors.errorRed,
      ),
      scaffoldBackgroundColor: AppColors.background,
      textTheme: GoogleFonts.interTextTheme(),
    );

    return base.copyWith(
      appBarTheme: const AppBarTheme(
        backgroundColor: Colors.transparent,
        foregroundColor: AppColors.textOnDark,
        elevation: 0,
        scrolledUnderElevation: 0,
      ),
      cardTheme: CardThemeData(
        color: AppColors.surface,
        elevation: 0,
        shape: RoundedRectangleBorder(borderRadius: AppStyles.radiusCard),
        margin: EdgeInsets.zero,
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.inputBackground,
        hintStyle: AppStyles.bodySmall.copyWith(color: AppColors.textMuted),
        labelStyle: AppStyles.labelText.copyWith(color: AppColors.textSecondary),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        border: OutlineInputBorder(
          borderRadius: AppStyles.radiusInput,
          borderSide: const BorderSide(color: AppColors.border),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: AppStyles.radiusInput,
          borderSide: const BorderSide(color: AppColors.border),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: AppStyles.radiusInput,
          borderSide: const BorderSide(color: AppColors.secondaryTeal, width: 1.6),
        ),
      ),
      dividerTheme: const DividerThemeData(color: AppColors.divider, thickness: 1),
      chipTheme: base.chipTheme.copyWith(
        shape: RoundedRectangleBorder(borderRadius: AppStyles.radiusPill),
        labelStyle: AppStyles.chipText,
        side: const BorderSide(color: AppColors.border),
        backgroundColor: AppColors.surfaceMuted,
        selectedColor: AppColors.secondaryTeal.withAlpha(24),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: AppColors.primaryBlue,
          foregroundColor: AppColors.textOnDark,
          shape: RoundedRectangleBorder(borderRadius: AppStyles.radiusButton),
          textStyle: AppStyles.buttonText,
          padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.primaryBlue,
          shape: RoundedRectangleBorder(borderRadius: AppStyles.radiusButton),
          side: const BorderSide(color: AppColors.border),
          textStyle: AppStyles.buttonText.copyWith(color: AppColors.primaryBlue),
          padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
        ),
      ),
    );
  }
}

class AppCategoryIcons {
  static const IconData fallback = Icons.medical_services_outlined;

  static IconData fromKey(
    String? iconKey, {
    String? slug,
    String? name,
  }) {
    final raw = (iconKey?.trim().isNotEmpty ?? false)
        ? iconKey!.trim()
        : (slug?.trim().isNotEmpty ?? false)
            ? slug!.trim()
            : (name?.trim().isNotEmpty ?? false)
                ? name!.trim()
                : '';

    final key = raw
        .toLowerCase()
        .replaceAll(RegExp(r'[^a-z0-9]+'), '_')
        .replaceAll(RegExp(r'_+'), '_')
        .replaceAll(RegExp(r'^_+|_+$'), '');

    return switch (key) {
      'cardiology' || 'cardiac' => Icons.favorite_rounded,
      'dermatology' || 'skin' => Icons.face_rounded,
      'orthopedic' || 'orthopedics' || 'ortho' => Icons.accessibility_new_rounded,
      'pediatrics' || 'paediatrics' || 'child' => Icons.child_care_rounded,
      'neurology' || 'neuro' => Icons.psychology_rounded,
      'gynecology' || 'gynaecology' || 'obgyn' => Icons.woman_rounded,
      'ent' => Icons.hearing_rounded,
      'ophthalmology' || 'eye' => Icons.remove_red_eye_rounded,
      'dentist' || 'dental' => Icons.medical_information_rounded,
      'general' || 'general_medicine' => Icons.medication_rounded,
      'surgery' || 'general_surgery' => Icons.healing_rounded,
      'urology' => Icons.water_drop_rounded,
      'psychiatry' => Icons.psychology_alt_rounded,
      'oncology' => Icons.biotech_rounded,
      'radiology' || 'imaging' => Icons.radar_rounded,
      'pathology' || 'lab' || 'laboratory' => Icons.science_rounded,
      _ => fallback,
    };
  }
}
