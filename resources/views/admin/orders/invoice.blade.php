@extends('layouts.app')

@section('content')
    @php
        $title = __('Invoice');
    @endphp

    <style>
        @media print {
            aside,
            header,
            .no-print {
                display: none !important;
            }

            main {
                padding: 0 !important;
            }

            body {
                background: #fff !important;
            }
        }
    </style>

    <div class="no-print mb-4 flex items-center justify-between">
        <h1 class="text-xl font-semibold">{{ __('Invoice') }}</h1>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.orders.show', $order) }}"
                class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium border border-slate-300 bg-white hover:bg-slate-50 dark:bg-slate-900 dark:border-slate-600">
                {{ __('Back to order') }}
            </a>
            <button type="button" onclick="window.print()"
                class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium border border-sky-500 bg-sky-600 text-white hover:bg-sky-700">
                {{ __('Print') }}
            </button>
        </div>
    </div>

    <div class="rounded-md border border-slate-200 bg-white p-6 text-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="flex items-center justify-between mb-6">
            <div>
                <div class="text-lg font-semibold">{{ config('app.name', 'Super3000') }}</div>
                <div class="text-slate-500">{{ __('Invoice') }}</div>
            </div>
            <div class="text-end">
                <div class="font-mono text-sm">{{ $order->order_no }}</div>
                <div class="text-slate-500">{{ $order->created_at?->format('Y-m-d') }}</div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 mb-6">
            <div>
                <div class="text-xs text-slate-500">{{ __('Billed to') }}</div>
                <div class="font-semibold">{{ $order->partner?->name ?? '—' }}</div>
                <div class="text-slate-500">{{ $order->partner?->phone ?? '' }}</div>
            </div>
            <div>
                <div class="text-xs text-slate-500">{{ __('Status') }}</div>
                <div class="font-semibold">{{ ucfirst($order->status) }}</div>
                <div class="text-slate-500">{{ __('Payment: :status', ['status' => ucfirst($order->payment_status)]) }}</div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-800">
                    <tr>
                        <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('Product') }}</th>
                        <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('Qty') }}</th>
                        <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('Price') }}</th>
                        <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('Line total') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="px-3 py-2">{{ $item->product?->name ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $item->qty }}</td>
                            <td class="px-3 py-2">{{ number_format($item->price, 2) }}</td>
                            <td class="px-3 py-2">{{ number_format($item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex justify-end">
            <div class="w-full max-w-xs space-y-1 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">{{ __('Subtotal') }}</span>
                    <span class="font-medium">{{ number_format($order->total, 2) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">{{ __('Cost total') }}</span>
                    <span class="font-medium">{{ number_format($order->cost_total, 2) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">{{ __('Profit') }}</span>
                    <span class="font-semibold text-emerald-600 dark:text-emerald-300">
                        {{ number_format($order->profit, 2) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
@endsection
