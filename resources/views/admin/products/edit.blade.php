@extends('layouts.app')

@section('content')
    @php
        $title = __('Edit product');
    @endphp

    <div class="mb-4">
        <h1 class="text-xl font-semibold">
            {{ __('Edit product') }}
        </h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            {{ __('Update the product details.') }}
        </p>
    </div>

    <div class="rounded-md border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
        <form method="POST" action="{{ route('admin.products.update', $product) }}" class="space-y-4">
            @csrf
            @method('PUT')

            @include('admin.products._form', [
                'product' => $product,
                'categories' => $categories,
                'submitLabel' => __('Update'),
            ])
        </form>
    </div>
@endsection
