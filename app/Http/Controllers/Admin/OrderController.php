<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    /**
     * Display a listing of the orders.
     */
    public function index(Request $request): View
    {
        $this->authorizeView();

        $query = Order::query()
            ->with(['partner', 'creator'])
            ->orderByDesc('created_at');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($paymentStatus = $request->string('payment_status')->toString()) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($partnerId = $request->integer('partner_id')) {
            $query->where('partner_id', $partnerId);
        }

        if ($from = $request->date('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->date('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $orders = $query
            ->paginate(15)
            ->withQueryString();

        $partners = Partner::query()
            ->orderBy('name')
            ->get();

        return view('admin.orders.index', [
            'orders' => $orders,
            'partners' => $partners,
            'filters' => $request->only(['status', 'payment_status', 'partner_id', 'from', 'to']),
        ]);
    }

    /**
     * Show the form for creating a new order.
     */
    public function create(): View
    {
        $this->authorizeCreate();

        $partners = Partner::query()->orderBy('name')->get();
        $products = Product::query()->orderBy('name_ar')->get();

        return view('admin.orders.create', [
            'partners' => $partners,
            'products' => $products,
        ]);
    }

    /**
     * Store a newly created order.
     */
    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $this->authorizeCreate();

        $validated = $request->validated();
        $itemsData = $validated['items'];

        $products = Product::query()
            ->whereIn('id', collect($itemsData)->pluck('product_id'))
            ->get()
            ->keyBy('id');

        $payload = $this->buildItemsPayload($itemsData, $products);
        $qtyMap = $this->buildQtyMapFromPayload($payload);

        $order = DB::transaction(function () use ($validated, $payload, $qtyMap): Order {
            $order = Order::query()->create([
                'order_no' => $this->generateOrderNumber(),
                'partner_id' => $validated['partner_id'],
                'status' => $validated['status'],
                'payment_status' => $validated['payment_status'],
                'created_by' => Auth::id(),
            ]);

            foreach ($payload as $item) {
                $order->items()->create($item);
            }

            $order->load('items');
            $order->recalculateTotals();
            $order->save();

            if ($this->shouldAdjustStock($order->status)) {
                $this->applyStockDelta($qtyMap, $order, __('Order created'));
            }

            return $order;
        });

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', __('Order created successfully.'));
    }

    /**
     * Display the specified order details.
     */
    public function show(Order $order): View
    {
        $this->authorizeView();

        $order->load(['items.product', 'partner', 'payments', 'creator']);

        return view('admin.orders.show', [
            'order' => $order,
        ]);
    }

    /**
     * Show the form for editing the specified order.
     */
    public function edit(Order $order): View
    {
        $this->authorizeUpdate();

        $order->load(['items.product', 'partner']);
        $partners = Partner::query()->orderBy('name')->get();
        $products = Product::query()->orderBy('name_ar')->get();

        return view('admin.orders.edit', [
            'order' => $order,
            'partners' => $partners,
            'products' => $products,
        ]);
    }

    /**
     * Update the specified order.
     */
    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $this->authorizeUpdate();

        $validated = $request->validated();
        $itemsData = $validated['items'];

        $products = Product::query()
            ->whereIn('id', collect($itemsData)->pluck('product_id'))
            ->get()
            ->keyBy('id');

        $payload = $this->buildItemsPayload($itemsData, $products);

        $oldQtyMap = $this->buildQtyMapFromItems($order->items()->get());
        $newQtyMap = $this->buildQtyMapFromPayload($payload);

        DB::transaction(function () use ($order, $validated, $payload, $oldQtyMap, $newQtyMap): void {
            $originalStatus = $order->status;

            $order->update([
                'partner_id' => $validated['partner_id'],
                'status' => $validated['status'],
                'payment_status' => $validated['payment_status'],
            ]);

            $order->items()->delete();
            foreach ($payload as $item) {
                $order->items()->create($item);
            }

            $order->load('items');
            $order->recalculateTotals();
            $order->save();

            $shouldStock = $this->shouldAdjustStock($order->status);
            $wasStocked = $this->shouldAdjustStock($originalStatus);

            if ($shouldStock) {
                if ($wasStocked) {
                    $delta = $this->mergeQtyDelta($newQtyMap, $oldQtyMap);
                } else {
                    $delta = $newQtyMap;
                }

                $this->applyStockDelta($delta, $order, __('Order updated'));
            }
        });

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', __('Order updated successfully.'));
    }

    /**
     * Store a payment for the order.
     */
    public function storePayment(StorePaymentRequest $request, Order $order): RedirectResponse
    {
        $this->authorizeUpdate();

        $validated = $request->validated();

        Payment::query()->create([
            'order_id' => $order->id,
            'amount' => $validated['amount'],
            'method' => $validated['method'] ?? 'cash',
            'paid_at' => $validated['paid_at'] ?? now(),
        ]);

        $this->refreshPaymentStatus($order);

        return back()->with('status', __('Payment added successfully.'));
    }

    /**
     * Print-friendly invoice view.
     */
    public function invoice(Order $order): View
    {
        $this->authorizeView();

        $order->load(['items.product', 'partner', 'payments']);

        return view('admin.orders.invoice', [
            'order' => $order,
        ]);
    }

    /**
     * Update the status of the order.
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeChangeStatus();

        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', [
                Order::STATUS_PENDING,
                Order::STATUS_CONFIRMED,
                Order::STATUS_SHIPPED,
                Order::STATUS_COMPLETED,
                Order::STATUS_CANCELED,
            ])],
        ]);

        $originalStatus = $order->status;
        $order->status = $validated['status'];
        $order->save();

        if (! $this->shouldAdjustStock($originalStatus) && $this->shouldAdjustStock($order->status)) {
            $qtyMap = $this->buildQtyMapFromItems($order->items()->get());
            $this->applyStockDelta($qtyMap, $order, __('Order status update'));
        }

        return back()->with('status', __('Order status updated.'));
    }

    /**
     * Update payment status (MVP).
     */
    public function updatePaymentStatus(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeChangeStatus();

        $validated = $request->validate([
            'payment_status' => ['required', 'in:' . implode(',', [
                Order::PAYMENT_UNPAID,
                Order::PAYMENT_PARTIAL,
                Order::PAYMENT_PAID,
            ])],
        ]);

        $order->payment_status = $validated['payment_status'];
        $order->save();

        return back()->with('status', __('Payment status updated.'));
    }

    protected function authorizeView(): void
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        abort_unless($user && $user->can('orders.view'), 403);
    }

    protected function authorizeCreate(): void
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        abort_unless($user && $user->can('orders.create'), 403);
    }

    protected function authorizeUpdate(): void
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        abort_unless($user && $user->can('orders.update'), 403);
    }

    protected function authorizeChangeStatus(): void
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        abort_unless($user && $user->can('orders.change_status'), 403);
    }

    protected function shouldAdjustStock(string $status): bool
    {
        return in_array($status, [Order::STATUS_CONFIRMED, Order::STATUS_COMPLETED], true);
    }

    /**
     * @param array<int, array<string, mixed>> $itemsData
     * @param \Illuminate\Support\Collection<int, \App\Models\Product> $products
     * @return array<int, array<string, mixed>>
     */
    protected function buildItemsPayload(array $itemsData, Collection $products): array
    {
        return collect($itemsData)->map(function (array $item) use ($products): array {
            /** @var \App\Models\Product $product */
            $product = $products->get($item['product_id']);
            $qty = (int) $item['qty'];
            $price = (float) $item['price'];
            $cost = (float) ($product?->cost ?? 0);
            $lineTotal = $qty * $price;

            return [
                'product_id' => $product->id,
                'qty' => $qty,
                'price' => $price,
                'cost' => $cost,
                'line_total' => $lineTotal,
            ];
        })->values()->all();
    }

    /**
     * @param \Illuminate\Support\Collection<int, \App\Models\OrderItem> $items
     * @return array<int, int>
     */
    protected function buildQtyMapFromItems(Collection $items): array
    {
        return $items
            ->groupBy('product_id')
            ->map(fn (Collection $group) => (int) $group->sum('qty'))
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $payload
     * @return array<int, int>
     */
    protected function buildQtyMapFromPayload(array $payload): array
    {
        return collect($payload)
            ->groupBy('product_id')
            ->map(fn (Collection $group) => (int) $group->sum('qty'))
            ->all();
    }

    /**
     * @param array<int, int> $newQty
     * @param array<int, int> $oldQty
     * @return array<int, int>
     */
    protected function mergeQtyDelta(array $newQty, array $oldQty): array
    {
        $productIds = collect(array_keys($newQty))
            ->merge(array_keys($oldQty))
            ->unique();

        $delta = [];

        foreach ($productIds as $productId) {
            $delta[$productId] = ($newQty[$productId] ?? 0) - ($oldQty[$productId] ?? 0);
        }

        return $delta;
    }

    /**
     * @param array<int, int> $deltaByProduct
     */
    protected function applyStockDelta(array $deltaByProduct, Order $order, string $reason): void
    {
        $warehouse = Warehouse::query()->orderBy('id')->first();

        if (! $warehouse) {
            throw ValidationException::withMessages([
                'warehouse_id' => __('Please create a warehouse before adjusting stock.'),
            ]);
        }

        foreach ($deltaByProduct as $productId => $delta) {
            if ($delta === 0) {
                continue;
            }

            /** @var \App\Models\Product $product */
            $product = Product::query()->findOrFail($productId);
            $stock = ProductStock::query()->firstOrCreate(
                ['product_id' => $productId, 'warehouse_id' => $warehouse->id],
                ['qty' => 0],
            );

            if ($delta > 0 && $stock->qty < $delta) {
                throw ValidationException::withMessages([
                    'items' => __('Insufficient stock for :product.', ['product' => $product->name]),
                ]);
            }

            $direction = $delta > 0 ? 'out' : 'in';
            $qty = abs($delta);

            $stock->qty = $stock->qty + ($direction === 'out' ? -$qty : $qty);
            $stock->save();

            if ($direction === 'out') {
                $product->decrement('stock', $qty);
            } else {
                $product->increment('stock', $qty);
            }

            StockMovement::query()->create([
                'product_id' => $productId,
                'warehouse_id' => $warehouse->id,
                'direction' => $direction,
                'qty' => $qty,
                'reason' => trim($reason . ' - ' . $order->order_no),
            ]);
        }
    }

    protected function generateOrderNumber(): string
    {
        do {
            $orderNo = sprintf('ORD-%s-%s', now()->format('Ymd'), Str::upper(Str::random(4)));
        } while (Order::query()->where('order_no', $orderNo)->exists());

        return $orderNo;
    }

    protected function refreshPaymentStatus(Order $order): void
    {
        $paid = (float) $order->payments()->sum('amount');
        $total = (float) $order->total;

        if ($paid <= 0) {
            $order->payment_status = Order::PAYMENT_UNPAID;
        } elseif ($paid >= $total) {
            $order->payment_status = Order::PAYMENT_PAID;
        } else {
            $order->payment_status = Order::PAYMENT_PARTIAL;
        }

        $order->save();
    }
}
