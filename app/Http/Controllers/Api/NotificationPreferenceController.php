<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function show(Request $request)
    {
        return response()->json([
            'preferences' => $request->user()->resolvedNotificationPreferences(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'push' => ['required', 'boolean'],
            'email' => ['required', 'boolean'],
            'sms' => ['required', 'boolean'],
            'whatsapp' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $user->notification_preferences = [
            'push' => (bool) $data['push'],
            'email' => (bool) $data['email'],
            'sms' => (bool) $data['sms'],
            'whatsapp' => (bool) $data['whatsapp'],
        ];
        $user->save();

        return response()->json([
            'message' => 'Notification preferences updated.',
            'preferences' => $user->resolvedNotificationPreferences(),
        ]);
    }
}
