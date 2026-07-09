import 'dart:io';

/// Konfigurasi API fleksibel untuk lokal & production.
///
/// Override saat build/run:
/// flutter run --dart-define=API_BASE_URL=http://192.168.1.10/bonusku/public
/// flutter run --dart-define=API_BASE_URL=https://bonusku.example.com
class EnvConfig {
  static const _fromEnv = String.fromEnvironment('API_BASE_URL');

  /// Production: flutter build apk --dart-define=API_BASE_URL=https://bonusku.example.com
  static String get apiBaseUrl {
    if (_fromEnv.isNotEmpty) return _fromEnv;

    if (Platform.isAndroid) {
      // Android emulator → host machine (php artisan serve --host=0.0.0.0 --port=8000)
      return 'http://10.0.2.2:8000';
    }

    // Windows / iOS simulator / desktop
    return 'http://localhost/bonusku/public';
  }

  static String get apiUrl => '$apiBaseUrl/api/v1';

  static const appName = 'BONUSKU';
}
