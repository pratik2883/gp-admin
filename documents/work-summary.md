# GP‑Specialist Backend & Admin Panel – Work Summary

## Overview
- Laravel 10 app with Sanctum auth, role-based access control, and REST APIs for GP, Specialist, and Admin.
- Admin web panel built with Blade + Bootstrap layout (plug‑and‑play, minimal styling).

## Roles & Auth
- Roles: gp, specialist, hospital_admin, admin (users.role).
- Middleware alias `role` registered in bootstrap/app.php; checks `$request->user()->role`.
- API protected with Sanctum tokens; Admin web protected with ['auth','role:admin'] and session login.

## API Routes
- Auth
  - POST /api/auth/register
  - POST /api/auth/login
  - GET /api/auth/me
  - POST /api/auth/logout
- GP (role: gp)
  - GET /api/gp/profile
  - POST /api/gp/profile
  - GET /api/gp/referrals
  - POST /api/gp/referrals
  - GET /api/gp/referrals/{id}
- Specialist (role: specialist)
  - GET /api/specialist/profile
  - POST /api/specialist/profile
  - GET /api/specialist/referrals
  - GET /api/specialist/referrals/{id}
  - POST /api/specialist/referrals/{id}/accept
  - POST /api/specialist/referrals/{id}/consult
  - POST /api/specialist/referrals/{id}/close
- Admin (role: admin)
  - GET /api/admin/dashboard
  - Specialists: GET /api/admin/specialists, GET /api/admin/specialists/{id}, PUT /api/admin/specialists/{id}, DELETE /api/admin/specialists/{id}
  - Hospitals: GET /api/admin/hospitals, POST /api/admin/hospitals, GET /api/admin/hospitals/{id}, PUT /api/admin/hospitals/{id}, DELETE /api/admin/hospitals/{id}
  - GPs: GET /api/admin/gps, GET /api/admin/gps/{id}, PUT /api/admin/gps/{id}
  - Referrals: GET /api/admin/referrals, GET /api/admin/referrals/{id}

## Controllers (API)
- AuthController: register/login/me/logout
- GpProfileController: show/update GP profile
- GpReferralController: index/store/show; lead_code generation; status defaults to sent
- SpecialistProfileController: show/update; profile photo upload; education arrays; full_name sync to users.name
- SpecialistReferralController: index/show/accept/consult/close with simple state machine (sent → accepted → consulted → closed)
- AdminDashboardController: summary tiles + 30‑day trend (day‑wise counts including zeros)
- AdminSpecialistController, AdminHospitalController, AdminGpController, AdminReferralController: basic listings and updates

## Models & Migrations
- Users table extended with mobile, role enum, status enum.
- Specialist: JSON education fields, clinic info, is_active, relations to hospitals/referrals.
- Hospital: city/status fields; many‑to‑many with specialists (hospital_specialist).
- Gp: basic clinic and preference fields.
- Referral: lead_code, gp_id, specialist_id, hospital_id, appointment_type (opd/ipd), status with accepted_at/consulted_at/closed_at; hasMany ReferralFile.

## Admin Web Panel (Blade)
- Routes (web):
  - GET /admin/login, POST /admin/login, POST /admin/logout
  - GET /admin/dashboard, /admin/specialists, /admin/hospitals, /admin/gps, /admin/referrals
- Controllers (web):
  - Admin\DashboardController@index → summary counts for tiles
  - Admin\SpecialistController@index, Admin\HospitalController@index, Admin\GpController@index, Admin\ReferralController@index → paginated tables
- Views:
  - resources/views/layouts/admin.blade.php
  - resources/views/admin/dashboard.blade.php
  - resources/views/admin/{specialists|hospitals|gps|referrals}.blade.php
  - resources/views/layouts/welcome.blade.php → Admin login (email/mobile + password)

## Seeding & Test Data
- DatabaseSeeder creates:
  - Admin: admin@example.com / mobile: 9000000000 / password: password / role: admin
  - GP user + Gp profile (Mumbai)
  - Two Hospitals (Apollo, Fortis – Mumbai)
  - Specialist user + Specialist profile (Cardiology, Mumbai) and link to Apollo
  - Referrals: SSC‑0001 (opd, sent, 1 day ago), SSC‑0002 (ipd Apollo, accepted, 2 days ago)
- Commands:
  - php artisan migrate:fresh --seed

## Run & Test
- Local run (PHP dev server): php -S 127.0.0.1:8000 -t public
- Admin web:
  - http://127.0.0.1:8000/admin/login → login with admin@example.com / password
  - Redirects to /admin/dashboard
- APIs (with Sanctum token for admin):
  - POST /api/auth/login → token
  - GET /api/admin/dashboard → summary/trend
  - Listings endpoints as above

## Notes / Next
- Add Chart.js to dashboard for 30‑day trend graph.
- Add filters on Blade tables; reuse API endpoints for data.
- Optional: switch to AdminLTE/CoreUI for richer UI components.

