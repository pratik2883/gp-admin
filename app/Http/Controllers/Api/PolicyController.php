<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class PolicyController extends Controller
{
    public function show(string $type)
    {
        $files = [
            'terms' => 'Terms_Conditions.md',
            'privacy' => 'PrivacyPolicy.md',
            'refund' => 'Refund_Cancellation_Policy.md',
            'disclaimer' => 'Disclaimer.md',
            'delete-account' => 'delete-account.md',
            'contact' => 'Contact_Us.md',
            'notification-consent' => 'Notification_Consent_Policy.md',
        ];

        $file = $files[$type] ?? null;
        if ($file === null) {
            return response()->json(['message' => 'Policy not found.'], 404);
        }

        $path = base_path('pages/'.$file);
        if (! is_file($path)) {
            return response()->json(['message' => 'Policy content not found.'], 404);
        }

        $content = file_get_contents($path);

        return response()->json([
            'type' => $type,
            'title' => match ($type) {
                'terms' => 'Terms & Conditions',
                'privacy' => 'Privacy Policy',
                'refund' => 'Refund & Cancellation Policy',
                'disclaimer' => 'Disclaimer',
                'delete-account' => 'Delete Account',
                'contact' => 'Contact Us',
                'notification-consent' => 'Notification & Communication Consent',
                default => $type,
            },
            'content' => $content,
            'updated_at' => date('Y-m-d', filemtime($path)),
        ]);
    }
}
