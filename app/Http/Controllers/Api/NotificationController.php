<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min(100, $perPage));
        $notifications = $request->user()
            ->notifications()
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return NotificationResource::collection($notifications);
    }

    public function unreadCount(Request $request)
    {
        $count = $request->user()->unreadNotifications()->count();

        return response()->json([
            'count' => $count,
            'unread_count' => $count,
        ]);
    }

    public function markRead(Request $request, string $id)
    {
        $notification = DatabaseNotification::where('notifiable_id', $request->user()->id)
            ->where('notifiable_type', get_class($request->user()))
            ->findOrFail($id);

        if ($notification->read_at) {
            return response()->json(['status' => 'ok']);
        }

        $notification->markAsRead();

        return response()->json(['status' => 'ok']);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['status' => 'ok']);
    }
}
