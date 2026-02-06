@extends('layouts.app')

@section('content')
    @php
        $title = __('Add employee');
    @endphp

    <div class="mb-4">
        <h1 class="text-xl font-semibold">{{ __('Add employee') }}</h1>
    </div>

    <div class="rounded-md border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-900">
        <form method="POST" action="{{ route('admin.employees.store') }}">
            @include('admin.employees._form', ['submitLabel' => __('Create')])
        </form>
    </div>
@endsection
