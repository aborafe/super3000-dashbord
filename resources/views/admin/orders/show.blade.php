@extends('layouts.app')

@section('content')
    @php
        $title = __('Order details');
    @endphp

    <div class="mb-4 flex items-center justify-between gap-2">
        <div>
            <h1 class="text-xl font-semibold">
                {{ __('Order :no', ['no' => $order->order_no]) }}
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ __('Customer: :name', ['name' => $order->partner?->name ?? '—']) }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.orders.index') }}"
                class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium border border-slate-300 bg-white hover:bg-slate-50 dark:bg-slate-900 dark:border-slate-600">
                {{ __('Back to list') }}
            </a>
            <a href="{{ route('admin.orders.invoice', $order) }}"
                class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium border border-slate-300 bg-white hover:bg-slate-50 dark:bg-slate-900 dark:border-slate-600">
                {{ __('Invoice') }}
            </a>
            @can('orders.update')
                <a href="{{ route('admin.orders.edit', $order) }}"
                    class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium border border-sky-500 bg-sky-600 text-white hover:bg-sky-700">
                    {{ __('Edit') }}
                </a>
            @endcan
        </div>
    </div>

    @if (session('status'))
        <div
            class="mb-4 rounded-md border border-emerald-500/60 bg-emerald-50 px-4 py-2 text-sm text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-100">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-md border border-rose-400/60 bg-rose-50 px-4 py-2 text-sm text-rose-800 dark:bg-rose-900/40 dark:text-rose-100">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-3 mb-4">
        <section
            class="rounded-md border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900 md:col-span-2">
            <h2 class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                {{ __('Order items') }}
            </h2>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800">
                        <tr>
                            <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">
                                {{ __('Product') }}</th>
                            <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">
                                {{ __('Qty') }}</th>
                            <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">
                                {{ __('Price') }}</th>
                            <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">
                                {{ __('Line total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="px-3 py-2">
                                    {{ $item->product?->name ?? '—' }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ $item->qty }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ number_format($item->price, 2) }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ number_format($item->line_total, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="space-y-4">
            <div class="rounded-md border border-slate-200 bg-white p-4 text-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                    {{ __('Summary') }}
                </h2>

                <dl class="space-y-1">
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500 dark:text-slate-400">{{ __('Subtotal') }}</dt>
                        <dd class="font-medium">{{ number_format($order->total, 2) }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500 dark:text-slate-400">{{ __('Cost total') }}</dt>
                        <dd class="font-medium">{{ number_format($order->cost_total, 2) }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500 dark:text-slate-400">{{ __('Profit') }}</dt>
                        <dd class="font-semibold text-emerald-600 dark:text-emerald-300">
                            {{ number_format($order->profit, 2) }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-md border border-slate-200 bg-white p-4 text-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                    {{ __('Status') }}
                </h2>

                <p class="mb-2 text-xs text-slate-500 dark:text-slate-400">
                    {{ __('Change status and payment status for this order.') }}
                </p>

                @can('orders.change_status')
                    <form method="POST" action="{{ route('admin.orders.update-status', $order) }}" class="space-y-3">
                        @csrf
                        @method('PUT')

                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
                            {{ __('Order status') }}
                        </label>
                        <select name="status"
                            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800">
                            @foreach ([
                \App\Models\Order::STATUS_PENDING => __('Pending'),
                \App\Models\Order::STATUS_CONFIRMED => __('Confirmed'),
                \App\Models\Order::STATUS_SHIPPED => __('Shipped'),
                \App\Models\Order::STATUS_COMPLETED => __('Completed'),
                \App\Models\Order::STATUS_CANCELED => __('Canceled'),
            ] as $value => $label)
                                <option value="{{ $value }}" @selected($order->status === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>

                        <button type="submit"
                            class="inline-flex w-full justify-center items-center px-3 py-1.5 rounded-md text-sm font-medium border border-sky-500 bg-sky-600 text-white hover:bg-sky-700">
                            {{ __('Update status') }}
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.orders.update-payment-status', $order) }}"
                        class="mt-4 space-y-3">
                        @csrf
                        @method('PUT')

                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
                            {{ __('Payment status') }}
                        </label>
                        <select name="payment_status"
                            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800">
                            @foreach ([
                \App\Models\Order::PAYMENT_UNPAID => __('Unpaid'),
                \App\Models\Order::PAYMENT_PARTIAL => __('Partial'),
                \App\Models\Order::PAYMENT_PAID => __('Paid'),
            ] as $value => $label)
                                <option value="{{ $value }}" @selected($order->payment_status === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>

                        <button type="submit"
                            class="inline-flex w-full justify-center items-center px-3 py-1.5 rounded-md text-sm font-medium border border-sky-500 bg-sky-600 text-white hover:bg-sky-700">
                            {{ __('Update payment') }}
                        </button>
                    </form>
                @else
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ __('You do not have permission to change order status.') }}
                    </p>
                @endcan
            </div>

            <div class="rounded-md border border-slate-200 bg-white p-4 text-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                    {{ __('Payments') }}
                </h2>

                @if ($order->payments->isNotEmpty())
                    <ul class="space-y-2 mb-3">
                        @foreach ($order->payments as $payment)
                            <li class="flex items-center justify-between text-xs">
                                <span>
                                    {{ $payment->paid_at?->format('Y-m-d') ?? '—' }} ·
                                    {{ $payment->method }}
                                </span>
                                <span class="font-semibold">{{ number_format($payment->amount, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">
                        {{ __('No payments recorded yet.') }}
                    </p>
                @endif

                @can('orders.update')
                    <form method="POST" action="{{ route('admin.orders.payments.store', $order) }}" class="space-y-3">
                        @csrf

                        <div>
                            <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
                                {{ __('Amount') }}
                            </label>
                            <input type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}"
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800"
                                required>
                            @error('amount')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
                                {{ __('Method') }}
                            </label>
                            <input type="text" name="method" value="{{ old('method', 'cash') }}"
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
                                {{ __('Paid at') }}
                            </label>
                            <input type="date" name="paid_at" value="{{ old('paid_at') }}"
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800">
                        </div>

                        <button type="submit"
                            class="inline-flex w-full justify-center items-center px-3 py-1.5 rounded-md text-sm font-medium border border-sky-500 bg-sky-600 text-white hover:bg-sky-700">
                            {{ __('Add payment') }}
                        </button>
                    </form>
                @endcan
            </div>
        </section>
    </div>
@endsection
