<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markRead(Request $request, string $notificationId): RedirectResponse
    {
        $notification = $request->user()
            ?->notifications()
            ->where('id', $notificationId)
            ->firstOrFail();

        if ($notification && is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()?->unreadNotifications()->update([
            'read_at' => now(),
        ]);

        return back();
    }
}
