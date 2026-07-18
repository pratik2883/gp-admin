# Release v0.2.2

## Changes
- Version bumped from 0.2.1+3 → 0.2.2+4
- Subscription plans & payment flow (Razorpay + CCAvenue)
- Registration flow with plan selection & pending subscription
- Mock payment callbacks for testing
- Admin Filament panel: Plans, User Subscriptions, Transactions
- Diagnostic center referral flow
- Support tickets system
- System backups with audit logs
- Notification preferences & Firebase FCM integration
- Deployment ZIPs (core.zip + public.zip) for shared hosting

## Fixed
- Tests updated for `terms_accepted` validation

## Notes
- Requires `php artisan migrate` for new tables
- Configure payment gateway in admin Settings
