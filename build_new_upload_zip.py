import zipfile, os, sys
from pathlib import Path

project_root = Path(__file__).parent.resolve()
zip_path = project_root / "new_upload.zip"
public_root = project_root / "public"

if zip_path.exists():
    zip_path.unlink()

index_php = r"""<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

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

exclude_prefixes = {
    "node_modules", ".git", "tests",
    "storage/logs", "storage/framework/cache", "storage/framework/sessions",
}

def is_excluded(rel_path):
    for prefix in exclude_prefixes:
        if rel_path.startswith(prefix):
            return True
    if rel_path.startswith("storage/framework/views") and not rel_path.endswith(".gitignore"):
        return True
    return False

def walk_and_add(zf, src_dir, arcname_prefix, check_exclude=False, no_compress=False):
    src_str = str(src_dir)
    count = 0
    for dirpath, dirnames, filenames in os.walk(src_str):
        for fname in filenames:
            fpath = os.path.join(dirpath, fname)
            rel = os.path.relpath(fpath, str(project_root)).replace("\\", "/")
            if check_exclude and is_excluded(rel):
                continue
            sub_rel = os.path.relpath(fpath, src_str).replace("\\", "/")
            arcname = f"{arcname_prefix}/{sub_rel}"
            if no_compress:
                zf.write(fpath, arcname=arcname, compress_type=zipfile.ZIP_STORED)
            else:
                zf.write(fpath, arcname=arcname)
            count += 1
            if count % 5000 == 0:
                print(f"  added {count} files...")
    print(f"  total {count} files from {arcname_prefix}")

print(f"Building {zip_path.name}...")
with zipfile.ZipFile(zip_path, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=1) as zf:
    zf.writestr(".htaccess", htaccess)
    zf.writestr("index.php", index_php)

    # Root-level public assets
    for item in ["build", "css", "js", "fonts", "landing-assets", "uploads", "vendor"]:
        src = public_root / item
        if src.is_dir():
            print(f"Adding {item}/ ...")
            walk_and_add(zf, src, item)
    for item in ["favicon.ico", "robots.txt"]:
        src = project_root / "public" / item
        if src.is_file():
            zf.write(str(src), arcname=item)

    # Core single files
    for item in ["artisan", "composer.json", "composer.lock", ".env.example"]:
        src = project_root / item
        if src.is_file():
            zf.write(str(src), arcname=f"core/{src.name}")

    # Core dirs (excluding vendor)
    for item in ["app", "bootstrap", "config", "database", "resources", "routes", "storage"]:
        src = project_root / item
        if src.is_dir():
            print(f"Adding core/{item}/ ...")
            walk_and_add(zf, src, f"core/{item}", check_exclude=True)

    # Vendor - use STORED for speed
    vendor_src = project_root / "vendor"
    if vendor_src.is_dir():
        print("Adding core/vendor/ (no compression)...")
        walk_and_add(zf, vendor_src, "core/vendor", no_compress=True)

print(f"\nDone! Zip created successfully: {zip_path} ({zip_path.stat().st_size} bytes)")
