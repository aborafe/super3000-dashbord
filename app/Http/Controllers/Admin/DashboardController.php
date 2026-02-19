<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = Cache::remember('dashboard.stats', now()->addMinute(), function () {
            $ordersCount = Order::query()->count();
            $customersCount = Customer::query()->count();
            $productsCount = Product::query()->count();
            $totalSales = Payment::query()
                ->where('status', 'paid')
                ->sum('amount');
            $transactionsTotal = Payment::query()->count();

            $discountTotal = Order::query()
                ->selectRaw('COALESCE(SUM(subtotal - total), 0) as discount_total')
                ->value('discount_total');

            $profit = max($totalSales - $discountTotal, 0);

            return [
                'totalSales' => $totalSales,
                'ordersCount' => $ordersCount,
                'customersCount' => $customersCount,
                'productsCount' => $productsCount,
                'profit' => $profit,
                'transactionsTotal' => $transactionsTotal,
            ];
        });

        $recentOrders = Order::query()
            ->with('customer')
            ->latest()
            ->take(6)
            ->get();

        $recentPayments = Payment::query()
            ->with('order')
            ->latest()
            ->take(6)
            ->get();

        $now = Carbon::now();
        $weeklyStart = $now->copy()->subDays(6)->startOfDay();

        $weeklyGrouped = Order::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total_orders, COALESCE(SUM(total), 0) as total_revenue')
            ->whereDate('created_at', '>=', $weeklyStart->toDateString())
            ->groupBy('day')
            ->pluck('total_orders', 'day')
            ->all();

        $weeklyRevenueGrouped = Order::query()
            ->selectRaw('DATE(created_at) as day, COALESCE(SUM(total), 0) as total_revenue')
            ->whereDate('created_at', '>=', $weeklyStart->toDateString())
            ->groupBy('day')
            ->pluck('total_revenue', 'day')
            ->all();

        $weeklyOrders = [];
        $weeklyRevenue = [];
        $weeklyLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $dayKey = $date->toDateString();
            $weeklyLabels[] = $date->format('D');
            $weeklyOrders[] = (int) ($weeklyGrouped[$dayKey] ?? 0);
            $weeklyRevenue[] = (float) ($weeklyRevenueGrouped[$dayKey] ?? 0);
        }

        $monthLabels = [];
        $currentYearSeries = [];
        $previousYearSeries = [];
        $monthWindowStart = $now->copy()->subYear()->subMonths(6)->startOfMonth();
        $monthTotals = Order::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key, COALESCE(SUM(total), 0) as month_total")
            ->where('created_at', '>=', $monthWindowStart)
            ->groupBy('month_key')
            ->pluck('month_total', 'month_key')
            ->all();

        for ($i = 6; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $monthLabels[] = $month->format('M');
            $currentKey = $month->format('Y-m');
            $previousKey = $month->copy()->subYear()->format('Y-m');

            $currentYearSeries[] = (float) ($monthTotals[$currentKey] ?? 0);
            $previousYearSeries[] = (float) ($monthTotals[$previousKey] ?? 0);
        }

        $currentMonthTotal = $currentYearSeries[count($currentYearSeries) - 1] ?? 0;
        $previousMonthTotal = $currentYearSeries[count($currentYearSeries) - 2] ?? 0;
        $growth = $previousMonthTotal > 0 ? (int) round(($currentMonthTotal / $previousMonthTotal) * 100) : 0;
        $growth = min(max($growth, 0), 100);

        $profileReportSeries = array_slice($currentYearSeries, -6);
        $incomeSeries = $currentYearSeries;

        $statusCounts = Order::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $orderStatistics = [
            'labels' => [
                __('Pending'),
                __('Approved'),
                __('Shipped'),
                __('Cancelled'),
            ],
            'series' => [
                $statusCounts[Order::STATUS_PENDING] ?? 0,
                $statusCounts[Order::STATUS_APPROVED] ?? 0,
                $statusCounts[Order::STATUS_SHIPPED] ?? 0,
                $statusCounts[Order::STATUS_CANCELLED] ?? 0,
            ],
        ];

        $ordersCount = $stats['ordersCount'];
        $expensePercent = $ordersCount > 0
            ? (int) round((($statusCounts[Order::STATUS_CANCELLED] ?? 0) / $ordersCount) * 100)
            : 0;

        $topProducts = OrderItem::query()
            ->select('product_id', DB::raw('SUM(qty) as sold_qty'))
            ->with(['product.category'])
            ->groupBy('product_id')
            ->orderByDesc('sold_qty')
            ->take(6)
            ->get();

        $topProductIds = $topProducts->pluck('product_id')->filter()->unique()->values();
        $latestItems = OrderItem::query()
            ->with(['order.payments'])
            ->whereIn('product_id', $topProductIds)
            ->latest('id')
            ->get()
            ->unique('product_id')
            ->keyBy('product_id');

        $topProducts = $topProducts->map(function ($item) use ($latestItems) {
            $latestItem = $latestItems->get($item->product_id);
            $item->latestOrder = $latestItem?->order;
            $item->latestPayment = $latestItem?->order?->payments?->first();

            return $item;
        });

        return view('admin.dashboard', compact(
            'stats',
            'recentOrders',
            'recentPayments',
            'weeklyOrders',
            'weeklyRevenue',
            'weeklyLabels',
            'monthLabels',
            'currentYearSeries',
            'previousYearSeries',
            'growth',
            'profileReportSeries',
            'incomeSeries',
            'orderStatistics',
            'expensePercent',
            'topProducts'
        ));
    }
}
