<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeReports();

        $range = $request->string('range')->toString();
        $from = null;
        $to = null;

        if (in_array($range, ['7', '30', '90'], true)) {
            $from = now()->subDays((int) $range)->startOfDay();
            $to = now()->endOfDay();
        } else {
            $from = $request->filled('from')
                ? Carbon::parse($request->input('from'))->startOfDay()
                : null;

            $to = $request->filled('to')
                ? Carbon::parse($request->input('to'))->endOfDay()
                : null;
        }

        $data = $this->reportService->overview($from, $to);

        return view('admin.reports.index', [
            'summary' => $data['summary'],
            'topProducts' => $data['topProducts'],
            'debts' => $data['debts'],
            'leastProducts' => $data['leastProducts'],
            'financial' => $data['financial'],
            'filters' => $request->only(['from', 'to', 'range']),
        ]);
    }

    protected function authorizeReports(): void
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        abort_unless($user && $user->can('reports.view'), 403);
    }
}
