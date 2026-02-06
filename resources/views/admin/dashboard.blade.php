@extends('layouts.app')

@section('content')
    @php
        $title = __('Dashboard');
    @endphp

    <div class="max-w-5xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold">
                {{ __('Admin Dashboard') }}
            </h1>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-6 text-sm dark:border-slate-700 dark:bg-slate-900">
            <p class="text-slate-700 dark:text-slate-200">
                {{ __('You are logged in as :name (:email).', ['name' => auth()->user()->name, 'email' => auth()->user()->email]) }}
            </p>
        </div>
    </div>
@endsection

