<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\StockConflictException;
use App\Http\Requests\Api\V1\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Product;
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
        if ((int) $order->customer_id !== (int) $request->user()->id) {
            throw new AuthorizationException('You are not allowed to access this order.');
        }

        $order->loadMissing(['customer', 'items.product', 'payments']);

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
                $existingOrder->load(['customer', 'items.product', 'payments']);

                return $this->success(new OrderResource($existingOrder), 200, 'Order already exists');
            }
        }

        try {
            $order = DB::transaction(function () use ($request, $customer, $idempotencyKey): Order {
                $validated = $request->validated();
                $items = $validated['items'];
                $snapshotInput = $this->extractCheckoutContact($validated, $customer);
                $requestedByProduct = collect($items)
                    ->groupBy('productId')
                    ->map(fn($group) => collect($group)->sum(fn($line) => (int) $line['quantity']));

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

                    if (! $product || ! $product->is_active || $product->stock_qty < $requestedQty) {
                        Log::notice('api.orders.stock_conflict', [
                            'product_id' => $productId,
                            'requested' => $requestedQty,
                            'available' => $product?->stock_qty,
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
                ] + $this->customerSnapshotPayload($customer, $snapshotInput);

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
                        'cost' => $product->price,
                        'line_total' => $lineTotal,
                    ]);

                    $product->decrement('stock_qty', $qty);
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

                return $order->load(['customer', 'items.product', 'payments']);
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
                    $existingOrder->load(['customer', 'items.product', 'payments']);

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
     * @param array<string, mixed> $validated
     * @return array<string, string|null>
     */
    private function extractCheckoutContact(array $validated, object $customer): array
    {
        $checkout = is_array($validated['checkout'] ?? null) ? $validated['checkout'] : [];

        $address = $this->firstFilledString([
            $validated['address'] ?? null,
            $checkout['address'] ?? null,
            $checkout['shippingAddress'] ?? null,
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
        ]);

        return [
            'address' => $address,
            'notes' => $notes,
            'email' => $email,
            'phone' => $phone,
            'whatsapp' => $whatsapp,
        ];
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
     * @param array<string, string|null> $snapshotInput
     */
    private function customerSnapshotPayload(object $customer, array $snapshotInput): array
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

        if (in_array('customer_name', $columns, true)) {
            $payload['customer_name'] = $customer->name;
        }
        if (in_array('customer_email', $columns, true)) {
            $payload['customer_email'] = $snapshotInput['email'];
        }
        if (in_array('customer_phone', $columns, true)) {
            $payload['customer_phone'] = $snapshotInput['phone'];
        }
        if (in_array('customer_whatsapp', $columns, true)) {
            $payload['customer_whatsapp'] = $snapshotInput['whatsapp'];
        }
        if (in_array('customer_address', $columns, true)) {
            $payload['customer_address'] = $snapshotInput['address'];
        }
        if (in_array('customer_notes', $columns, true)) {
            $payload['customer_notes'] = $snapshotInput['notes'];
        }
        if (in_array('shipping_address', $columns, true) && is_string($snapshotInput['address']) && $snapshotInput['address'] !== '') {
            $payload['shipping_address'] = [
                'address_line_1' => $snapshotInput['address'],
            ];
        }

        $requiredSnapshotColumns = ['customer_name', 'customer_email', 'customer_phone'];
        $missingColumns = array_values(array_diff($requiredSnapshotColumns, $columns));

        if ($missingColumns !== []) {
            Log::warning('api.orders.snapshot_columns_missing', [
                'table' => $table,
                'missing_columns' => $missingColumns,
            ]);
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
}
