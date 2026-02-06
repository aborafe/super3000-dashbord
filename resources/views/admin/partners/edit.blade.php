@extends('layouts.app')

@section('content')
    @php
        $title = __('Edit partner');
    @endphp

    <div class="mb-4">
        <h1 class="text-xl font-semibold">{{ __('Edit partner') }}</h1>
    </div>

    <div class="rounded-md border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-900">
        <form method="POST" action="{{ route('admin.partners.update', $partner) }}">
            @method('PUT')
            @include('admin.partners._form', ['submitLabel' => __('Update'), 'partner' => $partner])
        </form>
    </div>
@endsection
