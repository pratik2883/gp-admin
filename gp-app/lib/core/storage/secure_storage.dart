import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:gp_app/core/storage/storage_file_backend_stub.dart'
    if (dart.library.io) 'package:gp_app/core/storage/storage_file_backend_io.dart' as storage_file;

class AppLocalStorage {
  static final AppLocalStorage instance = AppLocalStorage._();

  AppLocalStorage._();

  Map<String, dynamic>? _cache;
  Future<void> _writeQueue = Future.value();
  Future<SharedPreferences> get _prefs => SharedPreferences.getInstance();

  bool get _useFile {
    if (kIsWeb) return false;
    return switch (defaultTargetPlatform) {
      TargetPlatform.windows || TargetPlatform.macOS || TargetPlatform.linux => true,
      _ => false,
    };
  }

  Future<void> _ensureLoaded() async {
    if (_cache != null) return;
    if (!_useFile) {
      _cache = <String, dynamic>{};
      return;
    }

    try {
      final raw = await storage_file.readStoreFile();
      if (raw.trim().isEmpty) {
        _cache = <String, dynamic>{};
        return;
      }
      final decoded = jsonDecode(raw);
      if (decoded is Map<String, dynamic>) {
        _cache = decoded;
      } else {
        _cache = <String, dynamic>{};
      }
    } catch (_) {
      _cache = <String, dynamic>{};
    }
  }

  Future<void> _persist() async {
    if (!_useFile) return;
    await _ensureLoaded();
    final data = Map<String, dynamic>.from(_cache ?? const {});

    _writeQueue = _writeQueue.then((_) async {
      try {
        await storage_file.writeStoreFile(jsonEncode(data));
      } catch (_) {}
    });

    await _writeQueue;
  }

  Future<String?> getString(String key) async {
    if (_useFile) {
      await _ensureLoaded();
      final v = _cache?[key];
      return v == null ? null : v.toString();
    }
    final prefs = await _prefs;
    return prefs.getString(key);
  }

  Future<bool?> getBool(String key) async {
    if (_useFile) {
      await _ensureLoaded();
      final v = _cache?[key];
      if (v is bool) return v;
      if (v is num) return v != 0;
      if (v is String) {
        final t = v.trim().toLowerCase();
        if (t == 'true') return true;
        if (t == 'false') return false;
        final n = num.tryParse(t);
        if (n != null) return n != 0;
      }
      return null;
    }
    final prefs = await _prefs;
    return prefs.getBool(key);
  }

  Future<void> setString(String key, String value) async {
    if (_useFile) {
      await _ensureLoaded();
      _cache![key] = value;
      await _persist();
      return;
    }
    final prefs = await _prefs;
    await prefs.setString(key, value);
  }

  Future<void> setBool(String key, bool value) async {
    if (_useFile) {
      await _ensureLoaded();
      _cache![key] = value;
      await _persist();
      return;
    }
    final prefs = await _prefs;
    await prefs.setBool(key, value);
  }

  Future<void> remove(String key) async {
    if (_useFile) {
      await _ensureLoaded();
      _cache!.remove(key);
      await _persist();
      return;
    }
    final prefs = await _prefs;
    await prefs.remove(key);
  }
}

class AppSecureStorage {
  static const _tokenKey = 'auth_token';
  static const _roleKey = 'user_role';
  final FlutterSecureStorage _secureStorage = const FlutterSecureStorage();
  final AppLocalStorage _local = AppLocalStorage.instance;

  bool get _usePrefs {
    if (kIsWeb) return true;
    return switch (defaultTargetPlatform) {
      TargetPlatform.windows || TargetPlatform.macOS || TargetPlatform.linux => true,
      _ => false,
    };
  }

  Future<void> saveToken(String token) async {
    if (_usePrefs) {
      await _local.setString(_tokenKey, token);
      return;
    }
    await _secureStorage.write(key: _tokenKey, value: token);
  }

  Future<String?> readToken() async {
    if (_usePrefs) {
      return _local.getString(_tokenKey);
    }
    return _secureStorage.read(key: _tokenKey);
  }

  Future<void> clearToken() async {
    if (_usePrefs) {
      await _local.remove(_tokenKey);
      return;
    }
    await _secureStorage.delete(key: _tokenKey);
  }

  Future<void> saveRole(String role) async {
    if (_usePrefs) {
      await _local.setString(_roleKey, role);
      return;
    }
    await _secureStorage.write(key: _roleKey, value: role);
  }

  Future<String?> readRole() async {
    if (_usePrefs) {
      return _local.getString(_roleKey);
    }
    return _secureStorage.read(key: _roleKey);
  }

  Future<void> clearRole() async {
    if (_usePrefs) {
      await _local.remove(_roleKey);
      return;
    }
    await _secureStorage.delete(key: _roleKey);
  }
}
