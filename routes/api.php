<?php

use App\Http\Controllers\Api\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\GpController as AdminGpController;
use App\Http\Controllers\Api\Admin\SpecialistController as AdminSpecialistController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\DiagnosticCenterAuthController;
use App\Http\Controllers\Api\DiagnosticCenterProfileController;
use App\Http\Controllers\Api\DiagnosticReferralController;
use App\Http\Controllers\Api\GlobalSearchController;
use App\Http\Controllers\Api\GpDashboardController;
use App\Http\Controllers\Api\GpDiagnosticsController;
use App\Http\Controllers\Api\GpProfileController;
use App\Http\Controllers\Api\GpReferralController;
use App\Http\Controllers\Api\MasterDataController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\NotificationPreferenceController;
use App\Http\Controllers\Api\OtpAuthController;
use App\Http\Controllers\Api\SpecialistDiagnosticReferralController;
use App\Http\Controllers\Api\SpecialistHospitalReferralController;
use App\Http\Controllers\Api\SpecialistDiagnosticsController;
use App\Http\Controllers\Api\SpecialistProfileController;
use App\Http\Controllers\Api\SpecialistReferralController;
use App\Http\Controllers\Api\SubscriptionPaymentController;
use App\Http\Controllers\Api\SupportTicketController;
use Illuminate\Support\Facades\Route;

// Public ping route to check API status
Route::get('/ping', function () {
    return response()->json(['message' => 'OK']);
});

// Public login alias (clean mobile login endpoint)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:api');
Route::post('/specialist/register', [AuthController::class, 'specialistRegister'])->middleware('throttle:api');
Route::post('/diagnostic/register', [DiagnosticCenterAuthController::class, 'register'])->middleware('throttle:api');

// OTP sends carry an extra, destination-number-keyed limiter on top of `api`.
Route::post('/register/send-otp', [OtpAuthController::class, 'sendOtp'])->middleware(['throttle:api', 'throttle:otp']);
Route::post('/register/verify-otp', [OtpAuthController::class, 'verifyOtp'])->middleware('throttle:api');

Route::post('/admin/login', [AdminAuthController::class, 'login'])->middleware('throttle:api');

Route::prefix('public')->middleware('throttle:api')->group(function () {
    Route::get('locations', [MasterDataController::class, 'locations']);
    Route::get('specialties', [MasterDataController::class, 'specialties']);
    Route::get('diagnostic-service-types', [MasterDataController::class, 'diagnosticServiceTypes']);
    Route::get('feature-flags', [MasterDataController::class, 'featureFlags']);
    Route::get('subscription-plans', [MasterDataController::class, 'subscriptionPlans']);
    Route::get('subscription-payments/{transactionUuid}', [SubscriptionPaymentController::class, 'publicStatus']);
    Route::get('policies/{type}', [\App\Http\Controllers\Api\PolicyController::class, 'show']);
});

/*
|--------------------------------------------------------------------------
| Auth routes (public)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:api');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:api');
    Route::post('send-otp', [OtpAuthController::class, 'sendLoginOtp'])->middleware(['throttle:api', 'throttle:otp']);
    Route::post('login-with-otp', [AuthController::class, 'loginWithOtp'])->middleware('throttle:api');
    Route::middleware('auth:sanctum')->post('logout', [AuthController::class, 'logout']);
});

/*
|--------------------------------------------------------------------------
| Protected routes (Sanctum)
|--------------------------------------------------------------------------
*/
// `throttle:api` deliberately follows `auth:sanctum` so the limiter can key on
// the authenticated user id instead of the shared client IP.
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('logout-all', [AuthController::class, 'logoutAll']);
    Route::post('subscriptions/{subscriptionId}/payment/initiate', [SubscriptionPaymentController::class, 'initiate']);

    // Debug token route to verify Sanctum bearer auth
    Route::get('debug-token', function (\Illuminate\Http\Request $request) {
        if (! app()->environment(['local', 'testing'])) {
            abort(404);
        }

        return response()->json([
            'auth' => auth()->check(),
            'user' => $request->user(),
        ]);
    });

    // Notifications (common)
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('notifications/{id}/mark-read', [NotificationController::class, 'markRead']);
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
    Route::get('notification-preferences', [NotificationPreferenceController::class, 'show']);
    Route::put('notification-preferences', [NotificationPreferenceController::class, 'update']);

    Route::post('device-tokens', [DeviceTokenController::class, 'store']);
    Route::delete('device-tokens', [DeviceTokenController::class, 'destroy']);
    Route::get('support-tickets', [SupportTicketController::class, 'index']);
    Route::post('support-tickets', [SupportTicketController::class, 'store']);
    Route::get('support-tickets/{ticketId}', [SupportTicketController::class, 'show']);
    Route::post('support-tickets/{ticketId}/reply', [SupportTicketController::class, 'reply']);

    // Global Search
    Route::get('search', [GlobalSearchController::class, 'search'])->middleware('throttle:search');

    // Master data
    Route::get('locations', [MasterDataController::class, 'locations']);
    Route::get('hospitals', [MasterDataController::class, 'hospitals']);
    Route::get('specialties', [MasterDataController::class, 'specialties']);
    Route::get('specialists', [MasterDataController::class, 'specialists']);

    /*
    |--------------------------------------------------------------
    | GP routes
    |--------------------------------------------------------------
    */
    Route::prefix('gp')->middleware('ensure.gp')->group(function () {
        Route::get('/dashboard', [GpDashboardController::class, 'index']);
        Route::get('/dashboard/recommended-specialists', [GpDashboardController::class, 'recommendedSpecialists']);
        Route::get('/dashboard/specialty-categories', [GpDashboardController::class, 'specialtyCategories']);
        Route::get('/profile', [GpProfileController::class, 'show']);
        Route::post('/profile', [GpProfileController::class, 'update']);
        Route::put('/profile', [GpProfileController::class, 'update']);
        Route::post('/profile/change-password', [GpProfileController::class, 'changePassword']);
        Route::get('/diagnostics/locations', [GpDiagnosticsController::class, 'locations']);
        Route::get('/diagnostics/centers', [GpDiagnosticsController::class, 'centers']);
        Route::get('/diagnostics/services', [GpDiagnosticsController::class, 'services']);
        Route::get('/referrals/locations', [GpReferralController::class, 'locations']);
        Route::get('/referrals/categories', [GpReferralController::class, 'categories']);
        Route::get('/referrals/specialists', [GpReferralController::class, 'specialists']);
        Route::get('/referrals/hospital-locations', [GpReferralController::class, 'hospitalLocations']);
        Route::get('/referrals/hospitals', [GpReferralController::class, 'hospitals']);
        Route::get('/referrals/hospitals/{id}/departments', [GpReferralController::class, 'hospitalDepartments']);
        Route::get('/referrals', [GpReferralController::class, 'index']);
        Route::post('/referrals', [GpReferralController::class, 'store']);
        Route::get('/referrals/{id}', [GpReferralController::class, 'show']);
    });

    /*
    |--------------------------------------------------------------
    | Specialist routes
    |--------------------------------------------------------------
    */
    Route::prefix('specialist')->middleware('ensure.specialist')->group(function () {
        Route::get('/profile', [SpecialistProfileController::class, 'show']);
        Route::post('/profile', [SpecialistProfileController::class, 'update']);
        Route::patch('/profile', [SpecialistProfileController::class, 'update']);
        Route::patch('/setup', [SpecialistProfileController::class, 'update']);
        Route::get('/diagnostics/locations', [SpecialistDiagnosticsController::class, 'locations']);
        Route::get('/diagnostics/centers', [SpecialistDiagnosticsController::class, 'centers']);
        Route::get('/diagnostics/services', [SpecialistDiagnosticsController::class, 'services']);
        Route::post('/diagnostic-referrals', [SpecialistDiagnosticReferralController::class, 'store']);
        Route::get('/hospital-referrals/locations', [SpecialistHospitalReferralController::class, 'locations']);
        Route::get('/hospital-referrals/hospitals', [SpecialistHospitalReferralController::class, 'hospitals']);
        Route::get('/hospital-referrals/hospitals/{id}/departments', [SpecialistHospitalReferralController::class, 'hospitalDepartments']);
        Route::post('/hospital-referrals', [SpecialistHospitalReferralController::class, 'store']);
        Route::get('/referrals', [SpecialistReferralController::class, 'index']);
        Route::get('/referrals/{id}', [SpecialistReferralController::class, 'show']);
        Route::post('/referrals/{id}/accept', [SpecialistReferralController::class, 'accept']);
        Route::post('/referrals/{id}/consulted', [SpecialistReferralController::class, 'consult']);
        Route::post('/referrals/{id}/ipd', [SpecialistReferralController::class, 'ipd']);
        Route::post('/referrals/{id}/close', [SpecialistReferralController::class, 'close']);
        Route::get('/leads', [SpecialistReferralController::class, 'index']);
        Route::get('/leads/{id}', [SpecialistReferralController::class, 'show']);
        Route::patch('/leads/{id}/status', [SpecialistReferralController::class, 'status']);
    });

    Route::prefix('diagnostic')->middleware('ensure.diagnostic')->group(function () {
        Route::get('/profile', [DiagnosticCenterProfileController::class, 'show']);
        Route::patch('/profile', [DiagnosticCenterProfileController::class, 'update']);
        Route::post('/profile', [DiagnosticCenterProfileController::class, 'update']);
        Route::get('/referrals', [DiagnosticReferralController::class, 'index']);
        Route::get('/referrals/{id}', [DiagnosticReferralController::class, 'show']);
        Route::patch('/referrals/{id}/status', [DiagnosticReferralController::class, 'status']);
    });

    /*
    |--------------------------------------------------------------
    | Hospital Admin routes
    |--------------------------------------------------------------
    */
    Route::middleware('role:hospital_admin')->prefix('hospital')->group(function () {
        // Route::get('/dashboard', [HospitalDashboardController::class, 'index']);
    });

    /*
    |--------------------------------------------------------------
    | Admin routes (API)
    |--------------------------------------------------------------
    */
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/me', [AdminAuthController::class, 'me']);
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::post('/logout-all', [AdminAuthController::class, 'logoutAll']);
        Route::get('/dashboard/summary', [AdminDashboardController::class, 'summary']);
        Route::get('/gps', [AdminGpController::class, 'index']);
        Route::post('/gps', [AdminGpController::class, 'store']);
        Route::get('/gps/{id}', [AdminGpController::class, 'show']);
        Route::put('/gps/{id}', [AdminGpController::class, 'update']);
        Route::patch('/gps/{id}/status', [AdminGpController::class, 'status']);
        Route::get('/specialists/{id}', [AdminSpecialistController::class, 'show']);
        Route::put('/specialists/{id}', [AdminSpecialistController::class, 'update']);
        Route::patch('/specialists/{id}/status', [AdminSpecialistController::class, 'status']);
    });

    /*
    |--------------------------------------------------------------
    | MR (Medical Representative) routes
    |--------------------------------------------------------------
    */
    Route::prefix('mr')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\MrController::class, 'dashboard']);
        Route::post('/attendance/check-in', [\App\Http\Controllers\Api\MrController::class, 'checkIn']);
        Route::post('/attendance/check-out', [\App\Http\Controllers\Api\MrController::class, 'checkOut']);
        Route::get('/visits', [\App\Http\Controllers\Api\MrController::class, 'getVisits']);
        Route::post('/visits', [\App\Http\Controllers\Api\MrController::class, 'logVisit']);
        Route::post('/onboard-doctor', [\App\Http\Controllers\Api\MrController::class, 'onboardDoctor']);
        Route::get('/non-active-gps', [\App\Http\Controllers\Api\MrController::class, 'nonActiveGps']);
        Route::get('/expiring-subscriptions', [\App\Http\Controllers\Api\MrController::class, 'expiringSubscriptions']);
        Route::post('/payment-link', [\App\Http\Controllers\Api\MrController::class, 'generatePaymentLink']);
        Route::get('/collaterals', [\App\Http\Controllers\Api\MrController::class, 'getCollaterals']);
        Route::post('/leaves', [\App\Http\Controllers\Api\MrController::class, 'applyLeave']);
        Route::get('/leaves', [\App\Http\Controllers\Api\MrController::class, 'getLeaves']);
        Route::get('/calendar', [\App\Http\Controllers\Api\MrController::class, 'calendar']);
        Route::get('/profile', [\App\Http\Controllers\Api\MrController::class, 'getProfile']);
        Route::post('/change-password', [\App\Http\Controllers\Api\MrController::class, 'changePassword']);
    });

});
