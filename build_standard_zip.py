import zipfile, os
from pathlib import Path

project_root = Path(__file__).parent.resolve()
zip_path = project_root / "new_upload.zip"

if zip_path.exists():
    zip_path.unlink()

include_dirs = [
    "app",
    "bootstrap",
    "config",
    "database",
    "pages",
    "public",
    "resources",
    "routes",
    "storage",
]

include_files = [
    ".env",
    ".env.example",
    ".editorconfig",
    ".gitattributes",
    "artisan",
    "composer.json",
    "composer.lock",
    "package.json",
    "phpunit.xml",
    "vite.config.js",
    "server_upload.md",
    "gp-specialist-firebase-adminsdk-fbsvc-190ebe2c83.json",
    "specialistconnectpro-firebase-adminsdk-fbsvc-4cf4b0be47.json"
]

exclude_prefixes = (
    "storage/logs/",
    "storage/framework/cache/",
    "storage/framework/sessions/",
)

def is_excluded(rel_path):
    for prefix in exclude_prefixes:
        if rel_path.startswith(prefix):
            return True
    if rel_path.startswith("storage/framework/views/") and not rel_path.endswith(".gitignore"):
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

print(f"Building standard {zip_path.name}...")
with zipfile.ZipFile(zip_path, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=1) as zf:
    # Single root files
    for item in include_files:
        src = project_root / item
        if src.is_file():
            zf.write(str(src), arcname=item)

    # Core dirs
    for item in include_dirs:
        src = project_root / item
        if src.is_dir():
            print(f"Adding {item}/ ...")
            walk_and_add(zf, src, item, check_exclude=(item == "storage"))

    # Vendor - use STORED for speed
    vendor_src = project_root / "vendor"
    if vendor_src.is_dir():
        print("Adding vendor/ (no compression)...")
        walk_and_add(zf, vendor_src, "vendor", no_compress=True)

print(f"\nDone! Zip created successfully: {zip_path} ({zip_path.stat().st_size} bytes)")
