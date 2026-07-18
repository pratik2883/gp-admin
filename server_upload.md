We are ready to pack the project for Hostinger deployment. However, prepare the zip file. 

Follow these exact steps:

1. Check all laravel & filament files:
   - Check the Laravel files.
   - Check all related files with filament files.

2. Shared Hosting Environment Checks (.env & AppServiceProvider):
   - Ensure `FILESYSTEM_DISK=public`, `SESSION_DRIVER=file`, and `CACHE_STORE=file` are set in the active `.env`.
   - Ensure `ASSET_URL` points to `specialistconnectpro.com/public`.
   - Ensure `URL::forceScheme('https');` remains active in `AppServiceProvider.php` for production.
   - Ensure `APP_KEY=base64:AKrvMovsUhl7Quq5HhIi54O+FgZgsrZh3mmwNoeYsMM=`
   - Ensure `APP_NAME="GP Admin"`
   - Ensure `APP_DEBUG=false`

3. Final Step - Guide Me to Zip the Project:
   - Once the "Assign Employees" section is fixed and rendering properly, do not change any more code.
   - Tell me exactly which folders (app, config, bootstrap, public, vendor, resources, routes, storage, etc.) and files (.env, artisan, composer.json, root .htaccess) I need to select right now to create a clean 'upload.zip' for Hostinger.