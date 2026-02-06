@extends('layouts.app')

@section('content')
    @php
        $title = __('Create order');
    @endphp

    <div class="mb-4">
        <h1 class="text-xl font-semibold">{{ __('Create order') }}</h1>
    </div>

    <div class="rounded-md border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-900">
        <form method="POST" action="{{ route('admin.orders.store') }}">
            @include('admin.orders._form', [
                'partners' => $partners,
                'products' => $products,
                'items' => old('items', []),
                'submitLabel' => __('Create'),
            ])
        </form>
    </div>
@endsection
