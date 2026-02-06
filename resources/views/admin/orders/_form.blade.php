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

<div x-data="orderForm(@json($productsPayload), @json($preparedItems))" class="space-y-6">
    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
                {{ __('Partner') }}
            </label>
            <select name="partner_id"
                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800"
                required>
                <option value="">{{ __('Select partner') }}</option>
                @foreach ($partners as $partner)
                    <option value="{{ $partner->id }}"
                        @selected(old('partner_id', $order?->partner_id) == $partner->id)>
                        {{ $partner->name }} ({{ $partner->phone }})
                    </option>
                @endforeach
            </select>
            @error('partner_id')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
                {{ __('Order status') }}
            </label>
            <select name="status"
                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800"
                required>
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
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
                {{ __('Payment status') }}
            </label>
            <select name="payment_status"
                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800"
                required>
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
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="rounded-md border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                {{ __('Order items') }}
            </h2>
            <button type="button" @click="addItem()"
                class="inline-flex items-center px-2.5 py-1.5 rounded-md text-xs font-medium border border-slate-300 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-600">
                {{ __('Add item') }}
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-800">
                    <tr>
                        <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('Product') }}</th>
                        <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('Qty') }}</th>
                        <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('Price') }}</th>
                        <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('Line total') }}</th>
                        <th class="px-3 py-2 text-end font-medium text-slate-600 dark:text-slate-200">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <template x-for="(item, index) in items" :key="item.key">
                        <tr>
                            <td class="px-3 py-2">
                                <select x-model="item.product_id" @change="syncPrice(item)"
                                    :name="'items[' + index + '][product_id]'"
                                    class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800"
                                    required>
                                    <option value="">{{ __('Select product') }}</option>
                                    <template x-for="product in products" :key="product.id">
                                        <option :value="product.id" x-text="product.name"></option>
                                    </template>
                                </select>
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" min="1" step="1" x-model.number="item.qty"
                                    :name="'items[' + index + '][qty]'"
                                    class="block w-24 rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800"
                                    required>
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" min="0" step="0.01" x-model.number="item.price"
                                    :name="'items[' + index + '][price]'"
                                    class="block w-28 rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800"
                                    required>
                            </td>
                            <td class="px-3 py-2">
                                <span x-text="formatMoney(item.qty * item.price)"></span>
                            </td>
                            <td class="px-3 py-2 text-end">
                                <button type="button" @click="removeItem(index)"
                                    class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium border border-rose-300 text-rose-700 bg-white hover:bg-rose-50 dark:bg-slate-900 dark:border-rose-500/70 dark:text-rose-200"
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
            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6 flex items-center justify-end gap-2">
    <a href="{{ route('admin.orders.index') }}"
        class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium border border-slate-300 bg-white hover:bg-slate-50 dark:bg-slate-900 dark:border-slate-600">
        {{ __('Cancel') }}
    </a>

    <button type="submit"
        class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium border border-sky-500 bg-sky-600 text-white hover:bg-sky-700">
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
