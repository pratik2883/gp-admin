# Notifications push fix — Hostinger deploy runbook (10 Oct 2026)

Commit: `c4754f0` · Patch: [update.zip](update.zip) · Zip sha256: `efd9c818f047871d03ab19f0871f6e7acc1eb4b6e6642fdce96b595d2c2f9924` · 4313 bytes

## काय deploy होतंय (फक्त 3 files, .env किंवा DB structure ला हात नाही)

| Path (Laravel root पासून) | का |
|---|---|
| `app/Services/FcmPushService.php` | FCM v1 payload मधली `icon` चूक दुरुस्त — आता icon `android.notification` खाली; तसेच `INVALID_ARGUMENT` वर आता वैध device tokens delete होत नाहीत |
| `app/Filament/Pages/Dashboard.php` | duplicate "Recent notifications" card काढले |
| `database/migrations/2026_10_09_000001_backfill_filament_format_on_notifications.php` | जुन्या notifications ना `format => filament` backfill (Filament bell आणि list एकच दाखवतील) |

## Server layout (live probes वरून निश्चित)

- `https://specialistconnectpro.com/api/ping` → **200**, assets `https://specialistconnectpro.com/css/filament/...` — म्हणजे app domain root वरून चालतो.
- `https://specialistconnectpro.com/public/index.php` → **200**, `/.env` → **403** — म्हणजे Laravel root मध्ये `public/`, `.env`, `app/`, `database/` आहेत आणि root वरचा `.htaccess` सगळं `public/` मध्ये पाठवतो.
- त्यामुळे **zip मधले paths Laravel root ला relative आहेत** — ज्या folder मध्ये `app/`, `database/`, `public/`, `.env`, `artisan` आहेत त्याच folder मध्ये extract करायचं.

---

## Step 1 — Laravel root शोधा

hPanel → **Files → File Manager** → domain folder उघडा (साधारण `~/domains/specialistconnectpro.com/public_html`).
तपासा: तिथे `app`, `database`, `public`, `.env`, `artisan` दिसतात का. `app/Services/FcmPushService.php` उघडला तर जुनी आवृत्ती दिसेल.

> Path hPanel मध्ये वर दिसतो — तो नक्की कॉपी करा, खाली cron commands साठी लागेल.

## Step 2 — Backup

1. `app/Services/FcmPushService.php` → right-click → **Copy** → तिथेच paste → नाव `FcmPushService.php.bak-20261010`
2. (recommended) phpMyAdmin → `notifications` table → **Export** (तसेच पूर्ण DB export ठेवणं चांगलं)

## Step 3 — Upload + Extract

1. File Manager मध्ये `update.zip` **Upload** करा.
2. `update.zip` वर right-click → **Extract**.
3. **महत्त्वाचं:** "Extract to" field मध्ये पूर्ण root path टाका, उदा.
   `/home/uXXXXXXX/domains/specialistconnectpro.com/public_html`
   नाहीतर Hostinger `update/` नावाचं subfolder तयार करतो आणि फाइल्स चुकीच्या ठिकाणी जातात.
4. Extract झाल्यावर `update.zip` **Delete** करा.

## Step 4 — फाइल्स खरोखर बदलल्या का तपासा

File Manager मध्ये:

- `app/Services/FcmPushService.php` → **8314 bytes** असावी; आत `// FCM v1 has no `icon` field` हा comment दिसावा.
- `app/Filament/Pages/Dashboard.php` → `RecentNotificationsWidget` आता नाही.
- `database/migrations/2026_10_09_000001_backfill_filament_format_on_notifications.php` → नवीन फाइल आली आहे.

---

## Step 5 — Artisan commands शक्य तितक्या सुरक्षित मार्गाने

हा fix फाइल-बदल आहे, त्यामुळे *बहुतेक* प्रकरणी काही command न चालवताही चालेल. पण खालील गोष्टी करणं उत्तम: caches clear, migration चालवणे, आणि **worker restart**.

### Option A — Terminal/SSH असेल तर (सर्वोत्तम)

```bash
cd ~/domains/specialistconnectpro.com/public_html
php artisan optimize:clear
php artisan filament:optimize-clear
php artisan migrate:status --pending
php artisan migrate --force
php artisan queue:restart
```

### Option B — Terminal नसेल तर: एक-वेळाचा Cronjob

hPanel → **Advanced → Cron Jobs** → नवीन job, "Every minute" ठेवा, command:

```bash
/usr/bin/php /home/uXXXXXXX/domains/specialistconnectpro.com/public_html/artisan optimize:clear >> /home/uXXXXXXX/domains/specialistconnectpro.com/public_html/storage/logs/deploy-cron.log 2>&1; /usr/bin/php /home/uXXXXXXX/domains/specialistconnectpro.com/public_html/artisan migrate --force >> /home/uXXXXXXX/domains/specialistconnectpro.com/public_html/storage/logs/deploy-cron.log 2>&1; /usr/bin/php /home/uXXXXXXX/domains/specialistconnectpro.com/public_html/artisan queue:restart >> /home/uXXXXXXX/domains/specialistconnectpro.com/public_html/storage/logs/deploy-cron.log 2>&1
```

एक-दोन मिनिटं थांबा → `storage/logs/deploy-cron.log` बघा → नंतर **हा cron job delete करा**.
(जर Hostinger ने तुमचा PHP 8.3 वेगळ्या path वर ठेवला असेल तर `/usr/bin/php` ऐवजी hPanel दाखवलेला binary वापरा.)

### Option C — deploy-once.php (स्वतःला delete करणारा एक-वेळ स्क्रिप्ट)

`deploy-once.php` (workspace root मध्ये तयार आहे) server वर **`public/`** folder मध्ये upload करा, मग browser मध्ये उघडा:

```
https://specialistconnectpro.com/deploy-once.php?token=5bc0326624c5fedb79cf69e3bb470db5
```

ती स्वतःच caches clear करते, pending migrations चालवते, `queue:restart` करते, patched फाइल्स तपासून output दाखवते आणि **शेवटी स्वतःला delete करते**.
web request मधून चालत असल्याने ती `filament:optimize-clear` वापरत नाही (त्यातला बाजूचा `icons:clear` command web मध्ये register होत नाही, त्यामुळे तो error देतो) — त्याऐवजी आपल्याला लागणारा `filament:clear-cached-components` थेट चालवते. ही स्क्रिप्ट खरोखर चालवून तपासलेली आहे (token चुकीचा → 404, बरोबर → सर्व steps exit 0, शेवटी `[self-deleted]`).
Output मध्ये `[self-deleted]` न आला तर File Manager मधून `deploy-once.php` हाताने delete करा. Token बदलायचा असेल तर फाइलमधला `$expectedToken` बदला.

---

## Step 6 — ⚠️ Queue worker चालू आहे का? (इथेच push अडकतंय)

कोडमध्ये पाहिलं (app/Notifications/*): सर्व notification classes `implements ShouldQueue` आहेत आणि `QUEUE_CONNECTION=database`. म्हणजे **worker चालू नसेल तर jobs table मध्ये रांग लागलेली राहते आणि एकही push/email/SMS पोहोचत नाही** — settings बरोबर असूनही.

hPanel → **Advanced → Cron Jobs** मध्ये खाली दोन entries असाव्यात (नसतील तर तयार करा):

```bash
# 1) queued notifications/emails/SMS पाठवण्यासाठी — दर मिनिटाला
/usr/bin/php /home/uXXXXXXX/domains/specialistconnectpro.com/public_html/artisan queue:work --stop-when-empty --tries=3 --timeout=55 >> /home/uXXXXXXX/domains/specialistconnectpro.com/public_html/storage/logs/queue-cron.log 2>&1
```

```bash
# 2) Laravel scheduler (subscriptions:check-expiry) — दर मिनिटाला
/usr/bin/php /home/uXXXXXXX/domains/specialistconnectpro.com/public_html/artisan schedule:run >> /home/uXXXXXXX/domains/specialistconnectpro.com/public_html/storage/logs/schedule-cron.log 2>&1
```

फाइल बदलल्यानंतर `queue:work` आधीच सतत चालू असेल तर **`php artisan queue:restart` चालवणं बंधनकारक** आहे — नाहीतर जुना FcmPushService जुन्या process च्या memory मध्येच राहतो.

---

## Step 7 — Live verification

### phpMyAdmin मध्ये SQL

```sql
SELECT COUNT(*) AS pending_jobs FROM jobs;              -- वाढतच राहिला तर worker चालू नाही
SELECT id, LEFT(data, 120) FROM notifications ORDER BY id DESC LIMIT 5;   -- "format":"filament" दिसावा
SELECT COUNT(*) AS device_tokens FROM device_tokens;    -- खऱ्या device चा token आहे का
SELECT id, failed_at, LEFT(exception, 160) FROM failed_jobs ORDER BY id DESC LIMIT 5;
```

### खरा test-push

1. खऱ्या मोबाइलवर app login करा (म्हणजे token register होईल).
2. Admin → **Send Notification** → त्या user ला पाठवा.
3. File Manager मध्ये `storage/logs/laravel.log` चा शेवट पाहा:

```
FCM push sent {"user_id":..,"tokens":1,"delivered":1}   ← यश
FCM push delivery failed {"fcm_status":"UNREGISTERED",...}  ← token मेला आहे, app पुन्हा login करून token नवीन मिळवेल
FCM push delivery failed {"fcm_status":"INVALID_ARGUMENT","message":"The registration token is not a valid FCM registration token"}  ← हे payload ठीक असल्याचं लक्षण; फक्त तो token दुसऱ्या (जुन्या) Firebase project चा आहे
FCM push delivery failed {"fcm_status":"INVALID_ARGUMENT","message":"Unknown name \"icon\""}  ← जुनी फाइल अजून live आहे
```

---

## Rollback

1. `FcmPushService.php.bak-20261010` → पुन्हा `FcmPushService.php` करा (जुनी फाइल परत).
2. `php artisan optimize:clear` (किंवा deploy-once.php पुन्हा चालवा).
3. Migration परत करायची असेल: `php artisan migrate:rollback --step=1 --force` — ती फक्त migration ने लिहिलेला `format` key काढते (`duration` असलेल्या payloads ला हात नाही).

## Troubleshooting

| लक्षण | कारण / उपाय |
|---|---|
| Extract नंतर `update/app/...` असं नवीन folder दिसतं | "Extract to" path चुकीचा — तो folder delete करा, path टाकून पुन्हा extract करा |
| Fix केल्यावरही log मध्ये तोच `Unknown name "icon"` | जुनी फाइल अजून आहे, किंवा OPcache जुना bytecode देतोय — deploy-once.php मध्ये `opcache_reset()` चा निकाल बघा; `[self-deleted]` आला नसेल तर फाइल हाताने delete करा |
| Migration "already up to date" पण bell मध्ये जुन्या notifications नाहीत | `notifications` table जास्त मोठा असेल तर backfill मर्यादित वेळात पूर्ण झाला नाही — deploy-cron.log बघा |
| Push पोहोचत नाही पण log मध्ये `delivered:1` | तो notification OTP/स्क्रीन-लॉक मुळे दिसत नाही; डिव्हाइसची notification permission बघा |
| `filament:optimize-clear` वर `There are no commands defined in the "icons" namespace` | हे Terminal ऐवजी web request मधून (deploy-once.php) चालवल्यावर होतं; तरीही आधी `Clearing cached components` पूर्ण होतं, त्यामुळे dashboard चा cache साफ होतो. Terminal असेल तर तिथून चालवा, नाहीतर deploy-once.php वापरा — ते हा error टाळतं. |
| `opcache_reset() : unavailable` | त्या खात्यात OPcache web SAPI मध्ये लोड नाही — काहीच बिघडत नाही; बदल केल्यावरही जुना bytecode दिसत राहिला तर तोच deploy-once.php पुन्हा चालवा किंवा Hostinger support ला PHP restart सांगा. |
