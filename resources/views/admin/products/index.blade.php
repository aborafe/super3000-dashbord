@extends('layouts.app')

@section('content')
    @php
        $title = __('Products');
    @endphp

    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">
            {{ __('Products') }}
        </h1>

        @can('products.create')
            <a href="{{ route('admin.products.create') }}"
                class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium border border-slate-300 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-600">
                {{ __('Add product') }}
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
        <form method="GET" action="{{ route('admin.products.index') }}" class="grid gap-3 md:grid-cols-4">
            <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
                    {{ __('Search') }}
                </label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                    class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800"
                    placeholder="{{ __('Name or SKU') }}">
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
                    {{ __('Category') }}
                </label>
                <select name="category_id"
                    class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800">
                    <option value="">{{ __('All categories') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? null) == $category->id)>
                            {{ app()->getLocale() === 'ar' ? $category->name_ar : $category->name_en }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
                    {{ __('Status') }}
                </label>
                <select name="status"
                    class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800">
                    <option value="">{{ __('All') }}</option>
                    <option value="active" @selected(($filters['status'] ?? null) === 'active')>
                        {{ __('Active') }}
                    </option>
                    <option value="inactive" @selected(($filters['status'] ?? null) === 'inactive')>
                        {{ __('Inactive') }}
                    </option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit"
                    class="inline-flex w-full justify-center items-center px-3 py-2 rounded-md text-sm font-medium border border-slate-300 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-600">
                    {{ __('Filter') }}
                </button>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto rounded-md border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800">
                <tr>
                    <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('SKU') }}
                    </th>
                    <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('Name') }}
                    </th>
                    <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('Category') }}
                    </th>
                    <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('Price') }}
                    </th>
                    <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('Stock') }}
                    </th>
                    <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('Status') }}
                    </th>
                    <th class="px-3 py-2 text-end font-medium text-slate-600 dark:text-slate-200">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($products as $product)
                    <tr>
                        <td class="px-3 py-2 whitespace-nowrap text-xs font-mono text-slate-700 dark:text-slate-200">
                            {{ $product->sku }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            {{ $product->name }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-slate-600 dark:text-slate-300">
                            {{ $product->category?->name ?? '—' }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            {{ number_format($product->price, 2) }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            {{ $product->stock }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            @if ($product->status === 'active')
                                <span
                                    class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-100">
                                    {{ __('Active') }}
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    {{ __('Inactive') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-end">
                            <div class="inline-flex items-center gap-1">
                                @can('products.update')
                                    <a href="{{ route('admin.products.edit', $product) }}"
                                        class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium border border-slate-300 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-600">
                                        {{ __('Edit') }}
                                    </a>
                                @endcan

                                @can('products.delete')
                                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                                        onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium border border-rose-300 text-rose-700 bg-white hover:bg-rose-50 dark:bg-slate-900 dark:border-rose-500/70 dark:text-rose-200">
                                            {{ __('Delete') }}
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                            {{ __('No products found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div
            class="border-t border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-400">
            {{ $products->links() }}
        </div>
    </div>
@endsection
