@extends('layouts.app')

@section('content')
    @php
        $title = __('Edit warehouse');
    @endphp

    <div class="mb-4">
        <h1 class="text-xl font-semibold">{{ __('Edit warehouse') }}</h1>
    </div>

    <div class="rounded-md border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-900">
        <form method="POST" action="{{ route('admin.warehouses.update', $warehouse) }}">
            @method('PUT')
            @include('admin.warehouses._form', ['submitLabel' => __('Update'), 'warehouse' => $warehouse])
        </form>
    </div>
@endsection
