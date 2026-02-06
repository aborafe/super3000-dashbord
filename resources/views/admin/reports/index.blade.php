@extends('layouts.app')

@section('content')
    @php
        $title = __('Reports');
    @endphp

    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">
            {{ __('Reports overview') }}
        </h1>
    </div>

    <div class="mb-4 rounded-md border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="grid gap-3 md:grid-cols-6">
            <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
                    {{ __('Quick range') }}
                </label>
                <select name="range"
                    class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800">
                    <option value="">{{ __('Custom') }}</option>
                    <option value="7" @selected(($filters['range'] ?? null) === '7')>{{ __('Last 7 days') }}</option>
                    <option value="30" @selected(($filters['range'] ?? null) === '30')>{{ __('Last 30 days') }}</option>
                    <option value="90" @selected(($filters['range'] ?? null) === '90')>{{ __('Last 90 days') }}</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
                    {{ __('From date') }}
                </label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}"
                    class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800">
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
                    {{ __('To date') }}
                </label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}"
                    class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800">
            </div>

            <div class="flex items-end">
                <button type="submit"
                    class="inline-flex w-full justify-center items-center px-3 py-2 rounded-md text-sm font-medium border border-slate-300 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-600">
                    {{ __('Apply') }}
                </button>
            </div>
        </form>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <section class="rounded-md border border-slate-200 bg-white p-4 text-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                {{ __('Key metrics') }}
            </h2>

            <dl class="space-y-2">
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">{{ __('Total orders') }}</dt>
                    <dd class="font-semibold">{{ $summary['orders_count'] }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">{{ __('Revenue') }}</dt>
                    <dd class="font-semibold">{{ number_format($summary['revenue_total'], 2) }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">{{ __('Profit') }}</dt>
                    <dd class="font-semibold text-emerald-600 dark:text-emerald-300">
                        {{ number_format($summary['profit_total'], 2) }}
                    </dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">{{ __('Avg. order value') }}</dt>
                    <dd class="font-semibold">{{ number_format($summary['avg_order_value'], 2) }}</dd>
                </div>
            </dl>
        </section>

        <section
            class="rounded-md border border-slate-200 bg-white p-4 text-sm dark:border-slate-700 dark:bg-slate-900 md:col-span-2">
            <h2 class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                {{ __('Top products') }}
            </h2>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800">
                        <tr>
                            <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">
                                {{ __('Product') }}</th>
                            <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">
                                {{ __('Qty sold') }}</th>
                            <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">
                                {{ __('Revenue') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($topProducts as $row)
                            <tr>
                                <td class="px-3 py-2">{{ $row['product_name'] ?: __('Unknown product') }}</td>
                                <td class="px-3 py-2">{{ $row['qty'] }}</td>
                                <td class="px-3 py-2">{{ number_format($row['revenue'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-3 py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                                    {{ __('No data for the selected period.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-md border border-slate-200 bg-white p-4 text-sm dark:border-slate-700 dark:bg-slate-900 md:col-span-3">
            <h2 class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                {{ __('Least-selling products') }}
            </h2>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800">
                        <tr>
                            <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">
                                {{ __('Product') }}</th>
                            <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">
                                {{ __('Qty sold') }}</th>
                            <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">
                                {{ __('Revenue') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($leastProducts as $row)
                            <tr>
                                <td class="px-3 py-2">{{ $row['product_name'] ?: __('Unknown product') }}</td>
                                <td class="px-3 py-2">{{ $row['qty'] }}</td>
                                <td class="px-3 py-2">{{ number_format($row['revenue'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-3 py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                                    {{ __('No data for the selected period.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-md border border-slate-200 bg-white p-4 text-sm dark:border-slate-700 dark:bg-slate-900 md:col-span-3">
            <h2 class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                {{ __('Financial distribution') }}
            </h2>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <canvas id="financialChart" height="180"></canvas>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span>{{ __('Cash') }}</span>
                        <span class="font-semibold">{{ number_format($financial['cash'], 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>{{ __('Receivables') }}</span>
                        <span class="font-semibold">{{ number_format($financial['receivables'], 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>{{ __('Inventory') }}</span>
                        <span class="font-semibold">{{ number_format($financial['inventory'], 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>{{ __('Remaining') }}</span>
                        <span class="font-semibold">{{ number_format($financial['remaining'], 2) }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section
            class="rounded-md border border-slate-200 bg-white p-4 text-sm dark:border-slate-700 dark:bg-slate-900 md:col-span-3">
            <h2 class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                {{ __('Debts snapshot') }}
            </h2>

            <dl class="grid gap-3 md:grid-cols-3">
                <div>
                    <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('Total debts') }}</dt>
                    <dd class="text-lg font-semibold">
                        {{ number_format($debts['total_debts'], 2) }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('Open debts') }}</dt>
                    <dd class="text-lg font-semibold text-amber-600 dark:text-amber-300">
                        {{ number_format($debts['open_debts'], 2) }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('Debts count') }}</dt>
                    <dd class="text-lg font-semibold">
                        {{ $debts['count'] }}
                    </dd>
                </div>
            </dl>
        </section>

        <section class="rounded-md border border-slate-200 bg-white p-4 text-sm dark:border-slate-700 dark:bg-slate-900 md:col-span-3">
            <h2 class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                {{ __('Sales charts') }}
            </h2>
            <canvas id="salesChart" height="140"></canvas>
        </section>
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            if (typeof Chart === 'undefined') {
                return;
            }

            const financialCtx = document.getElementById('financialChart');
            if (financialCtx) {
                new Chart(financialCtx, {
                    type: 'pie',
                    data: {
                        labels: [
                            '{{ __('Cash') }}',
                            '{{ __('Receivables') }}',
                            '{{ __('Inventory') }}',
                            '{{ __('Remaining') }}',
                        ],
                        datasets: [{
                            data: [
                                {{ $financial['cash'] }},
                                {{ $financial['receivables'] }},
                                {{ $financial['inventory'] }},
                                {{ $financial['remaining'] }},
                            ],
                            backgroundColor: ['#22c55e', '#f59e0b', '#3b82f6', '#94a3b8'],
                        }]
                    },
                });
            }

            const salesCtx = document.getElementById('salesChart');
            if (salesCtx) {
                const topLabels = @json($topProducts->pluck('product_name'));
                const topQty = @json($topProducts->pluck('qty'));

                new Chart(salesCtx, {
                    type: 'bar',
                    data: {
                        labels: topLabels,
                        datasets: [{
                            label: '{{ __('Qty sold') }}',
                            data: topQty,
                            backgroundColor: '#38bdf8',
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                            }
                        }
                    }
                });
            }
        });
    </script>
@endsection
