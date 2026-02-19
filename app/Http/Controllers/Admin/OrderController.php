<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\StockConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Models\Product;
use App\Services\InventoryService;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        $search = request('q');
        $rawStatus = request('status');
        $status = is_string($rawStatus) && $rawStatus !== ''
            ? Order::normalizeStatus($rawStatus)
            : null;
        $perPage = (int) request('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50], true) ? $perPage : 10;

        $orders = Order::query()
            ->with('customer')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('order_no', 'like', '%' . $search . '%')
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($status, fn($query) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $tableStats = [
            [
                'value' => number_format(Order::query()->count()),
                'label' => __('All Orders'),
                'icon' => 'bx-receipt',
            ],
            [
                'value' => number_format(Order::query()->where('status', Order::STATUS_PENDING)->count()),
                'label' => __('Pending'),
                'icon' => 'bx-time-five',
            ],
            [
                'value' => number_format(Order::query()->where('status', Order::STATUS_APPROVED)->count()),
                'label' => __('Approved'),
                'icon' => 'bx-check-circle',
            ],
            [
                'value' => number_format(Order::query()->where('status', Order::STATUS_SHIPPED)->count()),
                'label' => __('Shipped'),
                'icon' => 'bx-truck',
            ],
        ];

        return view('admin.orders.index', compact('orders', 'status', 'search', 'perPage', 'tableStats'));
    }

    public function show(Order $order): View
    {
        $order->load(['customer', 'items.product', 'payments']);
        $normalizedStatus = $order->normalized_status;
        $products = Product::query()
            ->select(['id', 'name', 'price', 'sku'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $paidPayment = $order->payments
            ->where('status', 'paid')
            ->sortByDesc('paid_at')
            ->first();

        $timeline = [
            [
                'title' => __('Order was placed'),
                'subtitle' => __('Order :no was created.', ['no' => $order->order_no]),
                'time' => $order->created_at,
                'type' => 'primary',
            ],
        ];

        if ($paidPayment) {
            $timeline[] = [
                'title' => __('Payment received'),
                'subtitle' => __('Payment :method for :amount', [
                    'method' => ucfirst($paidPayment->method),
                    'amount' => '$' . number_format($paidPayment->amount, 2),
                ]),
                'time' => $paidPayment->paid_at ?? $order->updated_at,
                'type' => 'success',
            ];
        }

        if ($normalizedStatus === Order::STATUS_SHIPPED) {
            $timeline[] = [
                'title' => __('Dispatched'),
                'subtitle' => __('Package has been picked up by courier'),
                'time' => $order->updated_at,
                'type' => 'info',
            ];
        }

        if ($normalizedStatus === Order::STATUS_DELIVERED) {
            $timeline[] = [
                'title' => __('Delivered'),
                'subtitle' => __('Package has been delivered to customer'),
                'time' => $order->updated_at,
                'type' => 'success',
            ];
        }

        if ($normalizedStatus === Order::STATUS_RETURNED) {
            $timeline[] = [
                'title' => __('Returned'),
                'subtitle' => __('Order has been returned'),
                'time' => $order->updated_at,
                'type' => 'secondary',
            ];
        }

        if ($normalizedStatus === Order::STATUS_CANCELLED) {
            $timeline[] = [
                'title' => __('Cancelled'),
                'subtitle' => __('Order was cancelled'),
                'time' => $order->updated_at,
                'type' => 'danger',
            ];
        }

        $availableStatusTransitions = collect($order->availableTransitions())
            ->prepend($normalizedStatus)
            ->values()
            ->all();

        $customerOrdersCount = $order->customer?->orders()->count() ?? 0;
        $customerDetails = [
            'name' => $order->customer_name ?: ($order->customer?->name ?? ''),
            'email' => $order->customer_email ?: ($order->customer?->email ?? ''),
            'phone' => $order->customer_phone ?: ($order->customer?->phone ?? ''),
            'whatsapp' => $order->customer_whatsapp ?? '',
            'address' => $order->customer_address ?? '',
            'notes' => $order->customer_notes ?? '',
        ];
        $shippingAddress = is_array($order->shipping_address) ? $order->shipping_address : [];
        if (($shippingAddress['address_line_1'] ?? '') === '' && $customerDetails['address'] !== '') {
            $shippingAddress['address_line_1'] = $customerDetails['address'];
        }

        return view('admin.orders.show', compact(
            'order',
            'timeline',
            'customerOrdersCount',
            'products',
            'customerDetails',
            'shippingAddress',
            'availableStatusTransitions'
        ));
    }

    public function updateDetails(Request $request, Order $order): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'customer_whatsapp' => ['nullable', 'string', 'max:50'],
            'customer_address' => ['nullable', 'string', 'max:1000'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
            'shipping.address_line_1' => ['nullable', 'string', 'max:255'],
            'shipping.address_line_2' => ['nullable', 'string', 'max:255'],
            'shipping.city' => ['nullable', 'string', 'max:120'],
            'shipping.state' => ['nullable', 'string', 'max:120'],
            'shipping.postal_code' => ['nullable', 'string', 'max:40'],
            'shipping.country' => ['nullable', 'string', 'max:120'],
        ]);

        $shippingPayload = $this->normalizeAddressPayload($validated['shipping'] ?? []);
        if ($shippingPayload === [] && ! empty($validated['customer_address'])) {
            $shippingPayload = [
                'address_line_1' => trim((string) $validated['customer_address']),
            ];
        }

        $order->update([
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'] ?? null,
            'customer_phone' => $validated['customer_phone'] ?? null,
            'customer_whatsapp' => $validated['customer_whatsapp'] ?? null,
            'customer_address' => $validated['customer_address'] ?? null,
            'customer_notes' => $validated['customer_notes'] ?? null,
            'shipping_address' => $shippingPayload,
        ]);

        ActivityLogger::log('updated', 'order', $order->id, ['section' => 'details']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('Invoice and customer details updated.'),
                'saved_at' => now()->toIso8601String(),
            ]);
        }

        return redirect()
            ->route('admin.orders.show', [
                'locale' => app()->getLocale(),
                'order' => $order,
            ])
            ->with('success', __('Invoice and customer details updated.'));
    }

    public function updateItems(Request $request, Order $order): RedirectResponse
    {
        $editableStatuses = [
            Order::STATUS_PENDING,
            Order::STATUS_APPROVED,
        ];

        if (! in_array($order->normalized_status, $editableStatuses, true)) {
            return back()
                ->withInput()
                ->withErrors([
                    'items' => __('Invoice items can only be edited while the order is pending or approved.'),
                ]);
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:999999'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ]);

        $itemsPayload = collect($validated['items'])->values();
        $duplicateIds = $itemsPayload->pluck('id')->filter()->duplicates();
        if ($duplicateIds->isNotEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['items' => __('Duplicate invoice item rows are not allowed.')]);
        }

        $existingItemIds = $order->items()->pluck('id')->all();
        $existingIdLookup = array_flip($existingItemIds);

        foreach ($itemsPayload as $line) {
            if (! empty($line['id']) && ! isset($existingIdLookup[(int) $line['id']])) {
                return back()
                    ->withInput()
                    ->withErrors(['items' => __('One of the invoice items is invalid.')]);
                }
        }

        $inventory = app(InventoryService::class);
        $defaultWarehouseId = $inventory->resolveDefaultWarehouseId();
        $currentQtyByProduct = $order->items()
            ->selectRaw('product_id, SUM(qty) as qty_sum')
            ->groupBy('product_id')
            ->pluck('qty_sum', 'product_id')
            ->map(fn ($qty) => (int) $qty);
        $requestedQtyByProduct = $itemsPayload
            ->groupBy(fn ($line) => (int) $line['product_id'])
            ->map(fn ($lines) => (int) collect($lines)->sum(fn ($line) => (int) $line['qty']));
        $affectedProductIds = $requestedQtyByProduct
            ->keys()
            ->merge($currentQtyByProduct->keys())
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();
        $stockDeltaByProduct = collect();

        foreach ($affectedProductIds as $productId) {
            $requestedQty = (int) ($requestedQtyByProduct->get($productId) ?? 0);
            $currentQty = (int) ($currentQtyByProduct->get($productId) ?? 0);
            $delta = $requestedQty - $currentQty;

            if ($delta !== 0) {
                $stockDeltaByProduct->put($productId, $delta);
            }
        }

        try {
            DB::transaction(function () use (
                $order,
                $itemsPayload,
                $existingItemIds,
                $inventory,
                $defaultWarehouseId,
                $stockDeltaByProduct
            ): void {
                $adjustedProducts = Product::query()
                    ->whereIn('id', $stockDeltaByProduct->keys()->all())
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($stockDeltaByProduct as $productId => $delta) {
                    $productId = (int) $productId;
                    $delta = (int) $delta;
                    /** @var \App\Models\Product|null $product */
                    $product = $adjustedProducts->get($productId);

                    if ($delta > 0) {
                        if (! $product || ! $product->is_active) {
                            throw ValidationException::withMessages([
                                'items' => __('One of the selected products is inactive and cannot be used.'),
                            ]);
                        }

                        $inventory->moveStock(
                            $productId,
                            $defaultWarehouseId,
                            -1 * $delta,
                            'Order ' . $order->order_no . ' invoice items adjusted by admin'
                        );
                    } elseif ($delta < 0) {
                        $inventory->moveStock(
                            $productId,
                            $defaultWarehouseId,
                            abs($delta),
                            'Order ' . $order->order_no . ' invoice items adjusted by admin (restock)'
                        );
                    }
                }

                $keptIds = [];

                foreach ($itemsPayload as $line) {
                    $productId = (int) $line['product_id'];
                    $qty = (int) $line['qty'];
                    $price = round((float) $line['price'], 2);
                    $lineTotal = round($qty * $price, 2);

                    if (! empty($line['id'])) {
                        $lineId = (int) $line['id'];
                        $keptIds[] = $lineId;

                        $order->items()->whereKey($lineId)->update([
                            'product_id' => $productId,
                            'qty' => $qty,
                            'price' => $price,
                            'cost' => $price,
                            'line_total' => $lineTotal,
                        ]);

                        continue;
                    }

                    $item = $order->items()->create([
                        'product_id' => $productId,
                        'qty' => $qty,
                        'price' => $price,
                        'cost' => $price,
                        'line_total' => $lineTotal,
                    ]);
                    $keptIds[] = $item->id;
                }

                $idsToDelete = array_values(array_diff($existingItemIds, $keptIds));
                if ($idsToDelete !== []) {
                    $order->items()->whereIn('id', $idsToDelete)->delete();
                }

                $order->load('items');
                $order->recalculateTotals();
                $order->save();
            });
        } catch (StockConflictException $exception) {
            return back()
                ->withInput()
                ->withErrors([
                    'items' => __('Insufficient stock for one or more items.'),
                ]);
        } catch (ValidationException $exception) {
            throw $exception;
        }

        ActivityLogger::log('updated', 'order', $order->id, ['section' => 'items']);

        return redirect()
            ->route('admin.orders.show', [
                'locale' => app()->getLocale(),
                'order' => $order,
            ])
            ->with('success', __('Invoice items updated.'));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $nextStatus = (string) $request->validated('status');

        if (! $order->transitionTo($nextStatus)) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'status' => __('Invalid status transition from :from to :to.', [
                        'from' => __(ucfirst($order->normalized_status)),
                        'to' => __(ucfirst(Order::normalizeStatus($nextStatus))),
                    ]),
                ]);
        }

        $order->save();

        ActivityLogger::log('updated', 'order', $order->id, ['status' => $order->status]);

        return redirect()
            ->route('admin.orders.show', [
                'locale' => app()->getLocale(),
                'order' => $order,
            ])
            ->with('success', __('Order status updated.'));
    }

    /**
     * Remove empty address keys before persisting.
     *
     * @param array<string, mixed> $address
     * @return array<string, string>
     */
    private function normalizeAddressPayload(array $address): array
    {
        return collect($address)
            ->mapWithKeys(function ($value, $key): array {
                return [(string) $key => trim((string) $value)];
            })
            ->filter(fn(string $value) => $value !== '')
            ->all();
    }
}
