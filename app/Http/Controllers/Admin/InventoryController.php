<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\StockConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryMovementRequest;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        $movements = InventoryMovement::query()
            ->with(['product', 'warehouse'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $tableStats = [
            [
                'value' => number_format(InventoryMovement::query()->count()),
                'label' => __('All Movements'),
                'icon' => 'bx-transfer',
            ],
            [
                'value' => number_format((int) InventoryMovement::query()->where('type', 'in')->sum('qty')),
                'label' => __('Stock In Qty'),
                'icon' => 'bx-log-in',
            ],
            [
                'value' => number_format((int) InventoryMovement::query()->where('type', 'out')->sum('qty')),
                'label' => __('Stock Out Qty'),
                'icon' => 'bx-log-out',
            ],
            [
                'value' => number_format(Product::query()->where('stock_qty', '<=', 10)->count()),
                'label' => __('Low Stock Products'),
                'icon' => 'bx-error-circle',
            ],
        ];

        return view('admin.catalog.inventory.index', compact('movements', 'tableStats'));
    }

    public function create(): View
    {
        $products = Product::query()->orderBy('name')->get();
        $warehouses = Warehouse::query()->orderBy('name')->get();

        return view('admin.catalog.inventory.create', compact('products', 'warehouses'));
    }

    public function store(StoreInventoryMovementRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $product = Product::query()->findOrFail($data['product_id']);
        $delta = $data['type'] === 'in' ? (int) $data['qty'] : -1 * (int) $data['qty'];

        try {
            $movement = app(InventoryService::class)->moveStock(
                (int) $data['product_id'],
                (int) $data['warehouse_id'],
                $delta,
                $data['note'] ?? null
            );
        } catch (StockConflictException) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['qty' => __('Not enough stock available.')]);
        }

        ActivityLogger::log('created', 'inventory_movement', $movement->id, [
            'type' => $data['type'],
            'qty' => $data['qty'],
            'product' => $product->name,
        ]);

        return redirect()
            ->route('admin.catalog.inventory.index')
            ->with('success', __('Inventory movement created successfully.'));
    }
}
