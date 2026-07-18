# Debug Session: subscription-off-notifications

Status: OPEN

## Scope
- Verify that when CCAvenue subscription payments are OFF, subscription UI is hidden and registration succeeds without subscription/payment.
- Verify subscription lifecycle notifications for mail, SMS, in-app, WhatsApp, and push-compatible payloads.

## Initial Hypotheses
1. Feature flag gating works in Flutter UI, but one or more registration flows still enforce `subscription_plan_id` on the backend.
2. Public plans API returns empty when payments are OFF, but app state may still render stale subscription options.
3. Subscription notification dispatch is wired for lifecycle events, but database/in-app assertions may fail because settings bootstrap or template defaults are incomplete.
4. WhatsApp/SMS channels are structurally integrated, but delivery may silently skip when notifiable routing or config resolution is missing.
5. Existing automated test failures are caused by test setup drift, not by the subscription/payment logic itself.

## Evidence Plan
- Re-run focused Laravel tests for payment flow and notification flow.
- Run focused Flutter analyzer checks on touched files.
- Execute targeted backend smoke tests for registration when payments are OFF.
- Inspect generated notification records/payloads for subscription lifecycle events.

## Evidence Collected
- `php artisan test --filter=CcaVenuePaymentFlowTest` now passes with 6 tests and 32 assertions.
- Verified `payments OFF` behavior for specialist, hospital, and diagnostic registration paths: registration succeeds, `payment` is `null`, `user.subscription` is `null`, and no `user_subscriptions` row is created.
- Verified public plans API returns `enabled=false` with empty `plans` and `groups` when payments are OFF.
- Verified subscription lifecycle notifications persist database notifications for both `subscription_payment_pending` and `subscription_activated`.
- `flutter analyze` on touched registration and notification preference files returns no issues.

## Findings
1. Hypothesis 1 rejected: backend registration no longer enforces `subscription_plan_id` when payments are OFF in covered flows.
2. Hypothesis 2 rejected for current covered flows: plans API and Flutter gating align with hidden subscription UI when payments are OFF.
3. Hypothesis 3 partially confirmed as a test-only issue: notification assertion was flaky because it sorted by UUID `id`, not by a reliable sequence.
4. Hypothesis 4 not contradicted structurally: SMS/WhatsApp channels are wired, but live delivery still depends on real Twilio configuration.
5. Hypothesis 5 confirmed: the observed failure was test setup/assertion drift, not subscription business logic.
