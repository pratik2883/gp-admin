# MR Activity — Admin Panel Plan + Hospital in MR

Status: **built and verified** (see §7 for what shipped).
Stack: Laravel 12.53 / Filament 5.3 / MySQL (SQLite in tests) + Flutter `mr_app`.

---

## 1. Why MR has no "Hospital"

There is no missing button — the two apps model different things.

| | Main doctor app | MR app |
|---|---|---|
| What "type" means | **Who is logged in** (`users.role` + `users.role_subtype`) | **Who the MR visited** (`mr_visits.doctor_type`) |
| Hospital | `role = specialist`, `role_subtype = hospital` — a Specialist account with `hospital_name` | not modelled at all |
| Record | `users` + `specialists` (+ optional `hospitals` directory row) | `users` + `gps` / `specialists` / `diagnostic_centers` |

So "Hospital" in the main app is a **subtype of Specialist**, not a fourth doctor record. MR was built on three visit targets, and that choice is baked into four places:

1. `mr_visits.doctor_type` — DB enum `('gp','specialist','diagnostic_center')`
2. `MrController::logVisit()` — `in:gp,specialist,diagnostic_center`
3. `MrController::onboardDoctor()` — `in:gp,specialist,diagnostic_center` + a 3-branch transaction
4. `mr_app` — `_roles` chips in onboarding, `_doctorTypes` chips in DCR

Also `hospitals` has **no** `onboarded_by_mr_id`, so a hospital directory record cannot even be attributed to an MR today.

### What adding it takes (Phase A)
- **Migration** `..._add_hospital_to_mr_module.php`
  - widen `mr_visits.doctor_type` to include `hospital` (MySQL `MODIFY ... ENUM`, SQLite via `change()` table rebuild — same pattern already used in `2026_10_05_000002_add_mr_role_to_users_table`)
  - `hospitals.onboarded_by_mr_id` (nullable FK → `users`, null on delete)
  - `mr_visits.hospital_id` (nullable FK → `hospitals`) so a visit can point at the real directory record
- **`onboardDoctor()`** — `hospital` branch inside the existing transaction:
  - user: `role = specialist`, `role_subtype = hospital` (exactly what the main app writes)
  - `specialists` row: `hospital_name`, `clinic_*`, council fields, `onboarded_by_mr_id`
  - `hospitals` row: name, type, address, micro area, city, pincode, contact, admin name/designation/mobile/email, `status = pending`, `onboarded_by_mr_id`
  - link the two via `hospital_specialist` pivot (`role = owner`)
- **`logVisit()`** — accept `doctor_type = hospital` + optional `hospital_id`
- **MR app** — 4th chip "Hospital", hospital field set (name, type, bed/size, address, pincode, contact person + designation, email); 4th chip in DCR; icon/colour
- **Admin** — hospital filter in visits + onboarded list

---

## 2. What the admin panel has today

Only `app/Filament/Resources/Mrs` — create / edit / delete MR **accounts** and their targets.

Nothing else about MR exists in the admin panel:
- ❌ no view of `mr_visits` (DCR), `mr_attendances`, `mr_leaves`, `collaterals`
- ❌ `onboarded_by_mr_id` is written by the API but never displayed or editable
- ❌ no approve/reject for leaves (the `approved_by` column exists and is unused)
- ❌ no assignment concept anywhere in schema
- ❌ no admin-side MR API (only the 15 `ensure.mr` routes for the app itself)

---

## 3. Target: one "MR Activity" area

New navigation group **MR Activity**, placed above "User Management".

### B1. MR Activity — hub page (custom Filament page)
`/admin/mr-activity`

- Filters: date range (default: current month), MR (multi-select), territory
- KPI cards: active MRs · checked-in today / total · visits today · visits this month · target achievement % · pending leaves · doctors onboarded this month
- Per-MR rollup table: name, emp code, territory, today's attendance, visits today / target, month visits, onboarded count, last visit date, status
- Row action → **MR detail** (B2)

### B2. MR detail (drill-down)
One page per MR: profile + territory + targets; recent 10 visits; this month's attendance; leaves; doctors onboarded by them; open assignments; reassign / approve actions inline.

### B3. DCR Visits (`MrVisitResource`)
Date / MR / doctor-type filters · selfie thumbnail + full preview · geotag link · purpose · notes · follow-up date · CSV export · admin can edit/delete a wrong entry.

### B4. Attendance (`MrAttendanceResource`)
Date range · check-in / check-out / duration · selfie · map link · late flag vs 09:30 · editable status (`present` / `half_day` / `absent`).

### B5. Leaves (`MrLeaveResource`)
Approve / reject action → writes `status` + `approved_by` + timestamp; pending count badge on the nav item.

### B6. Onboarded Doctors
Union of GP / Specialist / Diagnostic Center / Hospital filtered on `onboarded_by_mr_id`: name, type, mobile, city, onboarded by, onboarded on, status. Filter by MR, type, date. **Reassign** action (change the MR).

### B7. Assignments (new)
`mr_assignments`: `mr_user_id`, `assignable_type`, `assignable_id` (doctor / hospital / centre), or free `lead_name` + `lead_mobile` for a not-yet-registered prospect; `purpose`, `priority`, `due_date`, `status` (`pending` / `in_progress` / `done`), `notes`, `assigned_by`.
- Admin: create / edit / reassign / close
- MR app: new "My Assignments" screen (`GET /api/mr/assignments`, `PATCH /api/mr/assignments/{id}`) with done/in-progress

### B8. Collaterals (`CollateralResource`)
CRUD for the PDF/video/link library the MR app shows, with active toggle + thumbnail.

### B9. Targets
Already editable in the existing MR resource. Optional: month-wise target history — **not in this plan**, only if asked.

---

## 4. Phases

| Phase | Scope | Schema change | Why |
|---|---|---|---|
| **1 — Visibility** | B3, B4, B5 (read + approve), B6 (read), B1 hub | none | Admin can finally see everything MR does. Biggest value, lowest risk. |
| **2 — Control** | B6 reassign, B5 reject w/ note, B8 collaterals, B2 drill-down, B9 target edit | none | Admin can correct and manage. |
| **3 — Assignment** | B7 table + admin resource + `/api/mr/assignments` + MR app screen | 1 migration | Closes the "assign" requirement end-to-end. |
| **A — Hospital** | `hospital` doctor type across DB / API / MR app / admin | 1 migration | Independent of the admin pages; can go first or last. |

Suggested order: **A → 1 → 2 → 3** (A is one migration and unblocks correct visit data; if visits are logged before A, hospital visits can't be recorded at all).

---

## 5. Decisions needed

1. **Hospital**: directory record only (no login), or a full hospital login account like the main app registration, or both?
2. **"Assign"**: assign doctors/leads to an MR for follow-up, only re-attribute who onboarded a doctor, or also move territories between MRs?
3. **Page shape**: hub + drill-down pages in a new "MR Activity" nav group, or literally one page with tabs?

## 6. Verification plan (when built)

- `php artisan test` (full suite) + new feature tests: admin can list/approve/reject leave, reassign onboarded doctor, create assignment; MR cannot see another MR's assignment.
- MR app: `flutter analyze` clean, `flutter test`, new widget tests for the "Hospital" chip and the assignments screen.
- Manual: log a hospital visit end-to-end (MR app → API → admin list) and confirm the hospital directory row + pivot link.

---

## 7. What shipped

### Database
- `2026_10_22_000001_add_hospital_doctor_type_to_mr_module` — widens `mr_visits.doctor_type` to `hospital`, adds `mr_visits.hospital_id`, adds `hospitals.onboarded_by_mr_id`.
- `2026_10_22_000002_create_mr_assignments_table` — `mr_assignments` (MR, morph target or lead, title, purpose, priority, due date, status, notes, MR remarks, assigned_by, completed_at).

### API (`/api/mr/*`, still behind `ensure.mr`)
- `onboard-doctor` accepts `role=hospital`: creates the Specialist account (`role_subtype=hospital`), the `hospitals` directory row (`status=pending`), and the `hospital_specialist` owner link — all in the existing transaction.
- `visits` accepts `doctor_type=hospital` + optional `hospital_id`; the hospital name is copied into `doctor_name` so lists read properly.
- `GET /api/mr/assignments` (open worklist by default, `?status=` for done/all) and `PATCH /api/mr/assignments/{id}` (status + remarks, stamps `completed_at`), scoped to the signed-in MR.

### Admin panel — new `MR Activity` navigation group
| Page | Route | What it does |
|---|---|---|
| MR Activity | `/admin/mr-activity` | Date range, 8 KPI cards, per-MR rollup; `?mr={id}` opens the drill-down (visits, assignments, onboarded doctors, leaves). |
| DCR Visits | `/admin/mr-visits` | Filters by MR, doctor type and date; selfie thumbnail, geotag map link, follow-up, delete. |
| Attendance | `/admin/mr-attendances` | Date range, in/out, hours, late vs 09:30, selfie, status correction. |
| Leaves | `/admin/mr-leaves` | Pending badge, approve / reject (writes `approved_by`). |
| Collaterals | `/admin/mr-collaterals/collaterals` | Full CRUD for the field sales kit. |
| Assignments | `/admin/mr-assignments` | Assign to an MR against a doctor, a hospital, or a free lead; priority, due date, status. |
| Onboarded Doctors | `/admin/onboarded-doctors` | GP / Specialist / Diagnostic / Hospital registrations, filter by MR + type + search, re-assign the credited MR. |

### MR app
- Onboarding and DCR both have a fourth **Hospital** category; the hospital form collects name, type, contact, address, area, city, pincode, state, authorised person and registration number.
- New **My Assignments** screen (`/assignments`, also a home quick action) with Open / Done / All filters, Start and Mark done.

### Admin controls (every MR feature, not just visibility)
`App\Settings\MrSettings` (group `mr`, defaults reproduce the previous hardcoded behaviour) + `/admin/mr-settings`, both enforced in the API:

| Control | Effect |
|---|---|
| MR app enabled | `403` on every `/api/mr/*` request when off |
| Auto-create MR profile | Off → `409` instead of silently minting a profile |
| Shift start / end | Returned in the dashboard + profile payloads; the app shows these, not hardcoded times |
| Check-in reminder popup | Off → the home popup never appears |
| Selfie / GPS mandatory on check-in | `422` without them |
| GPS mandatory on visit | `422` without coordinates |
| Max visits per day | `422` once the cap is hit (0 = unlimited) |
| Default targets | Applied to newly created MR profiles |
| Non-active GP window | Drives the dashboard alert and `/non-active-gps` |
| Expiring subscription window | Drives the dashboard alert and `/expiring-subscriptions` |
| Onboarding permissions (per category) | Rejected with a validation error; the app hides the disabled chips |
| Payment links | `403` when off |
| MR login status (`users.status`) | Blocked accounts get `403` |
| MR profile status (`mrs.status`) | Inactive profiles get `403` |

Admin can also do the MR's work itself: create / correct / delete visits, create / adjust / delete attendance, create / approve / reject / delete leaves, full collateral and assignment CRUD, and re-assign who onboarded a doctor.

### MR app: GPS / maps fix
The geotag buttons never worked on a real device. Root causes and fixes:

| Problem | Fix |
|---|---|
| `AndroidManifest.xml` declared **no** location permission | Added `ACCESS_FINE_LOCATION` + `ACCESS_COARSE_LOCATION` (verified in the merged debug manifest) |
| iOS `Info.plist` had no location usage key, so requesting it crashes the app | Added `NSLocationWhenInUseUsageDescription` |
| A switched-off GPS, `deniedForever` and "no fix" all produced the same silent failure | New `lib/data/location_service.dart` distinguishes them, with a 30 s fix budget and last-known-position fallback |
| No way to leave the app to fix a refusal | Failure snackbar opens the app or system location settings |
| Attendance check-in sent **no GPS and no selfie**, though the popup promises both | Check-in now captures a selfie when the admin requires it and always attaches the fix |
| The tagged pin could not be checked | "Map var bagha" opens `google.com/maps/search/?api=1&query=<lat>,<lng>` from the DCR form and the onboarding card |

The admin dashboard/profile payload now also carries `selfie_required_on_check_in`, `geotag_required_on_check_in` and `geotag_required_on_visit`, so the app enforces exactly what the admin set.

### Checks run
- `php artisan test` → **59 passed (285 assertions)**; `MrAdminActivityTest` covers every page rendering, hospital onboarding/visit, assignment scoping and cross-MR isolation; `MrAdminControlsTest` drives each switch through a real request and saves the settings form through Livewire, then proves the saved switch changes API behaviour.
- `flutter test` → **19 passed**; `flutter analyze` → no errors.
- Migrations applied on local MySQL; `MrSettings` resolves its defaults with no rows present (safe to deploy before anyone opens the settings page); hub/rollup/drill-down queries executed against MySQL.
