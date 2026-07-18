import zipfile
from pathlib import Path

project_root = Path.cwd()
zip_path = project_root / "upload.zip"
if zip_path.exists():
    zip_path.unlink()

public_root = project_root / "public"

core_items = [
    "app",
    "bootstrap",
    "config",
    "database",
    "resources",
    "routes",
    "storage",
    "vendor",
    "artisan",
    "composer.json",
    "composer.lock",
]

exclude_public = {"index.php", ".htaccess", "hot"}
exclude_core_prefixes = {
    "storage/logs/",
    "storage/framework/views/",
}

htaccess = """<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    RewriteRule ^core/ - [F,L]
    RewriteRule ^\\.env - [F,L]
    RewriteRule ^\\.git - [F,L]
    RewriteRule ^composer\\.(json|lock)$ - [F,L]
    RewriteRule ^artisan$ - [F,L]
    RewriteRule ^phpunit\\.xml$ - [F,L]

    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    RewriteCond %{HTTP:x-xsrf-token} .
    RewriteRule .* - [E=HTTP_X_XSRF_TOKEN:%{HTTP:X-XSRF-Token}]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
"""

index_php = """<?php

use Illuminate\\Foundation\\Application;
use Illuminate\\Http\\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__ . '/core/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__ . '/core/vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__ . '/core/bootstrap/app.php';

if (method_exists($app, 'useEnvironmentPath')) {
    $app->useEnvironmentPath(__DIR__);
}
if (method_exists($app, 'loadEnvironmentFrom')) {
    $app->loadEnvironmentFrom('.env');
}

$app->usePublicPath(__DIR__);
$app->handleRequest(Request::capture());
"""

with zipfile.ZipFile(zip_path, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as zf:
    zf.writestr(".htaccess", htaccess)
    zf.writestr("index.php", index_php)

    if public_root.is_dir():
        for path in public_root.rglob("*"):
            if not path.is_file():
                continue
            rel = path.relative_to(public_root).as_posix()
            if rel in exclude_public:
                continue
            if rel.split("/", 1)[0] in exclude_public:
                continue
            zf.write(path, arcname=rel)

    for item in core_items:
        src = project_root / item
        if src.is_file():
            zf.write(src, arcname=f"core/{src.name}")
            continue
        if not src.is_dir():
            continue
        for path in src.rglob("*"):
            if not path.is_file():
                continue
            rel_from_project = path.relative_to(project_root).as_posix()
            if any(rel_from_project.startswith(prefix) for prefix in exclude_core_prefixes):
                continue
            zf.write(path, arcname=f"core/{rel_from_project}")

print("created", str(zip_path), zip_path.stat().st_size)
