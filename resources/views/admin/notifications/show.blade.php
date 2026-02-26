@extends('layouts.admin')

@section('title', __('Notification Details'))

@section('content')
    @php $locale = app()->getLocale(); @endphp
    @php $isRead = $notification->read_at !== null; @endphp
    @php $notificationDate = optional($notification->created_at)->format('Y-m-d H:i') ?: '-'; @endphp
    @php $template = $template ?? 'generic'; @endphp

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold py-3 mb-0">{{ __('Notification Details') }}</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1 mb-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.dashboard', ['locale' => $locale]) }}">{{ __('Dashboard') }}</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.notifications.index', ['locale' => $locale]) }}">{{ __('Notifications') }}</a>
                        </li>
                        <li class="breadcrumb-item active">{{ __('Details') }}</li>
                    </ol>
                </nav>
            </div>
            <a href="{{ route('admin.notifications.index', ['locale' => $locale]) }}" class="btn btn-sm btn-outline-primary">
                {{ __('Back to notifications') }}
            </a>
        </div>

        @if ($template === 'new_order')
            @php $orderNo = (string) ($orderDetails['order_no'] ?? '-'); @endphp
            @php $customerName = (string) ($orderDetails['customer_name'] ?? '-'); @endphp
            @php $invoiceValue = (float) ($orderDetails['invoice_value'] ?? 0); @endphp
            @php $orderDate = $orderDetails['order_date'] ?? null; @endphp
            @php $orderDateLabel = $orderDate ? $orderDate->format('Y-m-d H:i') : '-'; @endphp
            @php $orderUrl = $orderDetails['order_url'] ?? null; @endphp

            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                    <h5 class="mb-0">{{ __('New Order') }} #{{ $orderNo }}</h5>
                    <span class="badge {{ $isRead ? 'bg-label-success' : 'bg-label-warning' }}">
                        {{ $isRead ? __('Read') : __('Unread') }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">{{ __('Order Number') }}</small>
                            <div>#{{ $orderNo }}</div>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">{{ __('Customer Name') }}</small>
                            <div>{{ $customerName !== '' ? $customerName : '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">{{ __('Invoice Value') }}</small>
                            <div>{{ number_format($invoiceValue, 2) }}</div>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">{{ __('Date') }}</small>
                            <div>{{ $orderDateLabel }}</div>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block mb-1">{{ __('Details') }}</small>
                            <div style="white-space: pre-line;">
                                {{ $message !== '' ? $message : __('No details available.') }}
                            </div>
                        </div>
                    </div>

                    @if (is_string($orderUrl) && $orderUrl !== '')
                        <div class="mt-4">
                            <a href="{{ $orderUrl }}" class="btn btn-primary btn-sm">
                                {{ __('Open Order') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @elseif ($template === 'message')
            @php $senderName = (string) ($messageDetails['sender_name'] ?? __('System')); @endphp
            @php $oldProfile = is_array($messageDetails['old_profile'] ?? null) ? $messageDetails['old_profile'] : null; @endphp
            @php $newProfile = is_array($messageDetails['new_profile'] ?? null) ? $messageDetails['new_profile'] : null; @endphp
            @php $changedFields = is_array($messageDetails['changed_fields'] ?? null) ? $messageDetails['changed_fields'] : []; @endphp

            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                    <h5 class="mb-0">{{ $title }}</h5>
                    <span class="badge {{ $isRead ? 'bg-label-success' : 'bg-label-warning' }}">
                        {{ $isRead ? __('Read') : __('Unread') }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">{{ __('Sender Name') }}</small>
                            <div>{{ $senderName !== '' ? $senderName : __('System') }}</div>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">{{ __('Message Date') }}</small>
                            <div>{{ $notificationDate }}</div>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block mb-1">{{ __('Subject') }}</small>
                            <div style="white-space: pre-line;">
                                {{ $message !== '' ? $message : __('No message content.') }}
                            </div>
                        </div>
                        @if (is_array($oldProfile) && is_array($newProfile))
                            <div class="col-md-6">
                                <small class="text-muted d-block mb-1">{{ __('Old Profile Data') }}</small>
                                <div class="border rounded p-2 bg-light-subtle">
                                    @foreach ($oldProfile as $field => $value)
                                        <div>
                                            <strong>{{ \Illuminate\Support\Str::headline((string) $field) }}:</strong>
                                            {{ (string) $value !== '' ? (string) $value : '-' }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block mb-1">{{ __('New Profile Data') }}</small>
                                <div class="border rounded p-2 bg-light-subtle">
                                    @foreach ($newProfile as $field => $value)
                                        <div>
                                            <strong>{{ \Illuminate\Support\Str::headline((string) $field) }}:</strong>
                                            {{ (string) $value !== '' ? (string) $value : '-' }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if (!empty($changedFields))
                            <div class="col-12">
                                <small class="text-muted d-block mb-1">{{ __('Changed Fields') }}</small>
                                <div>{{ collect($changedFields)->map(fn ($field) => \Illuminate\Support\Str::headline((string) $field))->implode(', ') }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                    <h5 class="mb-0">{{ $title }}</h5>
                    <span class="badge {{ $isRead ? 'bg-label-success' : 'bg-label-warning' }}">
                        {{ $isRead ? __('Read') : __('Unread') }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <small class="text-muted d-block mb-1">{{ __('Subject') }}</small>
                        <div class="fs-6" style="white-space: pre-line;">
                            {{ $message !== '' ? $message : __('No message content.') }}
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">{{ __('Date') }}</small>
                            <div>{{ $notificationDate }}</div>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">{{ __('Notification ID') }}</small>
                            <div class="text-break">{{ $notification->id }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

