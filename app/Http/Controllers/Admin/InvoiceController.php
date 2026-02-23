<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(): View
    {
        $search = request('q');
        $rawStatus = request('status');
        $status = is_string($rawStatus) && $rawStatus !== ''
            ? Order::normalizeStatus($rawStatus)
            : null;
        $from = request('from');
        $to = request('to');
        $perPage = (int) request('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50], true) ? $perPage : 10;

        $invoices = Order::query()
            ->with([
                'customer',
                'payments',
                'items:id,order_id,qty,price,base_price',
            ])
            ->withSum('paymentAllocations as paid_amount', 'amount')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('order_no', 'like', '%' . $search . '%')
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($from, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $tableStats = [
            [
                'value' => number_format(Order::query()->count()),
                'label' => __('All Invoices'),
                'icon' => 'bx-receipt',
            ],
            [
                'value' => number_format(Order::query()->where('status', Order::STATUS_APPROVED)->count()),
                'label' => __('Approved Invoices'),
                'icon' => 'bx-check-circle',
            ],
            [
                'value' => number_format(Order::query()->where('status', Order::STATUS_PENDING)->count()),
                'label' => __('Pending Invoices'),
                'icon' => 'bx-time-five',
            ],
            [
                'value' => money((float) Order::query()->sum('total')),
                'label' => __('Total Invoiced'),
                'icon' => 'bx-wallet',
            ],
        ];

        return view('admin.invoices.index', compact('invoices', 'search', 'status', 'from', 'to', 'perPage', 'tableStats'));
    }

    public function print(Order $order): View
    {
        $order->load(['customer', 'items.product', 'payments', 'paymentAllocations.payment']);

        $paidPayment = $order->paymentAllocations
            ->sortByDesc(fn ($allocation) => $allocation->allocated_at ?? $allocation->created_at)
            ->map(fn ($allocation) => $allocation->payment)
            ->filter(fn ($payment) => $payment && $payment->status === 'paid')
            ->first()
            ?? $order->payments->firstWhere('status', 'paid');

        return view('admin.invoices.print', compact('order', 'paidPayment'));
    }
}
