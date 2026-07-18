import 'dart:io';

Future<File> _file() async {
  final sep = Platform.pathSeparator;

  String? base;
  if (Platform.isWindows) {
    base = Platform.environment['LOCALAPPDATA'] ??
        (Platform.environment['USERPROFILE'] != null
            ? '${Platform.environment['USERPROFILE']}${sep}AppData${sep}Local'
            : null) ??
        Platform.environment['APPDATA'];
  } else {
    base = Platform.environment['HOME'] ?? Platform.environment['USERPROFILE'];
  }

  final dirPath = (base == null || base.trim().isEmpty)
      ? Directory.systemTemp.path
      : '$base${sep}gp_app';

  final dir = Directory(dirPath);
  if (!await dir.exists()) {
    await dir.create(recursive: true);
  }
  return File('$dirPath${sep}kv_store.json');
}

Future<String> readStoreFile() async {
  try {
    final file = await _file();
    if (!await file.exists()) return '';
    return await file.readAsString();
  } catch (_) {
    return '';
  }
}

Future<void> writeStoreFile(String content) async {
  try {
    final file = await _file();
    await file.writeAsString(content, flush: true);
  } catch (_) {}
}

