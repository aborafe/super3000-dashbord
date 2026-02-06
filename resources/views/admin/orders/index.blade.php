@extends('layouts.app')

@section('content')
    @php
        $title = __('Orders');
    @endphp

    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">
            {{ __('Orders') }}
        </h1>

        @can('orders.create')
            <a href="{{ route('admin.orders.create') }}"
                class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium border border-slate-300 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-600">
                {{ __('Create order') }}
            </a>
        @endcan
    </div>

    @if (session('status'))
        <div
            class="mb-4 rounded-md border border-emerald-500/60 bg-emerald-50 px-4 py-2 text-sm text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-100">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-4 rounded-md border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="grid gap-3 md:grid-cols-5">
            <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
                    {{ __('Status') }}
                </label>
                <select name="status"
                    class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800">
                    <option value="">{{ __('All') }}</option>
                    @foreach ([
            \App\Models\Order::STATUS_PENDING => __('Pending'),
            \App\Models\Order::STATUS_CONFIRMED => __('Confirmed'),
            \App\Models\Order::STATUS_SHIPPED => __('Shipped'),
            \App\Models\Order::STATUS_COMPLETED => __('Completed'),
            \App\Models\Order::STATUS_CANCELED => __('Canceled'),
        ] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
                    {{ __('Payment status') }}
                </label>
                <select name="payment_status"
                    class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800">
                    <option value="">{{ __('All') }}</option>
                    @foreach ([
            \App\Models\Order::PAYMENT_UNPAID => __('Unpaid'),
            \App\Models\Order::PAYMENT_PARTIAL => __('Partial'),
            \App\Models\Order::PAYMENT_PAID => __('Paid'),
        ] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['payment_status'] ?? null) === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
                    {{ __('Customer') }}
                </label>
                <select name="partner_id"
                    class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800">
                    <option value="">{{ __('All') }}</option>
                    @foreach ($partners as $partner)
                        <option value="{{ $partner->id }}" @selected(($filters['partner_id'] ?? null) == $partner->id)>
                            {{ $partner->name }}
                        </option>
                    @endforeach
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
        </form>
    </div>

    <div class="overflow-x-auto rounded-md border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800">
                <tr>
                    <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('Order #') }}
                    </th>
                    <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('Customer') }}
                    </th>
                    <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('Status') }}
                    </th>
                    <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('Payment') }}
                    </th>
                    <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('Total') }}
                    </th>
                    <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('Profit') }}
                    </th>
                    <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('Created at') }}
                    </th>
                    <th class="px-3 py-2 text-end font-medium text-slate-600 dark:text-slate-200 sr-only">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($orders as $order)
                    <tr>
                        <td class="px-3 py-2 whitespace-nowrap font-mono text-xs text-slate-700 dark:text-slate-200">
                            {{ $order->order_no }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            {{ $order->partner?->name ?? '—' }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                @class([
                                    'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200' =>
                                        $order->status === \App\Models\Order::STATUS_PENDING,
                                    'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-100' =>
                                        $order->status === \App\Models\Order::STATUS_CONFIRMED,
                                    'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-100' =>
                                        $order->status === \App\Models\Order::STATUS_SHIPPED,
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-100' =>
                                        $order->status === \App\Models\Order::STATUS_COMPLETED,
                                    'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-100' =>
                                        $order->status === \App\Models\Order::STATUS_CANCELED,
                                ])
                            ">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                @class([
                                    'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-100' =>
                                        $order->payment_status === \App\Models\Order::PAYMENT_UNPAID,
                                    'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-100' =>
                                        $order->payment_status === \App\Models\Order::PAYMENT_PARTIAL,
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-100' =>
                                        $order->payment_status === \App\Models\Order::PAYMENT_PAID,
                                ])
                            ">
                                {{ ucfirst($order->payment_status) }}
                            </span>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            {{ number_format($order->total, 2) }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            {{ number_format($order->profit, 2) }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-500 dark:text-slate-400">
                            {{ $order->created_at?->format('Y-m-d H:i') }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-end">
                            <a href="{{ route('admin.orders.show', $order) }}"
                                class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium border border-slate-300 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-600">
                                {{ __('View') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-3 py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                            {{ __('No orders found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div
            class="border-t border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-400">
            {{ $orders->links() }}
        </div>
    </div>
@endsection
