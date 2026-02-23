<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\StockConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLedgerPaymentRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Models\Product;
use App\Services\CustomerLedgerService;
use App\Services\InventoryService;
use App\Support\ActivityLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        [$search, $status, $perPage] = $this->resolveIndexFilters($request);

        $orders = $this->buildOrdersIndexQuery($search, $status)
            ->paginate($perPage)
            ->withQueryString();

        $tableStats = $this->buildOrdersTableStats();

        return view('admin.orders.index', compact('orders', 'status', 'search', 'perPage', 'tableStats'));
    }

    public function live(Request $request): JsonResponse
    {
        [$search, $status, $perPage] = $this->resolveIndexFilters($request);

        $orders = $this->buildOrdersIndexQuery($search, $status)
            ->limit($perPage)
            ->get();

        $latestOrderId = (int) ($orders->first()?->id ?? 0);
        $sinceId = max(0, (int) $request->query('since_id', 0));
        $newOrdersCount = $sinceId > 0
            ? $orders->filter(fn (Order $order): bool => (int) $order->id > $sinceId)->count()
            : 0;
        $signature = hash(
            'sha256',
            $orders
                ->map(fn (Order $order): string => implode(':', [
                    (string) $order->id,
                    (string) optional($order->updated_at)->timestamp,
                    (string) $order->status,
                    (string) $order->total,
                ]))
                ->implode('|')
        );

        return response()->json([
            'rows_html' => view('admin.orders.partials.table-rows', [
                'orders' => $orders,
            ])->render(),
            'latest_order_id' => $latestOrderId,
            'new_orders_count' => $newOrdersCount,
            'signature' => $signature,
            'fetched_at' => now()->toIso8601String(),
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['customer', 'items.product', 'payments.allocations', 'paymentAllocations.payment']);
        $normalizedStatus = Order::normalizeStatus((string) $order->getAttribute('status'));
        $orderNo = (string) $order->getAttribute('order_no');
        /** @var \Illuminate\Support\Carbon|string|null $orderCreatedAt */
        $orderCreatedAt = $order->getAttribute('created_at');
        /** @var \Illuminate\Support\Carbon|string|null $orderUpdatedAt */
        $orderUpdatedAt = $order->getAttribute('updated_at');
        $timelineTime = $orderUpdatedAt !== null ? Carbon::parse((string) $orderUpdatedAt) : null;
        $products = Product::query()
            ->select(['id', 'name', 'price', 'sku'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $paidPayment = $order->paymentAllocations
            ->map(fn ($allocation) => $allocation->payment)
            ->filter(fn ($payment) => $payment && $payment->status === 'paid')
            ->sortByDesc(fn ($payment) => $payment->paid_at ?? $payment->created_at)
            ->first()
            ?? $order->payments
                ->where('status', 'paid')
                ->sortByDesc('paid_at')
                ->first();

        $timeline = [
            [
                'title' => __('Order was placed'),
                'subtitle' => __('Order :no was created.', ['no' => $orderNo]),
                'time' => $orderCreatedAt !== null ? Carbon::parse((string) $orderCreatedAt) : null,
                'type' => 'primary',
            ],
        ];

        if ($paidPayment) {
            $timeline[] = [
                'title' => __('Payment received'),
                'subtitle' => __('Payment :method for :amount', [
                    'method' => ucfirst($paidPayment->method),
                    'amount' => money($paidPayment->amount),
                ]),
                'time' => $paidPayment->paid_at ?? $timelineTime,
                'type' => 'success',
            ];
        }

        if ($normalizedStatus === Order::STATUS_SHIPPED) {
            $timeline[] = [
                'title' => __('Dispatched'),
                'subtitle' => __('Package has been picked up by courier'),
                'time' => $timelineTime,
                'type' => 'info',
            ];
        }

        if ($normalizedStatus === Order::STATUS_DELIVERED) {
            $timeline[] = [
                'title' => __('Delivered'),
                'subtitle' => __('Package has been delivered to customer'),
                'time' => $timelineTime,
                'type' => 'success',
            ];
        }

        if ($normalizedStatus === Order::STATUS_RETURNED) {
            $timeline[] = [
                'title' => __('Returned'),
                'subtitle' => __('Order has been returned'),
                'time' => $timelineTime,
                'type' => 'secondary',
            ];
        }

        if ($normalizedStatus === Order::STATUS_CANCELLED) {
            $timeline[] = [
                'title' => __('Cancelled'),
                'subtitle' => __('Order was cancelled'),
                'time' => $timelineTime,
                'type' => 'danger',
            ];
        }

        $availableStatusTransitions = collect($order->availableTransitions())
            ->prepend($normalizedStatus)
            ->values()
            ->all();

        $customerOrdersCount = $order->customer?->orders()->count() ?? 0;
        $rawShippingAddress = $order->getAttribute('shipping_address');
        $shippingAddress = is_array($rawShippingAddress) ? $rawShippingAddress : [];
        $customer = $order->customer;
        $customerDetails = [
            'name' => $customer?->name ?? '',
            'email' => $customer?->email ?? '',
            'phone' => $customer?->phone ?? '',
            'city' => $customer?->city ?? ($shippingAddress['city'] ?? ''),
            'whatsapp' => $customer?->whatsapp ?? '',
            'address' => $customer?->address ?? '',
            'notes' => $order->customer_notes ?? '',
        ];
        if (($shippingAddress['address_line_1'] ?? '') === '' && $customerDetails['address'] !== '') {
            $shippingAddress['address_line_1'] = $customerDetails['address'];
        }
        if (($shippingAddress['city'] ?? '') === '' && $customerDetails['city'] !== '') {
            $shippingAddress['city'] = $customerDetails['city'];
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
        $order->loadMissing('customer');
        $customer = $order->customer;
        $customerId = $customer?->id;
        $emailUnique = Rule::unique('customers', 'email')->whereNull('deleted_at');
        $phoneUnique = Rule::unique('customers', 'phone')->whereNull('deleted_at');
        if ($customerId !== null) {
            $emailUnique->ignore($customerId);
            $phoneUnique->ignore($customerId);
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255', $emailUnique],
            'customer_phone' => ['nullable', 'string', 'max:50', $phoneUnique],
            'customer_city' => ['nullable', 'string', 'max:120'],
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
        if (($shippingPayload['city'] ?? '') === '' && ! empty($validated['customer_city'])) {
            $shippingPayload['city'] = trim((string) $validated['customer_city']);
        }

        if ($customer) {
            $customer->update([
                'name' => $validated['customer_name'],
                'email' => $validated['customer_email'] ?? null,
                'phone' => $validated['customer_phone'] ?? null,
                'city' => $validated['customer_city'] ?? null,
                'whatsapp' => $validated['customer_whatsapp'] ?? null,
                'address' => $validated['customer_address'] ?? null,
            ]);
        }

        $order->update([
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
        $editableGuard = $this->guardOrderItemsEditable($order);
        if ($editableGuard !== null) {
            return $editableGuard;
        }

        $itemsPayload = $this->validatedItemsPayload($request);
        $existingItemIds = $order->items()->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $itemsGuard = $this->ensureItemsBelongToOrder($itemsPayload, $existingItemIds);
        if ($itemsGuard !== null) {
            return $itemsGuard;
        }

        $inventory = app(InventoryService::class);
        $defaultWarehouseId = $inventory->resolveDefaultWarehouseId();
        $stockDeltaByProduct = $this->calculateStockDeltaByProduct($order, $itemsPayload);

        try {
            DB::transaction(function () use ($order, $itemsPayload, $existingItemIds, $inventory, $defaultWarehouseId, $stockDeltaByProduct): void {
                $this->applyStockAdjustments($order, $inventory, $defaultWarehouseId, $stockDeltaByProduct);
                $this->persistOrderItems($order, $itemsPayload, $existingItemIds);

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

    private function guardOrderItemsEditable(Order $order): ?RedirectResponse
    {
        $editableStatuses = [
            Order::STATUS_PENDING,
            Order::STATUS_APPROVED,
        ];
        $normalizedStatus = Order::normalizeStatus((string) $order->getAttribute('status'));

        if (in_array($normalizedStatus, $editableStatuses, true)) {
            return null;
        }

        return back()
            ->withInput()
            ->withErrors([
                'items' => __('Invoice items can only be edited while the order is pending or approved.'),
            ]);
    }

    /**
     * @return Collection<int, array{id?: int|null, product_id: int, qty: int, price: float|int|string}>
     */
    private function validatedItemsPayload(Request $request): Collection
    {
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
            throw ValidationException::withMessages([
                'items' => __('Duplicate invoice item rows are not allowed.'),
            ]);
        }

        return $itemsPayload;
    }

    /**
     * @param Collection<int, array{id?: int|null, product_id: int, qty: int, price: float|int|string}> $itemsPayload
     * @param array<int, int> $existingItemIds
     */
    private function ensureItemsBelongToOrder(Collection $itemsPayload, array $existingItemIds): ?RedirectResponse
    {
        $existingIdLookup = array_flip($existingItemIds);

        foreach ($itemsPayload as $line) {
            if (! empty($line['id']) && ! isset($existingIdLookup[(int) $line['id']])) {
                return back()
                    ->withInput()
                    ->withErrors(['items' => __('One of the invoice items is invalid.')]);
            }
        }

        return null;
    }

    /**
     * @param Collection<int, array{id?: int|null, product_id: int, qty: int, price: float|int|string}> $itemsPayload
     * @return Collection<int, int>
     */
    private function calculateStockDeltaByProduct(Order $order, Collection $itemsPayload): Collection
    {
        $currentQtyByProduct = $order->items()
            ->selectRaw('product_id, SUM(qty) as qty_sum')
            ->groupBy('product_id')
            ->pluck('qty_sum', 'product_id')
            ->map(fn ($qty): int => (int) $qty);

        $requestedQtyByProduct = $itemsPayload
            ->groupBy(fn (array $line): int => (int) $line['product_id'])
            ->map(fn (Collection $lines): int => (int) $lines->sum(fn (array $line): int => (int) $line['qty']));

        $affectedProductIds = $requestedQtyByProduct
            ->keys()
            ->merge($currentQtyByProduct->keys())
            ->unique()
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        $stockDeltaByProduct = collect();
        foreach ($affectedProductIds as $productId) {
            $requestedQty = (int) ($requestedQtyByProduct->get($productId) ?? 0);
            $currentQty = (int) ($currentQtyByProduct->get($productId) ?? 0);
            $delta = $requestedQty - $currentQty;
            if ($delta !== 0) {
                $stockDeltaByProduct->put((int) $productId, (int) $delta);
            }
        }

        /** @var Collection<int, int> $stockDeltaByProduct */
        return $stockDeltaByProduct;
    }

    /**
     * @param Collection<int, int> $stockDeltaByProduct
     */
    private function applyStockAdjustments(
        Order $order,
        InventoryService $inventory,
        int $defaultWarehouseId,
        Collection $stockDeltaByProduct
    ): void {
        $orderNo = (string) $order->getAttribute('order_no');
        $adjustedProducts = Product::query()
            ->whereIn('id', $stockDeltaByProduct->keys()->all())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($stockDeltaByProduct as $productId => $delta) {
            $productId = (int) $productId;
            $delta = (int) $delta;
            /** @var Product|null $product */
            $product = $adjustedProducts->get($productId);
            $isActive = $product ? (bool) $product->getAttribute('is_active') : false;

            if ($delta > 0) {
                if (! $product || ! $isActive) {
                    throw ValidationException::withMessages([
                        'items' => __('One of the selected products is inactive and cannot be used.'),
                    ]);
                }

                $inventory->moveStock(
                    $productId,
                    $defaultWarehouseId,
                    -1 * $delta,
                    'Order ' . $orderNo . ' invoice items adjusted by admin'
                );
            } elseif ($delta < 0) {
                $inventory->moveStock(
                    $productId,
                    $defaultWarehouseId,
                    abs($delta),
                    'Order ' . $orderNo . ' invoice items adjusted by admin (restock)'
                );
            }
        }
    }

    /**
     * @param Collection<int, array{id?: int|null, product_id: int, qty: int, price: float|int|string}> $itemsPayload
     * @param array<int, int> $existingItemIds
     */
    private function persistOrderItems(Order $order, Collection $itemsPayload, array $existingItemIds): void
    {
        $existingItemsById = $order->items()
            ->whereIn('id', $existingItemIds)
            ->get(['id', 'product_id', 'base_price'])
            ->keyBy('id');
        $productPrices = Product::query()
            ->whereIn('id', $itemsPayload->pluck('product_id')->map(fn ($id): int => (int) $id)->unique()->all())
            ->pluck('price', 'id');

        $keptIds = [];
        foreach ($itemsPayload as $line) {
            $productId = (int) $line['product_id'];
            $qty = (int) $line['qty'];
            $price = round((float) $line['price'], 2);
            $basePrice = max(0, round((float) ($productPrices->get($productId) ?? $price), 2));
            $lineTotal = round($qty * $price, 2);

            if (! empty($line['id'])) {
                $lineId = (int) $line['id'];
                $keptIds[] = $lineId;
                $existingItem = $existingItemsById->get($lineId);
                if ($existingItem && (int) $existingItem->getAttribute('product_id') === $productId) {
                    $existingBasePrice = $existingItem->getAttribute('base_price');
                    if ($existingBasePrice !== null) {
                        $basePrice = max(0, round((float) $existingBasePrice, 2));
                    }
                }

                $order->items()->whereKey($lineId)->update([
                    'product_id' => $productId,
                    'qty' => $qty,
                    'price' => $price,
                    'base_price' => $basePrice,
                    'cost' => $price,
                    'line_total' => $lineTotal,
                ]);

                continue;
            }

            $item = $order->items()->create([
                'product_id' => $productId,
                'qty' => $qty,
                'price' => $price,
                'base_price' => $basePrice,
                'cost' => $price,
                'line_total' => $lineTotal,
            ]);
            $keptIds[] = (int) $item->id;
        }

        $idsToDelete = array_values(array_diff($existingItemIds, $keptIds));
        if ($idsToDelete !== []) {
            $order->items()->whereIn('id', $idsToDelete)->delete();
        }
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $previousStatus = Order::normalizeStatus((string) $order->getAttribute('status'));
        $nextStatus = (string) $request->validated('status');

        if (! $order->transitionTo($nextStatus)) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'status' => __('Invalid status transition from :from to :to.', [
                        'from' => __(ucfirst($previousStatus)),
                        'to' => __(ucfirst(Order::normalizeStatus($nextStatus))),
                    ]),
                ]);
        }

        $order->save();
        $currentStatus = Order::normalizeStatus((string) $order->getAttribute('status'));

        if (
            $previousStatus !== Order::STATUS_CANCELLED
            && $currentStatus === Order::STATUS_CANCELLED
        ) {
            app(CustomerLedgerService::class)->releaseCancelledOrderAllocations($order);
        }

        if (
            $previousStatus === Order::STATUS_CANCELLED
            && $currentStatus !== Order::STATUS_CANCELLED
            && $order->customer
        ) {
            app(CustomerLedgerService::class)->autoApplyCreditOnNewDue($order->customer);
        }

        ActivityLogger::log('updated', 'order', $order->id, ['status' => (string) $order->getAttribute('status')]);

        return redirect()
            ->route('admin.orders.show', [
                'locale' => app()->getLocale(),
                'order' => $order,
            ])
            ->with('success', __('Order status updated.'));
    }

    public function storePayment(
        StoreLedgerPaymentRequest $request,
        Order $order
    ): RedirectResponse {
        $validated = $request->validated();
        $ledgerService = app(CustomerLedgerService::class);
        $authIdentifier = $request->user()?->getAuthIdentifier();
        $createdBy = is_numeric($authIdentifier) ? (int) $authIdentifier : null;

        $payment = $ledgerService->postOrderPayment(
            $order,
            (float) $validated['amount'],
            (string) $validated['method'],
            isset($validated['paid_at']) ? Carbon::parse((string) $validated['paid_at']) : null,
            isset($validated['notes']) ? (string) $validated['notes'] : null,
            $createdBy
        );

        ActivityLogger::log('created', 'payment', $payment->id, [
            'source' => 'order_edit',
            'order_id' => $order->id,
            'amount' => (float) $payment->amount,
        ]);

        return redirect()
            ->route('admin.orders.show', [
                'locale' => app()->getLocale(),
                'order' => $order,
            ])
            ->with('success', __('Payment added successfully.'));
    }

    /**
     * @return array{0:?string,1:?string,2:int}
     */
    private function resolveIndexFilters(Request $request): array
    {
        $search = trim((string) $request->query('q', ''));
        if ($search === '') {
            $search = null;
        }

        $rawStatus = $request->query('status');
        $status = is_string($rawStatus) && $rawStatus !== ''
            ? Order::normalizeStatus($rawStatus)
            : null;

        $perPage = (int) $request->query('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50], true) ? $perPage : 10;

        return [$search, $status, $perPage];
    }

    private function buildOrdersIndexQuery(?string $search, ?string $status): Builder
    {
        return Order::query()
            ->with([
                'customer',
                'items:id,order_id,qty,price,base_price',
            ])
            ->withSum('paymentAllocations as paid_amount', 'amount')
            ->when($search, function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('order_no', 'like', '%' . $search . '%')
                        ->orWhereHas('customer', function (Builder $customerQuery) use ($search): void {
                            $customerQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->latest();
    }

    /**
     * @return array<int, array{value:string,label:string,icon:string}>
     */
    private function buildOrdersTableStats(): array
    {
        return [
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
