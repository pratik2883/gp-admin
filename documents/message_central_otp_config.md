# MessageCentral OTP Configuration Guide (अद्ययावत)

हे दस्तऐवज प्रोजेक्टमध्ये **MessageCentral (VerifyNow)** चा वापर करून OTP send / verify कसा कॉन्फिगर केला आहे, ते पूर्ण तपशील देतो. रेफरन्स: `documents/Message_Central_Verify_Now_API_Doc.pdf`.

> ⚠️ हे प्रोजेक्टच्या **वास्तविक कोडशी** जुळणारी अद्ययावत आवृत्ती आहे (टीप: पूर्वीची आवृत्ती `MessageCentralService.php`, `config/messagecentral.php`, `MessageCentralAuthController`, `routes/auth.php` व `resources/js/otp.js` बद्दल सांगत होती — ते फाइल्स **अस्तित्वात नाहीत**).

---

## 1. OTP फ्लो (Overview)

```
User Phone Enter → POST /api/register/send-otp → MessageCentral SMS पाठवतो (verificationId return)
                                                       │
User OTP Enter → POST /api/register/verify-otp  → MessageCentral validate → User create + Sanctum token
```

- **MessageCentral सक्षम असल्यास** → SMS पाठवला जातो आणि `verificationId` return होतो; verify करताना तोच `verificationId` + user OTP MessageCentral ला validate साठी जातो.
- **MessageCentral बंद असल्यास** → **Local (dev) fallback**: OTP cache मध्ये ठेवला जातो आणि फक्त `local`/`testing` environment मध्ये `otp_debug` return होतो. (उत्पादनात ही स्थिती नको — SMS पाठवणार नाही.)

> **Important:** हे `register/send-otp` व `register/verify-otp` endpoints आहेत (register-with-OTP flow). Mobile app मधील OTP **login** हा या वेगळ्या फ्लोने चालतो — **Firebase Phone Auth** — खाली §7 पहा.

---

## 2. MessageCentral च्या API Endpoints (DOC नुसार)

Base URL: `https://cpaas.messagecentral.com`

| Action | Method | Path |
|---|---|---|
| Token Generation | GET | `/auth/v1/authentication/token` |
| Send OTP | POST | `/verification/v3/send` |
| Validate OTP | GET | `/verification/v3/validateOtp` |

> **authToken header** — प्रत्येक request मध्ये `authToken: <token>` header पाठवावा लागतो.

### 2.1 Token Generation (`/auth/v1/authentication/token`)

| Parameter | Type | Mandatory | Description |
|---|---|---|---|
| `customerId` | String | Yes | Customer ID |
| `key` | String | Yes | Base-64 encrypted password |
| `country` | String | No | OTP पाठवायचा country code (e.g. `91`) |
| `email` | String | No | Email |
| `scope` | String | No | `NEW` (first time) |

### 2.2 Send OTP (`/verification/v3/send`)

| Parameter | Type | Mandatory | Description |
|---|---|---|---|
| `countryCode` | String | Yes | `91` |
| `mobileNumber` | String | Yes | Local 10-digit number (country code शिवाय) |
| `flowType` | String | Yes | `SMS` / `WHATSAPP` / `RCS` |
| `otpLength` | Integer | No | 4 ते 8 (default `4`) |

Response: `data.verificationId` — पुढच्या verify साठी लागतो.

### 2.3 Validate OTP (`/verification/v3/validateOtp`)

| Parameter | Type | Mandatory | Description |
|---|---|---|---|
| `verificationId` | Long | Yes | send API च्या response मधून |
| `code` | String | Yes | User ने टाकलेला OTP |
| `flowType` | String | Yes | `SMS` (भाषेसाठी; default English) |

Success: `data.verificationStatus = "VERIFICATION_COMPLETED"`

### 2.4 Response Codes (Error Mapping)

| Code | Meaning |
|---|---|
| 200 | SUCCESS |
| 400 | BAD_REQUEST |
| 409 | DUPLICATE_RESOURCE |
| 500 | SERVER_ERROR |
| 501 | INVALID_CUSTOMER_ID |
| 505 | INVALID_VERIFICATION_ID |
| 506 | REQUEST_ALREADY_EXISTS |
| 511 | INVALID_COUNTRY_CODE |
| 700 | VERIFICATION_FAILED |
| 702 | WRONG_OTP_PROVIDED |
| 703 | ALREADY_VERIFIED |
| 705 | VERIFICATION_EXPIRED |
| 800 | MAXIMUM_LIMIT_REACHED |

---

## 3. Project Files (OTP संबंधित) — वास्तविक

| File | काम |
|---|---|
| `app/Services/MessageCentralAuthService.php` | Token generation + cache (creds settings वरून) |
| `app/Services/MessageCentralSmsService.php` | `sendOtp()` / `validateOtp()` / `sendSms()` |
| `app/Services/MessageCentralWhatsAppService.php` | WhatsApp templates / chat (notification channel साठी) |
| `app/Http/Controllers/Api/OtpAuthController.php` | Controller — `register/send-otp` व `register/verify-otp` |
| `app/Http/Controllers/Api/AuthController.php` | Firebase-based `login-with-otp` (वेगळा फ्लो) |
| `routes/api.php` | OTP routes (lines 38-39) |
| `app/Settings/NotificationSettings.php` | MessageCentral credentials / toggles (DB settings) |
| `app/Filament/Pages/Settings/NotificationSettings.php` | Admin UI (Filament) — creds entry |
| `app/Notifications/Channels/MessageCentralSmsChannel.php` | Transactional SMS notification channel |
| `config/services.php` | `message_central.base_url` (env) |
| `documents/Message_Central_Verify_Now_API_Doc.pdf` | MessageCentral API reference |

> **नाही आहे:** `config/messagecentral.php`, `app/Services/MessageCentralService.php`, `app/Http/Controllers/Auth/MessageCentralAuthController.php`, `routes/auth.php`, `resources/js/otp.js`, `resources/views/auth/*.blade.php`.

---

## 4. Configuration (Settings + `.env` override)

> ✅ क्रेडेन्शियल्स दोन ठिकाणी असू शकतात:
> 1. **Database settings** (`settings` table, group `notifications`) — Filament Admin → **Settings → Notification** मधून सेट करता येतात (जुनी पद्धत).
> 2. **`.env`** (नवीन) — `MESSAGECENTRAL_*` variables. जेव्हा **`MESSAGECENTRAL_AUTH_TOKEN`** सेट असेल, तेव्हा token-generation **skip** होतो आणि थेट dashboard token वापरला जातो (token generation साठी customerId+password नको).
>
> Resolution priority: DB setting (नॉन-empty असल्यास) → `.env` value → default.

| Settings Key | काम |
|---|---|
| `message_central_sms_enabled` | OTP + SMS send चालू/बंद (DB toggle) |
| `message_central_whatsapp_enabled` | WhatsApp चालू/बंद (DB toggle) |
| `message_central_customer_id` | Customer ID (`C-...`) |
| `message_central_password` | **Raw password** (service मध्ये `base64_encode` होतो → `key` parameter) |
| `message_central_email` | Token API साठी email (optional) |
| `message_central_sms_sender_id` | SMS sender ID |
| `message_central_sms_template_id` | SMS DLT template ID (optional; both ते `template_id`/`entity_id` सोबत पाठवले जातात) |
| `message_central_sms_entity_id` | SMS DLT entity ID (optional) |
| `message_central_whatsapp_sender_id` | WhatsApp sender ID |

`.env` variables (`config/services.php` → `services.message_central.*`):

```env
MESSAGECENTRAL_BASE_URL=https://cpaas.messagecentral.com
MESSAGECENTRAL_CUSTOMER_ID=C-XXXXXXXXXX
MESSAGECENTRAL_PASSWORD=...
# Preferred: direct Auth Token from the Message Central dashboard. When set, token-generation is skipped.
MESSAGECENTRAL_AUTH_TOKEN=eyJ...
MESSAGECENTRAL_COUNTRY=91
MESSAGECENTRAL_OTP_SENDER_ID=PRSHMN
MESSAGECENTRAL_OTP_FLOW_TYPE=SMS
MESSAGECENTRAL_OTP_EXPIRY=5
MESSAGECENTRAL_OTP_RESEND_COOLDOWN=30
MESSAGECENTRAL_OTP_MAX_ATTEMPTS=5
MESSAGECENTRAL_OTP_LENGTH=4
```

> **Enable logic** (`MessageCentralSmsService::isEnabled()`): DB toggle (`message_central_sms_enabled`/`sms_enabled`) **किंवा** `.env` मध्ये `MESSAGECENTRAL_AUTH_TOKEN` / `MESSAGECENTRAL_CUSTOMER_ID` सेट असल्यास OTP flow MessageCentral वापरतो.

### 4.1 Token logic (`MessageCentralAuthService`)

- **Env token short-circuit:** `.env` मध्ये `MESSAGECENTRAL_AUTH_TOKEN` सेट असल्यास तो **थेट** return होतो — token-generation आणि cache दोन्ही skip.
- **Cache key:** `message_central_auth_token`, TTL **840s (14 मिनिटे)**.
- Token cache मध्ये नसल्यास → `customerId + base64(password)` वापरून `GET /auth/v1/authentication/token` call होतो (query params: `customerId`, `key`, `scope=NEW`, `country=91`, `email`). `customerId`/`password`/`email` DB settings मधून (नॉन-empty) किंवा `.env` वरून घेतले जातात.
- Token काढता येत नसल्यास / क्रेडेन्शियल्स नसल्यास → `null` return.
- `forgetToken()` — token cache reset करतो.

---

## 5. `MessageCentralSmsService` — Methods

| Method | काम |
|---|---|
| `sendOtp(string $mobileNumber, int $countryCode = null, int $otpLength = null): ?array` | OTP SMS पाठवतो → response (success: `data.verificationId`); defaults `countryCode`/`otpLength`/`flowType` `.env` वरून |
| `validateOtp(string $verificationId, string $code): ?array` | OTP validate → response (success: `data.verificationStatus = VERIFICATION_COMPLETED`) |
| `sendSms(string $to, string $message, array $context = [])` | Transactional SMS (notification channel साठी) |
| `isEnabled(): bool` | DB toggle किंवा `.env` (`auth_token`/`customer_id`) सेट असल्यास `true` |

- दोन्ही OTP methods आता **error body** पण return करतात (JSON असल्यास) — त्यामुळे controller error code map करू शकतो (702/705/800 इ.).
- `sendSms()` आता **MessageNow SMS API** नुसार `type=SMS`, `messageType=TRANSACTIONAL`, `countryCode`, `mobileNumber`, `senderId`, `message` पाठवतो. Settings मध्ये `templateId`/`entityId` भरल्यास ते ही पाठवले जातात (दोन्ही absent किंवा दोन्ही present — DOC नुसार).
- `sms_enabled` / `message_central_sms_enabled` बंद आणि `.env` credentials नसल्यास `sendOtp()` आणि `sendSms()` `null` return करतात.

**sendOtp request format (DOC नुसार):**
```
POST /verification/v3/send?countryCode=91&mobileNumber=9892711228&flowType=SMS&otpLength=4
Header: authToken: <token>
```

**validateOtp request format (DOC नुसार):**
```
GET /verification/v3/validateOtp?verificationId=xxx&code=1234
Header: authToken: <token>
```

---

## 6. `OtpAuthController` — Flow (`POST /api/register/send-otp` + `verify-otp`)

### `sendOtp(Request)`

1. Validate: `mobile` (required), `name` (required).
2. Phone normalize: `canonicalize()` → `91XXXXXXXXXX` (10-digit → `91` prefix), `localNumber()` → 10-digit local number.
3. `NotificationSettings::message_central_sms_enabled` **असल्यास**:
   - `MessageCentralSmsService::sendOtp(localNumber)` call.
   - `data.verificationId` मिळाल्यास → cache store (`register_otp_{91...}`):
     ```php
     ['mode' => 'messagecentral', 'verification_id' => 'xxx', 'name' => $name]
     ```
     TTL 5 min. Response: `{ message, verification_id }`.
   - verificationId नाही मिळाल्यास → `422` + error `code` (e.g. `800`).
4. **नाही असल्यास** (local fallback): random 6-digit OTP → cache (`mode=local`, TTL 10 min). `local`/`testing` मध्ये `otp_debug` return; अन्यथा `{ message }`.

### `verifyOtp(Request)`

1. Validate: `mobile`, `otp` (`digits_between:4,6`), `password` (min 6).
2. Cache `register_otp_{mobile}` वरून mode बघतो. Cache नसेल → `422 "Invalid or expired OTP."`
3. **`mode = messagecentral`** → `MessageCentralSmsService::validateOtp(verification_id, otp)`:
   - `data.verificationStatus === 'VERIFICATION_COMPLETED'` → पुढे जा.
   - अन्यथा error code map करून `422`:
     - `702` → Invalid OTP
     - `703` → OTP already verified
     - `705` → OTP expired
     - `800` → max attempts reached
     - `505`/`506` → invalid verification
4. **`mode = local`** → cache मधील OTP शी तुलना.
5. यशस्वी → cache forget → `User::firstOrNew(['mobile' => $mobile])` (role `gp` default) → GP/Specialist create (असल्यास नाही) → Sanctum token issue.
6. Response: `{ message, token, token_type, user }`.

### Routes (`routes/api.php`)

```php
POST /api/register/send-otp    → OtpAuthController@sendOtp
POST /api/register/verify-otp  → OtpAuthController@verifyOtp
```

---

## 7. Mobile App OTP **Login** (MessageCentral)

Mobile app (`gp-app/`) मधील OTP login आता **MessageCentral** वरून चालतो (Firebase Phone Auth पूर्वी वापरला जात होता, तो काढून टाकला आहे — `firebase_auth` package `pubspec.yaml` मधून गेला):

1. App → `POST /api/auth/send-otp` (`OtpAuthController@sendLoginOtp`, `mobile` फक्त) → MessageCentral OTP SMS पाठवतो.
   - Success: `{ message, verification_id }` (mode `messagecentral` cache मध्ये).
   - Local fallback (`MESSAGECENTRAL_AUTH_TOKEN`/`customer_id` नसल्यास): local OTP, फक्त `local`/`testing` मध्ये `otp_debug` return.
2. App → `POST /api/auth/login-with-otp` (`AuthController@loginWithOtp`) — आता `firebase_id_token` **optional** आहे; त्याऐवजी `mobile` + `verification_id` + `otp_code` (आणि `role_hint`, `terms_accepted`) पाठवले जातात:
   - `verification_id` आणि cache मधील `login_otp_{mobile}` जुळतो → `MessageCentralSmsService::validateOtp()` कडे जातो.
   - `VERIFICATION_COMPLETED` → user find/create → Sanctum token.
   - Error codes `702/703/705/800/505/506` → स्पष्ट message (दिलेल्या मॅपिंगनुसार).
3. Firebase phone-auth token मिळाल्यास `firebase_id_token` पाठवला तरी चालतो (backward compatible), पण नवीन app फ्लो MessageCentral वापरतो.

### Flutter साइड (OTP login)
- `gp-app/lib/features/auth/data/auth_repository.dart` — `sendLoginOtp()` / `loginWithOtp(mobile, verificationId, otpCode, ...)`.
- `gp-app/lib/features/auth/state/auth_controller.dart` — `loginWithMessageCentralOtp(...)`.
- `gp-app/lib/features/auth/presentation/otp_login_page.dart`, `otp_phone_page.dart`, `otp_verify_page.dart` — Firebase Auth ऐवजी MessageCentral API calls.

---

## 8. Test OTP पाठवणे (Quick Check)

MessageCentral SMS साठी (सेवा direct):

```bash
php artisan tinker --execute="dump(app(App\Services\MessageCentralSmsService::class)->sendOtp('9892711228'));"
```

Success output (DOC नुसार):
```
array:3 [
  "data" => array:1 [
    "verificationId" => "12163012"
  ]
  ...
]
```

Local fallback (settings मध्ये `message_central_sms_enabled` बंद ठेवून):

```bash
curl -X POST http://localhost:8000/api/register/send-otp -H "Accept: application/json" -H "Content-Type: application/json" -d '{"mobile":"9892711228","name":"Test User"}'
# local/testing मध्ये: { "message": "OTP sent successfully", "otp_debug": 123456 }
```

> Phone format: `9892711228` (10-digit) किंवा `919892711228` — दोन्ही चालतात; controller `91` prefix ला canonicalize करतो.

---

## 9. काय लक्षात ठेवावे (Important Notes)

1. **mobileNumber** — नेहमी **10-digit local number**; `countryCode` वेगळा parameter आहे (`localNumber()` मधून `91` prefix काढला जातो).
2. **OTP length** — default `4`, `sendOtp()` मध्ये `otpLength` parameter; controller `digits_between:4,6` validate करतो.
3. **Credentials DB settings मध्ये** आहेत (`settings` table) — `.env` मध्ये **नाही**. बदल करून Filament मधून save केल्यावर लगेच apply होतात.
4. **Token TTL 14 मिनिटे** (840s) — expiry नंतर auto-refresh होतो (`MessageCentralAuthService`).
5. **Local fallback** फक्त `message_central_sms_enabled` बंद असताना — production मध्ये SMS न पाठवता local OTP न देता, MessageCentral सक्षम करा.
6. **Tests** — सध्या OTP-संबंधित समर्पित test फाइल्स नाहीत (`MessageCentralServiceTest` / `ProfileOnboardingTest` अस्तित्वात नाहीत). Existing tests: `tests/Feature/{CcaVenuePaymentFlowTest,RazorpayPaymentFlowTest,SubscriptionPlansTest}.php`.