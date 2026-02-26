<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\StockConflictException;
use App\Http\Requests\Api\V1\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class OrderController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\Customer $customer */
        $customer = $request->user();
        $perPage = max(1, min(100, $request->integer('per_page', 20)));

        $orders = Order::query()
            ->where('customer_id', $customer->id)
            ->with(['customer', 'items.product'])
            ->withSum('paymentAllocations as paid_amount', 'amount')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return $this->success(
            OrderResource::collection($orders->getCollection())->resolve($request),
            200,
            'Orders fetched',
            $this->paginationMeta($orders)
        );
    }

    public function show(Order $order, Request $request): JsonResponse
    {
        $authIdentifier = $request->user()?->getAuthIdentifier();
        $orderCustomerId = $order->getAttribute('customer_id');
        if ((int) $orderCustomerId !== (int) ($authIdentifier ?? 0)) {
            throw new AuthorizationException('You are not allowed to access this order.');
        }

        $order->loadMissing(['customer', 'items.product', 'payments', 'paymentAllocations']);

        return $this->success(new OrderResource($order), 200, 'Order fetched');
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {
        $routeName = (string) $request->route()?->getName();
        $isV2OrderCreate = str_starts_with($routeName, 'api.v2.');
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));
        $idempotencyKey = $idempotencyKey !== '' ? $idempotencyKey : null;

        if ($idempotencyKey !== null && strlen($idempotencyKey) > 120) {
            return $this->error('Idempotency-Key is too long', 422, [[
                'field' => 'Idempotency-Key',
                'message' => 'Idempotency-Key must be at most 120 characters.',
            ]]);
        }

        if ($isV2OrderCreate && $idempotencyKey === null) {
            return $this->error('Idempotency-Key is required', 422, [[
                'field' => 'Idempotency-Key',
                'message' => 'Idempotency-Key header is required for /api/v2/orders.',
            ]]);
        }

        /** @var \App\Models\Customer $customer */
        $customer = $request->user();

        if ($idempotencyKey !== null && $this->ordersTableHasColumn('idempotency_key')) {
            $existingOrder = Order::query()
                ->where('customer_id', $customer->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existingOrder) {
                $existingOrder->load(['customer', 'items.product', 'payments', 'paymentAllocations']);

                return $this->success(new OrderResource($existingOrder), 200, 'Order already exists');
            }
        }

        try {
            $order = DB::transaction(function () use ($request, $customer, $idempotencyKey): Order {
                $validated = $request->validated();
                $items = $validated['items'];
                $inventory = app(InventoryService::class);
                $defaultWarehouseId = $inventory->resolveDefaultWarehouseId();
                $snapshotInput = $this->extractCheckoutContact($validated, $customer);
                $this->syncCustomerProfileFromCheckout($customer, $validated);
                $requestedByProduct = $this->buildRequestedByProduct($items);

                $products = Product::query()
                    ->whereIn('id', collect($items)->pluck('productId')->map(fn($id) => (int) $id)->all())
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($requestedByProduct as $productId => $requestedQty) {
                    $productId = (int) $productId;
                    $requestedQty = (int) $requestedQty;
                    /** @var \App\Models\Product|null $product */
                    $product = $products->get($productId);
                    $availableQty = $product
                        ? $inventory->availableInWarehouse($productId, $defaultWarehouseId)
                        : null;

                    if (! $product || ! $product->is_active || $availableQty < $requestedQty) {
                        Log::notice('api.orders.stock_conflict', [
                            'product_id' => $productId,
                            'requested' => $requestedQty,
                            'available' => $availableQty,
                            'warehouse_id' => $defaultWarehouseId,
                        ]);

                        throw new StockConflictException('Insufficient stock');
                    }
                }

                $orderPayload = [
                    'user_id' => null,
                    'customer_id' => $customer->id,
                    'order_no' => $this->generateOrderNumber(),
                    'status' => Order::STATUS_PENDING,
                    'subtotal' => 0,
                    'total' => 0,
                ] + $this->customerSnapshotPayload($snapshotInput);

                if ($idempotencyKey !== null && $this->ordersTableHasColumn('idempotency_key')) {
                    $orderPayload['idempotency_key'] = $idempotencyKey;
                }

                $order = Order::query()->create($orderPayload);

                foreach ($items as $item) {
                    $productId = (int) $item['productId'];
                    $qty = (int) $item['quantity'];
                    /** @var \App\Models\Product $product */
                    $product = $products[$productId];
                    $lineTotal = (float) $product->price * $qty;

                    $order->items()->create([
                        'product_id' => $product->id,
                        'qty' => $qty,
                        'price' => $product->price,
                        'base_price' => $product->price,
                        'cost' => $product->price,
                        'line_total' => $lineTotal,
                    ]);
                }

                foreach ($requestedByProduct as $productId => $requestedQty) {
                    $inventory->moveStock(
                        (int) $productId,
                        $defaultWarehouseId,
                        -1 * (int) $requestedQty,
                        'Order ' . $order->order_no . ' created via API'
                    );
                }

                $order->loadMissing('items');
                $order->recalculateTotals();
                $order->save();

                ActivityLog::query()->create([
                    'user_id' => null,
                    'action' => 'api.orders.created',
                    'entity_type' => Order::class,
                    'entity_id' => $order->id,
                    'meta' => [
                        'order_no' => $order->order_no,
                        'items_count' => count($items),
                    ],
                ]);

                return $order->load(['customer', 'items.product', 'payments', 'paymentAllocations']);
            });

            return $this->success(new OrderResource($order), 201, 'Order created');
        } catch (QueryException $exception) {
            if (
                $idempotencyKey !== null
                && $this->isDuplicateConstraint($exception)
                && $this->ordersTableHasColumn('idempotency_key')
            ) {
                $existingOrder = Order::query()
                    ->where('customer_id', $customer->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existingOrder) {
                    $existingOrder->load(['customer', 'items.product', 'payments', 'paymentAllocations']);

                    return $this->success(new OrderResource($existingOrder), 200, 'Order already exists');
                }
            }

            throw $exception;
        } catch (StockConflictException $exception) {
            return $this->error($exception->getMessage(), 409);
        } catch (Throwable $exception) {
            Log::error('api.orders.create_failed', [
                'customer_id' => $customer->id ?? null,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function generateOrderNumber(): string
    {
        do {
            $candidate = 'ORD-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
        } while (Order::query()->where('order_no', $candidate)->exists());

        return $candidate;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, int>
     */
    private function buildRequestedByProduct(array $items): array
    {
        $requestedByProduct = [];

        foreach ($items as $item) {
            $productId = (int) ($item['productId'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            if (! isset($requestedByProduct[$productId])) {
                $requestedByProduct[$productId] = 0;
            }

            $requestedByProduct[$productId] += $quantity;
        }

        return $requestedByProduct;
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function extractCheckoutContact(array $validated, object $customer): array
    {
        $checkout = is_array($validated['checkout'] ?? null) ? $validated['checkout'] : [];
        $name = $this->firstFilledString([
            $validated['name'] ?? null,
            $checkout['name'] ?? null,
            $customer->name ?? null,
        ]);

        $address = $this->firstFilledString([
            $validated['address'] ?? null,
            $checkout['address'] ?? null,
            $checkout['shippingAddress'] ?? null,
            $customer->address ?? null,
        ]);

        $city = $this->firstFilledString([
            $validated['city'] ?? null,
            $checkout['city'] ?? null,
            $customer->city ?? null,
        ]);

        $notes = $this->firstFilledString([
            $validated['notes'] ?? null,
            $checkout['notes'] ?? null,
        ]);

        $email = $this->firstFilledString([
            $validated['email'] ?? null,
            $checkout['email'] ?? null,
            $customer->email ?? null,
        ]);

        $phone = $this->firstFilledString([
            $validated['phone'] ?? null,
            $checkout['phone'] ?? null,
            $customer->phone ?? null,
        ]);

        $whatsapp = $this->firstFilledString([
            $validated['whatsapp'] ?? null,
            $checkout['whatsapp'] ?? null,
            $customer->whatsapp ?? null,
        ]);

        $shippingAddress = $this->normalizeAddressPayload(
            $validated['shipping_address'] ?? ($checkout['shipping_address'] ?? null),
            $address,
            $city
        );
        $shippingAddressFromText = $this->normalizeAddressPayload($checkout['shippingAddress'] ?? null, $address, $city);
        if ($shippingAddress === null && $shippingAddressFromText !== null) {
            $shippingAddress = $shippingAddressFromText;
        }

        $billingAddress = $this->normalizeAddressPayload(
            $validated['billing_address'] ?? ($checkout['billing_address'] ?? null),
            null,
            null
        );
        $billingAddressFromText = $this->normalizeAddressPayload($checkout['billingAddress'] ?? null, null, $city);
        if ($billingAddress === null && $billingAddressFromText !== null) {
            $billingAddress = $billingAddressFromText;
        }
        if ($billingAddress === null) {
            $billingAddress = $shippingAddress;
        }

        $billingPaymentMethod = $this->firstFilledString([
            $validated['billing_payment_method'] ?? null,
            $checkout['billing_payment_method'] ?? null,
        ]);

        return [
            'name' => $name,
            'address' => $address,
            'city' => $city,
            'notes' => $notes,
            'email' => $email,
            'phone' => $phone,
            'whatsapp' => $whatsapp,
            'shipping_address' => $shippingAddress,
            'billing_address' => $billingAddress,
            'billing_payment_method' => $billingPaymentMethod,
        ];
    }

    private function normalizeAddressPayload(mixed $value, ?string $fallbackAddress, ?string $fallbackCity): ?array
    {
        $addressLine = null;
        $city = null;

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed !== '') {
                $addressLine = $trimmed;
            }
        }

        if (is_array($value)) {
            $addressLine = $this->firstFilledString([
                $value['address_line_1'] ?? null,
                $value['address'] ?? null,
                $value['street'] ?? null,
            ]) ?? $addressLine;

            $city = $this->firstFilledString([
                $value['city'] ?? null,
                $value['address_city'] ?? null,
            ]) ?? $city;
        }

        $addressLine = $addressLine ?? $fallbackAddress;
        $city = $city ?? $fallbackCity;

        $payload = [];

        if (is_string($addressLine) && trim($addressLine) !== '') {
            $payload['address_line_1'] = trim($addressLine);
        }

        if (is_string($city) && trim($city) !== '') {
            $payload['city'] = trim($city);
        }

        return $payload !== [] ? $payload : null;
    }

    /**
     * @param array<mixed> $values
     */
    private function firstFilledString(array $values): ?string
    {
        foreach ($values as $value) {
            if (! is_string($value)) {
                continue;
            }

            $trimmed = trim($value);
            if ($trimmed !== '') {
                return $trimmed;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $snapshotInput
     */
    private function customerSnapshotPayload(array $snapshotInput): array
    {
        $order = new Order();
        $table = $order->getTable();

        try {
            $columns = Schema::getColumnListing($table);
        } catch (Throwable $exception) {
            Log::warning('api.orders.snapshot_columns_check_failed', [
                'table' => $table,
                'message' => $exception->getMessage(),
            ]);

            return [];
        }

        $payload = [];

        if (in_array('customer_notes', $columns, true)) {
            $payload['customer_notes'] = $snapshotInput['notes'];
        }
        if (in_array('shipping_address', $columns, true)) {
            $shippingAddressPayload = is_array($snapshotInput['shipping_address'] ?? null)
                ? $snapshotInput['shipping_address']
                : $this->normalizeAddressPayload($snapshotInput['address'] ?? null, null, $snapshotInput['city'] ?? null);

            if (is_array($shippingAddressPayload) && $shippingAddressPayload !== []) {
                $payload['shipping_address'] = $shippingAddressPayload;
            }
        }
        if (in_array('billing_address', $columns, true)) {
            $billingAddressPayload = is_array($snapshotInput['billing_address'] ?? null)
                ? $snapshotInput['billing_address']
                : null;

            if (is_array($billingAddressPayload) && $billingAddressPayload !== []) {
                $payload['billing_address'] = $billingAddressPayload;
            }
        }
        if (in_array('billing_payment_method', $columns, true)) {
            $billingPaymentMethod = $snapshotInput['billing_payment_method'] ?? null;
            if (is_string($billingPaymentMethod) && trim($billingPaymentMethod) !== '') {
                $payload['billing_payment_method'] = trim($billingPaymentMethod);
            }
        }

        return $payload;
    }

    private function ordersTableHasColumn(string $column): bool
    {
        $table = (new Order())->getTable();

        try {
            return Schema::hasColumn($table, $column);
        } catch (Throwable $exception) {
            Log::warning('api.orders.table_column_check_failed', [
                'table' => $table,
                'column' => $column,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function isDuplicateConstraint(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $driverCode = (string) ($exception->errorInfo[1] ?? '');

        return in_array($sqlState, ['23000', '23505'], true)
            || in_array($driverCode, ['1062', '19', '1555', '2067'], true);
    }

    /**
     * Persist explicit customer profile inputs on the customer record.
     * Shipping-only fields remain order-level snapshots for exceptional delivery cases.
     *
     * @param array<string, mixed> $validated
     */
    private function syncCustomerProfileFromCheckout(Customer $customer, array $validated): void
    {
        $checkout = is_array($validated['checkout'] ?? null) ? $validated['checkout'] : [];

        $name = $this->firstFilledString([
            $validated['name'] ?? null,
            $checkout['name'] ?? null,
        ]);
        $email = $this->firstFilledString([
            $validated['email'] ?? null,
            $checkout['email'] ?? null,
        ]);
        $phone = $this->firstFilledString([
            $validated['phone'] ?? null,
            $checkout['phone'] ?? null,
        ]);
        $whatsapp = $this->firstFilledString([
            $validated['whatsapp'] ?? null,
            $checkout['whatsapp'] ?? null,
        ]);
        $city = $this->firstFilledString([
            $validated['city'] ?? null,
            $checkout['city'] ?? null,
        ]);
        // Intentionally ignore shippingAddress/shipping_address when syncing customer defaults.
        $address = $this->firstFilledString([
            $validated['address'] ?? null,
            $checkout['address'] ?? null,
        ]);

        $updates = [];

        if ($name !== null && $name !== (string) $customer->name) {
            $updates['name'] = $name;
        }
        if ($email !== null) {
            $email = strtolower($email);
            $existingEmail = Customer::query()
                ->where('id', '!=', $customer->id)
                ->where('email', $email)
                ->exists();

            if (! $existingEmail && $email !== (string) $customer->email) {
                $updates['email'] = $email;
            }
        }
        if ($phone !== null) {
            $existingPhone = Customer::query()
                ->where('id', '!=', $customer->id)
                ->where('phone', $phone)
                ->exists();

            if (! $existingPhone && $phone !== (string) ($customer->phone ?? '')) {
                $updates['phone'] = $phone;
            }
        }
        if ($whatsapp !== null && $whatsapp !== (string) ($customer->whatsapp ?? '')) {
            $updates['whatsapp'] = $whatsapp;
        }
        if ($city !== null && $city !== (string) ($customer->city ?? '')) {
            $updates['city'] = $city;
        }
        if ($address !== null && $address !== (string) ($customer->address ?? '')) {
            $updates['address'] = $address;
        }

        if ($updates === []) {
            return;
        }

        try {
            $customer->forceFill($updates)->save();
        } catch (Throwable $exception) {
            Log::warning('api.orders.customer_profile_sync_failed', [
                'customer_id' => $customer->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
