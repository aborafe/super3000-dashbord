@csrf

@php
    $order = $order ?? null;
    $items = $items ?? [];
    $preparedItems = collect($items)->map(function ($item) {
        return [
            'product_id' => $item['product_id'] ?? null,
            'qty' => (int) ($item['qty'] ?? 1),
            'price' => (float) ($item['price'] ?? 0),
        ];
    })->values()->all();

    $productsPayload = $products->map(function ($product) {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->price,
        ];
    })->values();
@endphp

<div x-data="orderForm(@json($productsPayload), @json($preparedItems))" class="d-flex flex-column gap-4">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">{{ __('Partner') }}</label>
            <select name="partner_id" class="form-select @error('partner_id') is-invalid @enderror" required>
                <option value="">{{ __('Select partner') }}</option>
                @foreach ($partners as $partner)
                    <option value="{{ $partner->id }}"
                        @selected(old('partner_id', $order?->partner_id) == $partner->id)>
                        {{ $partner->name }} ({{ $partner->phone }})
                    </option>
                @endforeach
            </select>
            @error('partner_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-3">
            <label class="form-label">{{ __('Order status') }}</label>
            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                @foreach ([
                    \App\Models\Order::STATUS_PENDING => __('Pending'),
                    \App\Models\Order::STATUS_CONFIRMED => __('Confirmed'),
                    \App\Models\Order::STATUS_SHIPPED => __('Shipped'),
                    \App\Models\Order::STATUS_COMPLETED => __('Completed'),
                    \App\Models\Order::STATUS_CANCELED => __('Canceled'),
                ] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $order?->status ?? \App\Models\Order::STATUS_PENDING) === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-3">
            <label class="form-label">{{ __('Payment status') }}</label>
            <select name="payment_status" class="form-select @error('payment_status') is-invalid @enderror" required>
                @foreach ([
                    \App\Models\Order::PAYMENT_UNPAID => __('Unpaid'),
                    \App\Models\Order::PAYMENT_PARTIAL => __('Partial'),
                    \App\Models\Order::PAYMENT_PAID => __('Paid'),
                ] as $value => $label)
                    <option value="{{ $value }}" @selected(old('payment_status', $order?->payment_status ?? \App\Models\Order::PAYMENT_UNPAID) === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('payment_status')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0">{{ __('Order items') }}</h5>
            <button type="button" @click="addItem()" class="btn btn-sm btn-outline-primary">
                {{ __('Add item') }}
            </button>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Product') }}</th>
                        <th>{{ __('Qty') }}</th>
                        <th>{{ __('Price') }}</th>
                        <th>{{ __('Line total') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    <template x-for="(item, index) in items" :key="item.key">
                        <tr>
                            <td>
                                <select x-model="item.product_id" @change="syncPrice(item)"
                                    :name="'items[' + index + '][product_id]'"
                                    class="form-select" required>
                                    <option value="">{{ __('Select product') }}</option>
                                    <template x-for="product in products" :key="product.id">
                                        <option :value="product.id" x-text="product.name"></option>
                                    </template>
                                </select>
                            </td>
                            <td style="width: 120px;">
                                <input type="number" min="1" step="1" x-model.number="item.qty"
                                    :name="'items[' + index + '][qty]'"
                                    class="form-control" required>
                            </td>
                            <td style="width: 140px;">
                                <input type="number" min="0" step="0.01" x-model.number="item.price"
                                    :name="'items[' + index + '][price]'"
                                    class="form-control" required>
                            </td>
                            <td>
                                <span x-text="formatMoney(item.qty * item.price)"></span>
                            </td>
                            <td class="text-end">
                                <button type="button" @click="removeItem(index)"
                                    class="btn btn-sm btn-outline-danger"
                                    :disabled="items.length === 1">
                                    {{ __('Remove') }}
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        @error('items')
            <div class="text-danger small px-4 pb-3">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">
        {{ __('Cancel') }}
    </a>
    <button type="submit" class="btn btn-primary">
        {{ $submitLabel ?? __('Save') }}
    </button>
</div>

<script>
    window.orderForm = (products, items) => ({
        products: products || [],
        items: (items && items.length ? items : [{ product_id: '', qty: 1, price: 0 }]).map((item) => ({
            ...item,
            key: `${Date.now()}-${Math.random()}`,
        })),
        addItem() {
            this.items.push({ product_id: '', qty: 1, price: 0, key: `${Date.now()}-${Math.random()}` });
        },
        removeItem(index) {
            if (this.items.length === 1) {
                return;
            }
            this.items.splice(index, 1);
        },
        syncPrice(item) {
            const product = this.products.find((p) => String(p.id) === String(item.product_id));
            if (product && (!item.price || Number(item.price) === 0)) {
                item.price = Number(product.price || 0);
            }
        },
        formatMoney(value) {
            const number = Number(value || 0);
            return number.toFixed(2);
        },
    });
</script>
