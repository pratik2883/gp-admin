plugins {
    id("com.android.application")
    id("kotlin-android")
    // The Flutter Gradle Plugin must be applied after the Android and Kotlin Gradle plugins.
    id("dev.flutter.flutter-gradle-plugin")
    id("com.google.gms.google-services")
}

android {
    val isReleaseTask = gradle.startParameter.taskNames.any { it.contains("Release", ignoreCase = true) }
    val keystorePath = System.getenv("ANDROID_KEYSTORE_PATH")
        ?: (project.findProperty("ANDROID_KEYSTORE_PATH") as String?)
    val keystorePassword = System.getenv("ANDROID_KEYSTORE_PASSWORD")
        ?: (project.findProperty("ANDROID_KEYSTORE_PASSWORD") as String?)
    val keyAlias = System.getenv("ANDROID_KEY_ALIAS")
        ?: (project.findProperty("ANDROID_KEY_ALIAS") as String?)
    val keyPassword = System.getenv("ANDROID_KEY_PASSWORD")
        ?: (project.findProperty("ANDROID_KEY_PASSWORD") as String?)

    if (isReleaseTask && (keystorePath.isNullOrBlank() || keystorePassword.isNullOrBlank() || keyAlias.isNullOrBlank() || keyPassword.isNullOrBlank())) {
        throw GradleException(
            "Missing Android release signing config. Set ANDROID_KEYSTORE_PATH, ANDROID_KEYSTORE_PASSWORD, ANDROID_KEY_ALIAS, ANDROID_KEY_PASSWORD (env vars or Gradle properties)."
        )
    }

    namespace = "gp.specialit.com"
    compileSdk = flutter.compileSdkVersion
    ndkVersion = flutter.ndkVersion

    compileOptions {
        isCoreLibraryDesugaringEnabled = true
        sourceCompatibility = JavaVersion.VERSION_11
        targetCompatibility = JavaVersion.VERSION_11
    }

    kotlinOptions {
        jvmTarget = JavaVersion.VERSION_11.toString()
    }

    defaultConfig {
        // TODO: Specify your own unique Application ID (https://developer.android.com/studio/build/application-id.html).
        applicationId = "gp.specialit.com"
        // You can update the following values to match your application needs.
        // For more information, see: https://flutter.dev/to/review-gradle-config.
        minSdk = flutter.minSdkVersion
        targetSdk = flutter.targetSdkVersion
        versionCode = flutter.versionCode
        versionName = flutter.versionName
    }

    signingConfigs {
        create("release") {
            if (!keystorePath.isNullOrBlank()) {
                storeFile = file(keystorePath)
            }
            if (!keystorePassword.isNullOrBlank()) {
                storePassword = keystorePassword
            }
            if (!keyAlias.isNullOrBlank()) {
                this.keyAlias = keyAlias
            }
            if (!keyPassword.isNullOrBlank()) {
                this.keyPassword = keyPassword
            }
        }
    }

    buildTypes {
        release {
            signingConfig = signingConfigs.getByName("release")
        }
    }
}

flutter {
    source = "../.."
}

dependencies {
    coreLibraryDesugaring("com.android.tools:desugar_jdk_libs:2.0.4")
}
