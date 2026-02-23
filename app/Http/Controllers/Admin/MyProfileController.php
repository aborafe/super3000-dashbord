<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\View\View;

class MyProfileController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $user?->loadMissing('roles');

        $activityLogs = $user
            ? ActivityLog::query()
                ->where('user_id', $user->id)
                ->latest('created_at')
                ->limit(8)
                ->get()
            : collect();

        $recentNotifications = $user
            ? $user->notifications()
                ->latest()
                ->limit(8)
                ->get()
            : collect();

        $permissionsCount = $user ? $user->getAllPermissions()->count() : 0;
        $unreadNotificationsCount = $user ? $user->unreadNotifications()->count() : 0;

        return view('admin.myprofile.index', compact(
            'user',
            'activityLogs',
            'recentNotifications',
            'permissionsCount',
            'unreadNotificationsCount'
        ));
    }
}
