@forelse($orders as $order)
    @php
        $normalizedStatus = $order->normalized_status;
        $badge = match ($normalizedStatus) {
            'approved' => 'bg-label-success',
            'shipped' => 'bg-label-info',
            'delivered' => 'bg-label-primary',
            'returned' => 'bg-label-secondary',
            'cancelled' => 'bg-label-danger',
            default => 'bg-label-warning',
        };
        $isRecentOrder = $order->created_at && $order->created_at->greaterThan(now()->subMinutes(30));
    @endphp
    <tr data-order-id="{{ (int) $order->id }}">
        <td>
            <a href="{{ route('admin.orders.show', ['locale' => app()->getLocale(), 'order' => $order]) }}"
                class="text-body fw-medium">{{ $order->order_no }}</a>
            <span class="badge bg-label-primary ms-1 {{ $isRecentOrder ? '' : 'd-none' }}" data-new-order-badge>
                {{ __('New') }}
            </span>
        </td>
        <td>
            @if ($order->customer)
                <a href="{{ route('admin.sales.customers.edit', ['locale' => app()->getLocale(), 'customer' => $order->customer]) }}"
                    class="text-body">{{ $order->customer->name }}</a>
            @else
                <span>-</span>
            @endif
        </td>
        <td><span class="badge {{ $badge }}">{{ __(ucfirst($normalizedStatus)) }}</span></td>
        <td>{{ money($order->paid_amount, 2) }}</td>
        <td>{{ money($order->due_amount, 2) }}</td>
        <td class="{{ (float) $order->items_discount_total > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
            {{ (float) $order->items_discount_total > 0 ? money($order->items_discount_total, 2) : '' }}
        </td>
        <td>{{ money($order->total, 2) }}</td>
        <td>{{ $order->created_at?->format('Y-m-d') }}</td>
        <td>
            <a href="{{ route('admin.orders.show', ['locale' => app()->getLocale(), 'order' => $order]) }}"
                class="btn btn-sm btn-outline-secondary btn-icon-soft">
                <i class="icon-base bx bx-show"></i>
            </a>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="9" class="text-center text-muted">{{ __('No orders found.') }}</td>
    </tr>
@endforelse
