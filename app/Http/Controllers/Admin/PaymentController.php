<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View
    {
        $perPage = (int) request('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50], true) ? $perPage : 10;

        $payments = Payment::query()
            ->with(['order.customer', 'customer'])
            ->withSum('allocations as allocated_amount', 'amount')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $paidPayments = Payment::query()->where('status', 'paid');
        $paidTotal = (float) $paidPayments->sum('amount');
        $allocatedTotal = (float) DB::table('payment_allocations')->sum('amount');
        $unallocatedTotal = max(0, $paidTotal - $allocatedTotal);

        $tableStats = [
            [
                'value' => number_format(Payment::query()->where('status', 'pending')->count()),
                'label' => __('Pending Payments'),
                'icon' => 'bx-time-five',
            ],
            [
                'value' => number_format(Payment::query()->where('status', 'paid')->count()),
                'label' => __('Completed Payments'),
                'icon' => 'bx-check-double',
            ],
            [
                'value' => number_format(Payment::query()->where('status', 'failed')->count()),
                'label' => __('Failed Payments'),
                'icon' => 'bx-error-circle',
            ],
            [
                'value' => money($allocatedTotal),
                'label' => __('Allocated Amount'),
                'icon' => 'bx-spreadsheet',
            ],
            [
                'value' => money($unallocatedTotal),
                'label' => __('Unallocated Credit'),
                'icon' => 'bx-transfer',
            ],
            [
                'value' => money($paidTotal),
                'label' => __('Paid Amount'),
                'icon' => 'bx-wallet',
            ],
        ];

        return view('admin.sales.payments.index', compact('payments', 'tableStats', 'perPage'));
    }
}
