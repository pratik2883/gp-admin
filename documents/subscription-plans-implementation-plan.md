# Subscription Plans Implementation Plan

## Objective

Implement subscription plans for:

- Specialist
- Hospital
- Diagnostic Center

And provide:

- Admin `Plans` page
- Registration-time plan selection in Flutter app
- Stored subscription record for each paid user category

## Scope Breakdown

## Phase 0: Foundation Decisions

Before coding, confirm these business rules:

- Specialist plans:
  - `Normal`
  - `Premium`
  - durations: `1`, `5`, `12` months
- Hospital plans:
  - bed-wise
  - slabs: `25`, `50`, `100+`
  - durations: `6`, `12` months
- Diagnostic Center plans:
  - durations: `6`, `12` months
- Registration without plan allowed ki nahi:
  - recommendation: `not allowed`
- Payment gateway now ki later:
  - recommendation: `later`, first manual activation support

## Phase 1: Database

### Task 1.1 - Persist user subtype

Add migration:

- `users.role_subtype` nullable string or enum

Values:

- `specialist`
- `hospital`
- `diagnostic_center`

Backfill guidance:

- diagnostic center users:
  - set `diagnostic_center`
- existing other specialist users:
  - default `specialist`
- hospital-like users:
  - admin review/manual correction

### Task 1.2 - Create `subscription_plans`

Migration fields:

- `name`
- `slug`
- `category`
- `plan_family`
- `bed_slab`
- `duration_months`
- `price`
- `currency`
- `description`
- `feature_points`
- `is_active`
- `sort_order`
- `metadata`

Indexes:

- `category + is_active`
- `category + plan_family`
- `category + bed_slab`

### Task 1.3 - Create `user_subscriptions`

Migration fields:

- `user_id`
- `subscription_plan_id`
- `status`
- `payment_status`
- `starts_at`
- `ends_at`
- snapshots for plan details
- `payment_reference`
- `notes`
- `metadata`

Indexes:

- `user_id + status`
- `subscription_plan_id`
- `ends_at`

### Task 1.4 - Optional `subscription_transactions`

Needed if payment/manual transaction logs pahijet.

## Phase 2: Backend Models and Business Logic

### Task 2.1 - Add models

Create:

- `SubscriptionPlan`
- `UserSubscription`
- optional `SubscriptionTransaction`

Relationships:

- `User hasMany UserSubscription`
- `UserSubscription belongsTo User`
- `UserSubscription belongsTo SubscriptionPlan`

### Task 2.2 - Add subtype-aware registration support

Update:

- `app/Http/Controllers/Api/AuthController.php`
- `app/Http/Controllers/Api/DiagnosticCenterAuthController.php`

Accept new fields:

- `role_subtype`
- `subscription_plan_id`

Validation rules:

- specialist register:
  - `role_subtype` in `specialist`, `hospital`
- diagnostic register:
  - auto set `role_subtype = diagnostic_center`
- selected plan must belong to correct category

### Task 2.3 - Create subscription at registration

Inside registration transaction:

1. create user
2. create profile record
3. create `user_subscriptions` row

Recommended initial status:

- without payment gateway:
  - `pending_activation`
- with payment gateway:
  - `pending_payment`

### Task 2.4 - Premium sync logic

When premium specialist subscription becomes active:

- set `specialists.is_premium = true`

When premium subscription expires/cancels:

- set `specialists.is_premium = false`

Keep:

- `priority_order` admin-managed

## Phase 3: Admin Panel

### Task 3.1 - Create Filament resource: `Plans`

Recommended path:

- `app/Filament/Resources/SubscriptionPlans/SubscriptionPlanResource.php`

Fields:

- category
- plan family
- bed slab
- duration
- price
- active
- sort order
- description
- feature points

Conditional UI:

- Specialist => show `plan_family`
- Hospital => show `bed_slab`
- Diagnostic => hide both

### Task 3.2 - Add filters

Filters:

- category
- plan family
- bed slab
- active/inactive

### Task 3.3 - Optional admin resource: `User Subscriptions`

Useful for:

- view current plan
- activate pending plan
- expire plan
- mark paid

## Phase 4: Public APIs

### Task 4.1 - Public list endpoint

Create:

- `GET /api/public/subscription-plans`

Supports:

- `category=specialist`
- `category=hospital`
- `category=diagnostic_center`

Return grouped data for simple app rendering.

### Task 4.2 - Me/profile subscription summary

Add subscription snapshot in:

- `GET /api/auth/me`
- `GET /api/specialist/profile`
- `GET /api/diagnostic/profile`

Fields:

- active plan name
- duration
- expires at
- premium yes/no
- status

## Phase 5: Flutter App

### Task 5.1 - Add subscription repository/provider

New repository methods:

- fetch public plans by category

Recommended file options:

- add into `providers.dart`
- or create dedicated `subscription_repository.dart`

### Task 5.2 - Specialist registration UI

File:

- `gp-app/lib/features/specialist/presentation/sp_register_page.dart`

Changes:

- if subtype `individual`
  - show `Normal` and `Premium`
  - show 1/5/12 month plan cards
- if subtype `hospital`
  - show bed dropdown
  - show 6/12 month cards filtered by bed slab

Submit payload:

- `role_subtype`
- `subscription_plan_id`

### Task 5.3 - Diagnostic registration UI

File:

- `gp-app/lib/features/diagnostic_center/presentation/dx_register_page.dart`

Changes:

- fetch diagnostic plans
- show:
  - 6 months
  - 12 months
- submit `subscription_plan_id`

### Task 5.4 - Plan summary widget

Reusable widget recommendation:

- selected plan name
- duration
- price
- category info

## Phase 6: Post-Registration Experience

### Option A - Best for phase 1

After registration:

- account created
- show message:
  - `Plan selected successfully. Your account is awaiting activation.`

### Option B - Later

After payment success:

- activate subscription immediately
- navigate to home/setup

## Validation Rules Checklist

- GP la plan flow dakhvaycha nahi
- specialist/hospital/diagnostic la plan compulsory
- inactive plans select karta yenar nahi
- specialist normal/premium mismatch allow karu naye
- hospital bed slab mismatch allow karu naye
- diagnostic la fakta diagnostic plans allow karayche

## Testing Checklist

## Backend

- specialist register with normal plan
- specialist register with premium plan
- hospital register with 25 bed plan
- hospital register with 50 bed plan
- diagnostic register with 6 month plan
- invalid category-plan combination rejects

## Admin

- admin plan create/edit/delete works
- inactive plan app list madhye yet nahi
- filters work

## App

- specialist subtype la normal/premium visible
- hospital subtype la bed dropdown visible
- diagnostic page la 2 simple plans visible
- selected plan submit hoto
- no plan selected asel tar validation yete

## Risks

- Existing `hospital` subtype backend var persist hot naslyamule first migration mandatory
- Existing premium boolean logic subscription-based karaychi asel tar sync job/event lagel
- Payment gateway later add kelyavar schema enough flexible thevavi

## Recommended Implementation Order

1. add `role_subtype`
2. add plan/subscription tables
3. add models
4. add admin `Plans` resource
5. add public plans API
6. update specialist register endpoint
7. update diagnostic register endpoint
8. update Flutter specialist register page
9. update Flutter diagnostic register page
10. add admin subscription monitoring page
11. add payment gateway later

## Suggested Deliverables

### Deliverable A

- Admin can create plans
- App can show plans at registration
- Registration stores selected plan

### Deliverable B

- Admin can activate/cancel user subscriptions
- active premium specialist auto-updates recommendation state

### Deliverable C

- Payment gateway and auto-renew

## Recommendation

For lowest risk:

- first ship `plan master + selection + subscription record + manual admin activation`
- payment integration separate phase madhye kara

He current project architecture sathi safest ahe.
