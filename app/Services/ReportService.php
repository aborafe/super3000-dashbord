<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductStock;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Build a simple KPI overview for the selected date range.
     *
     * @return array{
     *     summary: array<string, mixed>,
     *     topProducts: \Illuminate\Support\Collection<int, array<string, mixed>>,
     *     debts: array<string, mixed>,
     * }
     */
    public function overview(?Carbon $from, ?Carbon $to, int $limit = 5): array
    {
        $ordersQuery = Order::query();

        if ($from) {
            $ordersQuery->whereDate('created_at', '>=', $from);
        }

        if ($to) {
            $ordersQuery->whereDate('created_at', '<=', $to);
        }

        /** @var \Illuminate\Support\Collection<int, \App\Models\Order> $orders */
        $orders = $ordersQuery->get();

        $summary = [
            'orders_count' => $orders->count(),
            'revenue_total' => (float) $orders->sum('total'),
            'profit_total' => 0.0,
            'avg_order_value' => $orders->count() > 0
                ? (float) ($orders->sum('total') / $orders->count())
                : 0.0,
        ];

        $topProducts = $this->buildTopProducts($from, $to, $limit);
        $leastProducts = $this->buildLeastProducts($from, $to, $limit);
        $financial = $this->buildFinancialDistribution($orders);

        $debts = $this->buildDebtsSnapshot();

        return [
            'summary' => $summary,
            'topProducts' => $topProducts,
            'leastProducts' => $leastProducts,
            'debts' => $debts,
            'financial' => $financial,
        ];
    }

    /**
     * Get simple top-selling products report by quantity.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    protected function buildTopProducts(?Carbon $from, ?Carbon $to, int $limit): Collection
    {
        $itemsQuery = OrderItem::query()
            ->with('product');

        if ($from) {
            $itemsQuery->whereHas('order', function ($q) use ($from): void {
                $q->whereDate('created_at', '>=', $from);
            });
        }

        if ($to) {
            $itemsQuery->whereHas('order', function ($q) use ($to): void {
                $q->whereDate('created_at', '<=', $to);
            });
        }

        /** @var \Illuminate\Support\Collection<int, \App\Models\OrderItem> $items */
        $items = $itemsQuery->get();

        return $items
            ->groupBy('product_id')
            ->map(function (Collection $group): array {
                $item = $group->first();
                $qty = (int) $group->sum('qty');
                $revenue = (float) $group->sum('line_total');

                return [
                    'product_id' => $item?->product_id,
                    'product_name' => $item?->product?->name ?? '',
                    'qty' => $qty,
                    'revenue' => $revenue,
                ];
            })
            ->sortByDesc('qty')
            ->take($limit)
            ->values();
    }

    /**
     * Get least-selling products by quantity.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    protected function buildLeastProducts(?Carbon $from, ?Carbon $to, int $limit): Collection
    {
        $itemsQuery = OrderItem::query()
            ->with('product');

        if ($from) {
            $itemsQuery->whereHas('order', function ($q) use ($from): void {
                $q->whereDate('created_at', '>=', $from);
            });
        }

        if ($to) {
            $itemsQuery->whereHas('order', function ($q) use ($to): void {
                $q->whereDate('created_at', '<=', $to);
            });
        }

        /** @var \Illuminate\Support\Collection<int, \App\Models\OrderItem> $items */
        $items = $itemsQuery->get();

        return $items
            ->groupBy('product_id')
            ->map(function (Collection $group): array {
                $item = $group->first();
                $qty = (int) $group->sum('qty');
                $revenue = (float) $group->sum('line_total');

                return [
                    'product_id' => $item?->product_id,
                    'product_name' => $item?->product?->name ?? '',
                    'qty' => $qty,
                    'revenue' => $revenue,
                ];
            })
            ->sortBy('qty')
            ->take($limit)
            ->values();
    }

    /**
     * Build a financial distribution snapshot (MVP).
     *
     * @param \Illuminate\Support\Collection<int, \App\Models\Order> $orders
     * @return array<string, mixed>
     */
    protected function buildFinancialDistribution(Collection $orders): array
    {
        $cash = (float) $orders
            ->where('status', Order::STATUS_APPROVED)
            ->sum('total');

        $receivables = (float) $orders
            ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_SHIPPED])
            ->sum('total');

        $inventoryValue = (float) ProductStock::query()
            ->with('product')
            ->get()
            ->sum(function (ProductStock $stock): float {
                return $stock->qty * (float) ($stock->product?->price ?? 0);
            });

        $total = $cash + $receivables + $inventoryValue;
        $remaining = max($total - ($cash + $receivables + $inventoryValue), 0);

        return [
            'cash' => $cash,
            'receivables' => $receivables,
            'inventory' => $inventoryValue,
            'remaining' => $remaining,
            'total' => $total,
            'percentages' => $total > 0 ? [
                'cash' => round(($cash / $total) * 100, 2),
                'receivables' => round(($receivables / $total) * 100, 2),
                'inventory' => round(($inventoryValue / $total) * 100, 2),
                'remaining' => round(($remaining / $total) * 100, 2),
            ] : [
                'cash' => 0,
                'receivables' => 0,
                'inventory' => 0,
                'remaining' => 0,
            ],
        ];
    }

    /**
     * Build a small snapshot of debts.
     *
     * @return array<string, mixed>
     */
    protected function buildDebtsSnapshot(): array
    {
        $ledgerRows = Customer::query()
            ->select('id')
            ->withSum([
                'orders as invoice_total' => fn ($query) => $query->where('status', '!=', Order::STATUS_CANCELLED),
            ], 'total')
            ->withSum([
                'payments as paid_total' => fn ($query) => $query->where('status', 'paid'),
            ], 'amount')
            ->get()
            ->map(function (Customer $customer): float {
                $invoiceTotal = (float) ($customer->invoice_total ?? 0);
                $paidTotal = (float) ($customer->paid_total ?? 0);

                return round($paidTotal - $invoiceTotal, 2);
            });

        $totalDebts = (float) $ledgerRows
            ->filter(fn (float $balance): bool => $balance < 0)
            ->sum(fn (float $balance): float => abs($balance));
        $totalCredits = (float) $ledgerRows
            ->filter(fn (float $balance): bool => $balance > 0)
            ->sum();

        return [
            'total_debts' => round($totalDebts, 2),
            'open_debts' => round($totalDebts, 2),
            'total_credits' => round($totalCredits, 2),
            'count' => $ledgerRows->count(),
        ];
    }
}
