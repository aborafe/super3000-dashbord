@extends('layouts.app')

@section('content')
    @php
        $title = __('Warehouse details');
    @endphp

    <div class="mb-4 flex items-center justify-between gap-2">
        <div>
            <h1 class="text-xl font-semibold">{{ $warehouse->name }}</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ $warehouse->location ?: __('No location set') }}
            </p>
        </div>

        <a href="{{ route('admin.warehouses.index') }}"
            class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium border border-slate-300 bg-white hover:bg-slate-50 dark:bg-slate-900 dark:border-slate-600">
            {{ __('Back to list') }}
        </a>
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

    <div class="grid gap-4 md:grid-cols-3">
        <section class="rounded-md border border-slate-200 bg-white p-4 text-sm dark:border-slate-700 dark:bg-slate-900 md:col-span-2">
            <h2 class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                {{ __('Stock in this warehouse') }}
            </h2>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800">
                        <tr>
                            <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('Product') }}</th>
                            <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('SKU') }}</th>
                            <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('Qty') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($stocks as $stock)
                            <tr>
                                <td class="px-3 py-2">{{ $stock->product?->name ?? __('Unknown') }}</td>
                                <td class="px-3 py-2 text-xs font-mono">{{ $stock->product?->sku ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $stock->qty }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-3 py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                                    {{ __('No stock tracked yet.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-md border border-slate-200 bg-white p-4 text-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                {{ __('Transfer stock') }}
            </h2>

            @can('warehouses.transfer')
                <form method="POST" action="{{ route('admin.warehouses.transfer', $warehouse) }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="from_warehouse_id" value="{{ $warehouse->id }}">

                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
                            {{ __('Product') }}
                        </label>
                        <select name="product_id"
                            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800"
                            required>
                            <option value="">{{ __('Select product') }}</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>
                                    {{ $product->name }} ({{ $product->sku }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
                            {{ __('Destination warehouse') }}
                        </label>
                        <select name="to_warehouse_id"
                            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800"
                            required>
                            <option value="">{{ __('Select warehouse') }}</option>
                            @foreach ($targetWarehouses as $target)
                                <option value="{{ $target->id }}" @selected(old('to_warehouse_id') == $target->id)>
                                    {{ $target->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
                            {{ __('Quantity') }}
                        </label>
                        <input type="number" name="qty" min="1" value="{{ old('qty', 1) }}"
                            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800"
                            required>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
                            {{ __('Reason (optional)') }}
                        </label>
                        <input type="text" name="reason" value="{{ old('reason') }}"
                            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800">
                    </div>

                    <button type="submit"
                        class="inline-flex w-full justify-center items-center px-3 py-2 rounded-md text-sm font-medium border border-sky-500 bg-sky-600 text-white hover:bg-sky-700">
                        {{ __('Transfer') }}
                    </button>
                </form>
            @else
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    {{ __('You do not have permission to transfer stock.') }}
                </p>
            @endcan
        </section>
    </div>
@endsection
