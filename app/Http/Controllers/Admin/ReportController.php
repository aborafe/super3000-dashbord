<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $perPage = 10;

        $summary = [
            'totalSales' => Order::query()->sum('total'),
            'ordersCount' => Order::query()->count(),
            'avgOrder' => Order::query()->avg('total'),
        ];

        $salesByDay = Order::query()
            ->selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->groupByRaw('DATE(created_at)')
            ->orderByDesc('date')
            ->paginate($perPage, ['*'], 'sales_page')
            ->withQueryString();

        $topProducts = OrderItem::query()
            ->select('product_id', DB::raw('SUM(qty) as total_qty'), DB::raw('SUM(line_total) as total_sales'))
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByDesc('total_sales')
            ->paginate($perPage, ['*'], 'top_products_page')
            ->withQueryString();

        $salesLast7Days = (float) Order::query()
            ->whereDate('created_at', '>=', now()->subDays(6)->toDateString())
            ->sum('total');
        $ordersLast30Days = Order::query()
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->count();
        $highValueOrders = Order::query()
            ->where('total', '>=', 500)
            ->count();
        $customerLedgerBaseQuery = Customer::query()
            ->select(['id', 'name'])
            ->withSum([
                'orders as invoice_total' => fn ($query) => $query->where('status', '!=', Order::STATUS_CANCELLED),
            ], 'total')
            ->withSum([
                'payments as paid_total' => fn ($query) => $query->where('status', 'paid'),
            ], 'amount')
            ->orderBy('name');

        $customerLedgerSnapshot = (clone $customerLedgerBaseQuery)
            ->get()
            ->map(function (Customer $customer): array {
                $invoiceTotal = (float) ($customer->invoice_total ?? 0);
                $paidTotal = (float) ($customer->paid_total ?? 0);
                $balance = round($paidTotal - $invoiceTotal, 2);

                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'invoice_total' => round($invoiceTotal, 2),
                    'paid_total' => round($paidTotal, 2),
                    'balance' => $balance,
                    'state' => $balance < 0 ? 'debtor' : ($balance > 0 ? 'creditor' : 'balanced'),
                ];
            })
            ->values();

        $totalDebts = (float) $customerLedgerSnapshot
            ->filter(fn (array $row): bool => $row['balance'] < 0)
            ->sum(fn (array $row): float => abs((float) $row['balance']));
        $totalCredits = (float) $customerLedgerSnapshot
            ->filter(fn (array $row): bool => $row['balance'] > 0)
            ->sum('balance');
        $netPosition = round($totalCredits - $totalDebts, 2);

        $customerLedger = (clone $customerLedgerBaseQuery)
            ->paginate($perPage, ['*'], 'ledger_page')
            ->withQueryString();
        $customerLedger->setCollection(
            $customerLedger->getCollection()
                ->map(function (Customer $customer): array {
                    $invoiceTotal = (float) ($customer->invoice_total ?? 0);
                    $paidTotal = (float) ($customer->paid_total ?? 0);
                    $balance = round($paidTotal - $invoiceTotal, 2);

                    return [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'invoice_total' => round($invoiceTotal, 2),
                        'paid_total' => round($paidTotal, 2),
                        'balance' => $balance,
                        'state' => $balance < 0 ? 'debtor' : ($balance > 0 ? 'creditor' : 'balanced'),
                    ];
                })
                ->values()
        );

        $openInvoices = Order::query()
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->whereRaw('orders.total > (SELECT COALESCE(SUM(pa.amount), 0) FROM payment_allocations pa WHERE pa.order_id = orders.id)')
            ->with('customer:id,name')
            ->withSum('paymentAllocations as allocated_amount', 'amount')
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'open_invoices_page')
            ->withQueryString();
        $openInvoices->setCollection(
            $openInvoices->getCollection()
                ->map(function (Order $order): array {
                    $allocated = (float) ($order->allocated_amount ?? 0);
                    $due = max(0, round((float) $order->total - $allocated, 2));

                    return [
                        'id' => $order->id,
                        'order_no' => $order->order_no,
                        'customer_name' => $order->customer?->name ?? '-',
                        'total' => (float) $order->total,
                        'paid' => round($allocated, 2),
                        'due' => $due,
                        'status' => $order->normalized_status,
                        'created_at' => $order->created_at,
                    ];
                })
                ->values()
        );

        $paymentsLog = Payment::query()
            ->where('status', 'paid')
            ->with(['customer:id,name', 'order:id,order_no'])
            ->withSum('allocations as allocated_amount', 'amount')
            ->orderByDesc(DB::raw('COALESCE(paid_at, created_at)'))
            ->paginate($perPage, ['*'], 'payments_page')
            ->withQueryString();
        $paymentsLog->setCollection(
            $paymentsLog->getCollection()
                ->map(function (Payment $payment): array {
                    $allocated = round((float) ($payment->allocated_amount ?? 0), 2);
                    $amount = (float) $payment->amount;

                    return [
                        'id' => $payment->id,
                        'customer_name' => $payment->customer?->name ?? '-',
                        'order_no' => $payment->order?->order_no,
                        'source' => $payment->source,
                        'method' => $payment->method,
                        'amount' => $amount,
                        'allocated' => $allocated,
                        'unallocated' => max(0, round($amount - $allocated, 2)),
                        'paid_at' => $payment->paid_at ?? $payment->created_at,
                    ];
                })
                ->values()
        );

        $tableStats = [
            [
                'value' => money($salesLast7Days),
                'label' => __('Sales (Last 7 Days)'),
                'icon' => 'bx-line-chart',
            ],
            [
                'value' => number_format($ordersLast30Days),
                'label' => __('Orders (Last 30 Days)'),
                'icon' => 'bx-receipt',
            ],
            [
                'value' => number_format($highValueOrders),
                'label' => __('High Value Orders'),
                'icon' => 'bx-trending-up',
            ],
            [
                'value' => money($totalDebts),
                'label' => __('Total Debts'),
                'icon' => 'bx-trending-down',
            ],
        ];

        $ledgerSummary = [
            'total_debts' => round($totalDebts, 2),
            'total_credits' => round($totalCredits, 2),
            'net_position' => $netPosition,
        ];

        return view('admin.operations.reports.index', compact(
            'summary',
            'salesByDay',
            'topProducts',
            'tableStats',
            'ledgerSummary',
            'customerLedger',
            'openInvoices',
            'paymentsLog',
            'perPage'
        ));
    }
}
