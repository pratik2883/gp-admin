# Subscription Plans R&D

## Goal

User requirement:

- Subscription plans only for `Specialist`, `Hospital`, and `Diagnostic Center`
- Specialist la 2 plan families pahijet:
  - `Normal`
  - `Premium`
- Specialist sathi donhi family madhye durations:
  - `1 month`
  - `5 months`
  - `12 months`
- Hospital sathi plans `beds` wise pahijet:
  - `25 beds`
  - `50 beds`
  - `100+ beds`
- Hospital price duration-wise change honar:
  - example: `25 beds = Rs 900 for 6 months`
  - example: `25 beds = Rs 1500 for 12 months`
- Diagnostic Center sathi simple plans:
  - `6 months`
  - `12 months`
- Admin panel madhye new page `Plans`
- Flutter app registration veles category nusar plans disle pahijet

## Deep Scan Findings

### 1. Existing admin/backend state

- Current codebase madhye actual `plans`, `subscriptions`, `payments`, `renewals`, `invoices`, `plan history` sathi ekahi dedicated table/model/controller nahi.
- Admin panel madhye `Plans` sarkha resource/page nahi.
- Existing paid-like logic fakta `Specialist` var `is_premium` + `priority_order` itkach ahe.
- He current premium logic recommendation sorting sathi use hote; billing/subscription lifecycle sathi nahi.

Relevant files:

- `app/Models/Specialist.php`
- `app/Filament/Resources/Specialists/SpecialistResource.php`
- `database/migrations/2026_04_06_000001_add_recommendation_fields_to_specialists_and_gps_table.php`

### 2. Existing app registration state

- `Specialist` ani `Hospital` registration same Flutter page vaparte:
  - `gp-app/lib/features/specialist/presentation/sp_register_page.dart`
- Backend var donhi same endpoint vapartat:
  - `POST /api/specialist/register`
- `Diagnostic Center` registration separate ahe:
  - Flutter: `gp-app/lib/features/diagnostic_center/presentation/dx_register_page.dart`
  - API: `POST /api/diagnostic/register`

### 3. Important architecture constraint

- App madhye subtype selection ahe:
  - `individual`
  - `hospital`
  - `diagnostic`
- Pan ha subtype aaj backend/database madhye durable form madhye save hot nahi for specialist/hospital flow.
- Role selection file:
  - `gp-app/lib/app/role.dart`
  - `gp-app/lib/features/role/presentation/role_selection_page.dart`

Meaning:

- User `Specialist` ahe ki `Hospital` ahe he aaj registration nantar backend reliably persist होत nahi.
- `Diagnostic Center` case madhye `diagnostic_centers` row mule infer karta yeto.
- `Hospital` case sathi future plan lookup/payment renewal sathi explicit subtype field lagel.

### 4. User roles actual backend var kase ahet

- `Specialist`, `Hospital`, `Diagnostic Center` ya sarv app-side variants practically `users.role = specialist` var based ahet.
- `Diagnostic Center` auth sathi extra `diagnostic_centers` record create hoto.
- `Hospital` la separate mobile-auth role/subtype storage nahi.

Relevant files:

- `app/Models/User.php`
- `app/Http/Controllers/Api/AuthController.php`
- `app/Http/Controllers/Api/DiagnosticCenterAuthController.php`
- `app/Http/Middleware/EnsureSpecialist.php`
- `app/Http/Middleware/EnsureDiagnosticCenter.php`

### 5. Payment gateway state

- Stripe / Razorpay / payment gateway integration current codebase madhye nahi.
- Mhanje phase-1 design karta na `plan selection + subscription record + manual activation` ha safe route ahe.

## Product Design Recommendation

## Recommended categories

Use 3 explicit categories:

- `specialist`
- `hospital`
- `diagnostic_center`

## Recommended plan dimensions

### Specialist

- `plan_family`
  - `normal`
  - `premium`
- `duration_months`
  - `1`
  - `5`
  - `12`

Examples:

- Specialist Normal - 1 Month
- Specialist Normal - 5 Months
- Specialist Normal - 12 Months
- Specialist Premium - 1 Month
- Specialist Premium - 5 Months
- Specialist Premium - 12 Months

### Hospital

- `bed_slab`
  - `upto_25`
  - `upto_50`
  - `above_100`
- `duration_months`
  - `6`
  - `12`

Examples:

- Hospital 25 Beds - 6 Months
- Hospital 25 Beds - 12 Months
- Hospital 50 Beds - 6 Months
- Hospital 50 Beds - 12 Months
- Hospital 100+ Beds - 6 Months
- Hospital 100+ Beds - 12 Months

### Diagnostic Center

- `duration_months`
  - `6`
  - `12`

Examples:

- Diagnostic Center - 6 Months
- Diagnostic Center - 12 Months

## Recommended database design

### Table 1: `subscription_plans`

Purpose:

- Admin-created master plans

Suggested columns:

- `id`
- `name`
- `slug`
- `category` enum:
  - `specialist`
  - `hospital`
  - `diagnostic_center`
- `plan_family` nullable enum:
  - `normal`
  - `premium`
- `bed_slab` nullable enum:
  - `upto_25`
  - `upto_50`
  - `above_100`
- `duration_months` integer
- `price` decimal(10,2)
- `currency` string default `INR`
- `description` text nullable
- `feature_points` json nullable
- `is_active` boolean
- `sort_order` integer nullable
- `metadata` json nullable
- timestamps

Notes:

- Specialist plans sathi `plan_family` required, `bed_slab` null
- Hospital plans sathi `bed_slab` required, `plan_family` null
- Diagnostic sathi donhi null

### Table 2: `user_subscriptions`

Purpose:

- Kon user ne kon plan ghetla, status kay ahe, expiry kadhi ahe

Suggested columns:

- `id`
- `user_id`
- `subscription_plan_id`
- `category_snapshot`
- `plan_name_snapshot`
- `plan_family_snapshot` nullable
- `bed_slab_snapshot` nullable
- `duration_months_snapshot`
- `price_snapshot`
- `currency_snapshot`
- `status` enum:
  - `pending_payment`
  - `pending_activation`
  - `active`
  - `expired`
  - `cancelled`
- `payment_status` enum:
  - `unpaid`
  - `paid`
  - `failed`
  - `refunded`
- `starts_at` nullable
- `ends_at` nullable
- `activated_at` nullable
- `expired_at` nullable
- `cancelled_at` nullable
- `payment_reference` nullable
- `notes` nullable
- `metadata` json nullable
- timestamps

### Table 3: `subscription_transactions` optional but recommended

Purpose:

- Payment gateway ya manual payment audit sathi

Suggested columns:

- `id`
- `user_subscription_id`
- `amount`
- `currency`
- `provider`
- `provider_order_id`
- `provider_payment_id`
- `status`
- `payload` json nullable
- timestamps

## Critical backend change required before plans

### Persist account subtype

Current issue:

- `Hospital` ani `Specialist` app-side vegle ahet, pan backend var same specialist registration route use karto.
- Nantar plan validation, renewal, upgrade, dashboard logic sathi durable subtype lagel.

Recommended solution:

- `users` table madhe `role_subtype` add kara

Suggested values:

- `specialist`
- `hospital`
- `diagnostic_center`

Why on `users`:

- login nantar immediate account category available hoil
- app redirect and plan fetch easy hoil
- subscription logic role + subtype var based karta yeil

Alternative:

- `specialists.account_type`

But primary recommendation:

- `users.role_subtype`

## How premium should work

Current state:

- Admin manually `is_premium` toggle karto in specialist resource

Recommended future state:

- `Premium` specialist plan active asel tar:
  - `specialists.is_premium = true`
- Plan expire/downgrade zala tar:
  - `specialists.is_premium = false`

Short-term practical approach:

- `is_premium` field keep kara
- subscription activation/deactivation veles sync kara
- `priority_order` admin-controlled theva

## Admin panel design

## New Filament resource: `Plans`

Admin page fields:

- `Category`
  - Specialist
  - Hospital
  - Diagnostic Center
- `Plan Family`
  - Normal
  - Premium
- `Bed Slab`
  - 25 Beds
  - 50 Beds
  - 100+ Beds
- `Duration Months`
- `Price`
- `Currency`
- `Description`
- `Feature Points`
- `Active`
- `Sort Order`

Behavior rules:

- `Category = Specialist` asel tar `Plan Family` mandatory, `Bed Slab` hidden
- `Category = Hospital` asel tar `Bed Slab` mandatory, `Plan Family` hidden
- `Category = Diagnostic Center` asel tar donhi hidden

## Optional second admin resource: `User Subscriptions`

Admin la he useful honar:

- selected plan baghata yeil
- payment pending subscriptions filter karta yeil
- manual activation/deactivation karta yeil
- expiry dates baghata yeil

Recommended actions:

- `Activate`
- `Expire`
- `Cancel`
- `Mark Paid`

## API design recommendation

### Public plan listing

Endpoint:

- `GET /api/public/subscription-plans`

Query params:

- `category=specialist|hospital|diagnostic_center`
- `role_subtype=` optional if needed

Suggested response:

- Specialist sathi grouped by `normal` / `premium`
- Hospital sathi grouped by `bed_slab`
- Diagnostic sathi simple list

Example response shape:

```json
{
  "category": "specialist",
  "groups": [
    {
      "key": "normal",
      "label": "Normal Plan",
      "plans": [
        {"id": 1, "duration_months": 1, "price": 299, "currency": "INR"},
        {"id": 2, "duration_months": 5, "price": 1299, "currency": "INR"},
        {"id": 3, "duration_months": 12, "price": 2499, "currency": "INR"}
      ]
    },
    {
      "key": "premium",
      "label": "Premium Plan",
      "plans": [
        {"id": 4, "duration_months": 1, "price": 599, "currency": "INR"},
        {"id": 5, "duration_months": 5, "price": 2499, "currency": "INR"},
        {"id": 6, "duration_months": 12, "price": 4999, "currency": "INR"}
      ]
    }
  ]
}
```

### Registration endpoints

Specialist/Hospital:

- `POST /api/specialist/register`

Diagnostic:

- `POST /api/diagnostic/register`

Add fields:

- `role_subtype`
- `subscription_plan_id`

Validation:

- selected plan active asla pahije
- selected plan category current user category shi match zala pahije
- specialist premium/normal only specialist category la allow kara
- hospital bed slab only hospital category la allow kara

### Auth/profile endpoint

Return current subscription summary:

- active plan
- expiry date
- premium or normal
- payment status

## Flutter app design

## Where to show plans

### Specialist registration

File:

- `gp-app/lib/features/specialist/presentation/sp_register_page.dart`

UI flow:

- subtype `individual` asel tar show:
  - `Normal`
  - `Premium`
- family select kelavar duration cards:
  - `1 month`
  - `5 months`
  - `12 months`
- one plan select karne mandatory

### Hospital registration

Same file:

- `gp-app/lib/features/specialist/presentation/sp_register_page.dart`

UI flow:

- subtype `hospital` asel tar first `Bed Category` dropdown
  - `25 beds`
  - `50 beds`
  - `100+ beds`
- mag selected bed slab nusar plans:
  - `6 months`
  - `12 months`

### Diagnostic registration

File:

- `gp-app/lib/features/diagnostic_center/presentation/dx_register_page.dart`

UI flow:

- simple 2 cards:
  - `6 months`
  - `12 months`

## Recommended app UX

- Step order:
  - Basic account info
  - Profile/business info
  - Plan selection
  - Price summary
  - Submit / Proceed to payment

Recommended widgets:

- segmented choice for `Normal / Premium`
- dropdown for hospital `Bed Category`
- cards for plans
- sticky summary card:
  - selected plan name
  - duration
  - price

## Rollout recommendation

## Phase 1

- Admin creates plans
- App fetches plans and lets user choose
- Registration stores `subscription_plan_id`
- Create `user_subscriptions` row with `pending_activation` or `pending_payment`
- Admin manual activation available

## Phase 2

- Payment gateway integration
- payment callback/webhook
- auto activation after payment success
- renewal flow
- expiry reminders

## Risks and edge cases

### 1. Hospital identity ambiguity

- Current backend madhye hospital subtype durable nahi
- He first fix karne mandatory

### 2. Existing users migration

- Existing specialist users na backfill strategy lagel
- Existing diagnostic users infer hotil via `diagnostic_centers.user_id`
- Existing hospital-type specialist users manually classify karावे lagtil

### 3. Premium sync

- Subscription active/premium state ani manual admin `is_premium` conflict yeu shakto
- One source of truth define karne better

### 4. Payment not ready

- Payment gateway nasel tar app copy clear pahije:
  - `Plan selected`
  - `Awaiting activation/payment`

## Final recommendation

Best technical direction:

1. `users.role_subtype` add kara
2. `subscription_plans` table add kara
3. `user_subscriptions` table add kara
4. Filament `Plans` resource create kara
5. Public plan listing API create kara
6. Registration flow madhye `subscription_plan_id` mandatory kara for specialist/hospital/diagnostic
7. Specialist premium plan active asel tar `specialists.is_premium` sync kara

This approach current architecture la least-break risk deil ani future payment integration sathi clean base deil.
