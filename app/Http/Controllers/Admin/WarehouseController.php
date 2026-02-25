<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Models\InventoryMovement;
use App\Models\Warehouse;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function index(): View
    {
        $warehouses = Warehouse::query()
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $tableStats = [
            [
                'value' => number_format(Warehouse::query()->count()),
                'label' => __('Total Warehouses'),
                'icon' => 'bx-buildings',
            ],
            [
                'value' => number_format(Warehouse::query()->where('is_active', true)->count()),
                'label' => __('Active Warehouses'),
                'icon' => 'bx-check-shield',
            ],
            [
                'value' => number_format(InventoryMovement::query()->count()),
                'label' => __('Inventory Movements'),
                'icon' => 'bx-transfer',
            ],
            [
                'value' => number_format((int) Warehouse::query()->distinct()->count('location')),
                'label' => __('Locations'),
                'icon' => 'bx-map-pin',
            ],
        ];

        return view('admin.operations.warehouses.index', compact('warehouses', 'tableStats'));
    }

    public function create(): View
    {
        return view('admin.operations.warehouses.create');
    }

    public function store(StoreWarehouseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = (bool) $data['is_active'];

        $warehouse = Warehouse::query()->create($data);

        ActivityLogger::log('created', 'warehouse', $warehouse->id, [
            'name' => (string) ($warehouse->getAttribute('name') ?? ''),
        ]);

        return redirect()
            ->route('admin.operations.warehouses.index')
            ->with('success', __('Warehouse created successfully.'));
    }

    public function edit(Warehouse $warehouse): View
    {
        return view('admin.operations.warehouses.edit', compact('warehouse'));
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = (bool) $data['is_active'];

        $warehouse->update($data);

        ActivityLogger::log('updated', 'warehouse', $warehouse->id, [
            'name' => (string) ($warehouse->getAttribute('name') ?? ''),
        ]);

        return redirect()
            ->route('admin.operations.warehouses.index')
            ->with('success', __('Warehouse updated successfully.'));
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        $warehouse->delete();

        ActivityLogger::log('deleted', 'warehouse', $warehouse->id);

        return redirect()
            ->route('admin.operations.warehouses.index')
            ->with('success', __('Warehouse deleted successfully.'));
    }
}
