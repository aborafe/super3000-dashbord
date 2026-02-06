<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStockTransferRequest;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    public function index(Request $request): View
    {
        $warehouses = Warehouse::query()
            ->withCount('stocks')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.warehouses.index', [
            'warehouses' => $warehouses,
        ]);
    }

    public function create(): View
    {
        return view('admin.warehouses.create');
    }

    public function store(StoreWarehouseRequest $request): RedirectResponse
    {
        Warehouse::query()->create($request->validated());

        return redirect()
            ->route('admin.warehouses.index')
            ->with('status', __('Warehouse created successfully.'));
    }

    public function show(Warehouse $warehouse): View
    {
        $stocks = ProductStock::query()
            ->with('product')
            ->where('warehouse_id', $warehouse->id)
            ->orderBy('qty')
            ->get();

        $products = Product::query()
            ->orderBy('name_ar')
            ->get();

        $targetWarehouses = Warehouse::query()
            ->where('id', '!=', $warehouse->id)
            ->orderBy('name')
            ->get();

        return view('admin.warehouses.show', [
            'warehouse' => $warehouse,
            'stocks' => $stocks,
            'products' => $products,
            'targetWarehouses' => $targetWarehouses,
        ]);
    }

    public function edit(Warehouse $warehouse): View
    {
        return view('admin.warehouses.edit', [
            'warehouse' => $warehouse,
        ]);
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $warehouse->update($request->validated());

        return redirect()
            ->route('admin.warehouses.index')
            ->with('status', __('Warehouse updated successfully.'));
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        $warehouse->delete();

        return redirect()
            ->route('admin.warehouses.index')
            ->with('status', __('Warehouse deleted successfully.'));
    }

    public function transfer(StoreStockTransferRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $validated = $request->validated();

        if ((int) $validated['from_warehouse_id'] !== $warehouse->id) {
            return back()->withErrors(['from_warehouse_id' => __('Invalid source warehouse.')]);
        }

        $qty = (int) $validated['qty'];
        $productId = (int) $validated['product_id'];
        $toWarehouseId = (int) $validated['to_warehouse_id'];

        $fromWarehouse = $warehouse;
        $toWarehouse = Warehouse::query()->findOrFail($toWarehouseId);
        $reason = $validated['reason'] ?? null;

        $fromStock = ProductStock::query()->firstOrCreate(
            ['product_id' => $productId, 'warehouse_id' => $fromWarehouse->id],
            ['qty' => 0],
        );

        if ($fromStock->qty < $qty) {
            return back()->withErrors(['qty' => __('Insufficient stock in source warehouse.')]);
        }

        DB::transaction(function () use (
            $fromWarehouse,
            $toWarehouse,
            $productId,
            $qty,
            $reason,
            $fromStock
        ): void {
            $fromStock->qty = $fromStock->qty - $qty;
            $fromStock->save();

            $toStock = ProductStock::query()->firstOrCreate(
                ['product_id' => $productId, 'warehouse_id' => $toWarehouse->id],
                ['qty' => 0],
            );
            $toStock->qty = $toStock->qty + $qty;
            $toStock->save();

            $outReason = trim(sprintf(
                'Transfer to %s%s',
                $toWarehouse->name,
                $reason ? ' - ' . $reason : ''
            ));
            $inReason = trim(sprintf(
                'Transfer from %s%s',
                $fromWarehouse->name,
                $reason ? ' - ' . $reason : ''
            ));

            StockMovement::query()->create([
                'product_id' => $productId,
                'warehouse_id' => $fromWarehouse->id,
                'direction' => 'out',
                'qty' => $qty,
                'reason' => $outReason,
            ]);

            StockMovement::query()->create([
                'product_id' => $productId,
                'warehouse_id' => $toWarehouse->id,
                'direction' => 'in',
                'qty' => $qty,
                'reason' => $inReason,
            ]);
        });

        return back()->with('status', __('Stock transferred successfully.'));
    }
}
