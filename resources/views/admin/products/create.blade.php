@extends('layouts.app')

@section('content')
    @php
        $title = __('Add product');
    @endphp

    <div class="mb-4">
        <h1 class="text-xl font-semibold">
            {{ __('Add product') }}
        </h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            {{ __('Create a new product in the catalog.') }}
        </p>
    </div>

    <div class="rounded-md border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
        <form method="POST" action="{{ route('admin.products.store') }}" class="space-y-4">
            @include('admin.products._form', [
                'product' => $product ?? null,
                'categories' => $categories,
                'submitLabel' => __('Create'),
            ])
        </form>
    </div>
@endsection
