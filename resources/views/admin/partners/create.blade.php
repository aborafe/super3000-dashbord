@extends('layouts.app')

@section('content')
    @php
        $title = __('Add partner');
    @endphp

    <div class="mb-4">
        <h1 class="text-xl font-semibold">{{ __('Add partner') }}</h1>
    </div>

    <div class="rounded-md border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-900">
        <form method="POST" action="{{ route('admin.partners.store') }}">
            @include('admin.partners._form', ['submitLabel' => __('Create')])
        </form>
    </div>
@endsection
