<?php

if (! function_exists('currency_code')) {
    function currency_code(): string
    {
        return (string) config('app.currency_code', 'EGP');
    }
}

if (! function_exists('currency_symbol')) {
    function currency_symbol(): string
    {
        return (string) config('app.currency_symbol', 'ج.م');
    }
}

if (! function_exists('money')) {
    function money(float|int|string|null $amount, int $decimals = 2, bool $withCode = false): string
    {
        $value = is_numeric($amount) ? (float) $amount : 0.0;
        $formatted = number_format($value, $decimals);
        $moneyValue = currency_symbol().' '.$formatted;

        return $withCode ? $moneyValue.' '.currency_code() : $moneyValue;
    }
}

if (! function_exists('notification_status_label')) {
    function notification_status_label(string $status): string
    {
        $normalized = \Illuminate\Support\Str::of($status)
            ->replace(['_', '-'], ' ')
            ->squish()
            ->headline()
            ->toString();

        return $normalized !== '' ? __($normalized) : '-';
    }
}

if (! function_exists('localized_notification_payload')) {
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    function localized_notification_payload(array $payload, ?string $notificationClass = null): array
    {
        $data = $payload;
        $type = trim((string) ($data['type'] ?? ''));

        if ($type === '' && is_string($notificationClass) && $notificationClass !== '') {
            $classType = \Illuminate\Support\Str::snake(class_basename($notificationClass));
            $type = \Illuminate\Support\Str::replaceEnd('_notification', '', $classType);
        }

        $orderNo = trim((string) ($data['order_no'] ?? ''));

        if ($type === 'new_order_created') {
            $data['title'] = __('New order created');
            if ($orderNo !== '') {
                $data['message'] = __('Order :no was created.', ['no' => $orderNo]);
            }
        }

        if ($type === 'order_status_changed') {
            $data['title'] = __('Order status updated');
            $from = trim((string) ($data['from'] ?? ''));
            $to = trim((string) ($data['to'] ?? ''));

            if ($orderNo !== '' && $from !== '' && $to !== '') {
                $data['message'] = __('Order :no status changed from :from to :to.', [
                    'no' => $orderNo,
                    'from' => notification_status_label($from),
                    'to' => notification_status_label($to),
                ]);
            }
        }

        if ($type === 'order_payment_recorded') {
            $data['title'] = __('Payment update');
            $amount = (float) ($data['payment_amount'] ?? 0);
            $dueAfter = (float) ($data['due_after'] ?? 0);
            $isPartial = array_key_exists('is_partial', $data) ? (bool) $data['is_partial'] : $dueAfter > 0;

            if ($orderNo !== '') {
                $data['message'] = $isPartial
                    ? __('Payment of :amount was recorded for order :no. Remaining due: :due.', [
                        'amount' => money($amount),
                        'no' => $orderNo,
                        'due' => money($dueAfter),
                    ])
                    : __('Payment of :amount was recorded for order :no. Invoice is fully paid.', [
                        'amount' => money($amount),
                        'no' => $orderNo,
                    ]);
            }
        }

        if ($type === 'low_stock_alert') {
            $data['title'] = __('Low stock alert');
            $data['message'] = __(':product is low in :warehouse (qty: :qty).', [
                'product' => (string) ($data['product_name'] ?? __('Unknown product')),
                'warehouse' => (string) ($data['warehouse_name'] ?? __('Warehouse')),
                'qty' => (string) ($data['qty'] ?? 0),
            ]);
        }

        $isProfileUpdate = $type === 'customer_profile_updated'
            || (isset($data['old_profile']) && isset($data['new_profile']));

        if ($isProfileUpdate) {
            $name = trim((string) ($data['sender_name'] ?? $data['customer_name'] ?? __('Customer')));
            $data['title'] = __('Customer profile updated');
            $data['message'] = __('Customer :name updated account profile details.', [
                'name' => $name !== '' ? $name : __('Customer'),
            ]);
        }

        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            if ($type !== '') {
                $title = __(\Illuminate\Support\Str::of($type)->replace('_', ' ')->headline()->toString());
            } elseif (is_string($notificationClass) && $notificationClass !== '') {
                $title = __(class_basename($notificationClass));
            } else {
                $title = __('Notification');
            }
            $data['title'] = $title;
        }

        $data['message'] = (string) ($data['message'] ?? '');

        return $data;
    }
}
