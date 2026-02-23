<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(): View
    {
        $logs = ActivityLog::query()
            ->with('user')
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        $tableStats = [
            [
                'value' => number_format(ActivityLog::query()->count()),
                'label' => __('All Logs'),
                'icon' => 'bx-history',
            ],
            [
                'value' => number_format(ActivityLog::query()->whereDate('created_at', today())->count()),
                'label' => __('Today Logs'),
                'icon' => 'bx-calendar',
            ],
            [
                'value' => number_format(ActivityLog::query()->where('action', 'created')->count()),
                'label' => __('Create Actions'),
                'icon' => 'bx-plus-circle',
            ],
            [
                'value' => number_format((int) ActivityLog::query()->whereNotNull('user_id')->distinct()->count('user_id')),
                'label' => __('Active Users'),
                'icon' => 'bx-user',
            ],
        ];

        return view('admin.security.activity-logs.index', compact('logs', 'tableStats'));
    }
}
