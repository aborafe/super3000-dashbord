<?php

namespace App\Http\Controllers\Admin;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Exceptions\StockConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Services\InventoryService;
use App\Support\ActivityLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('q');
        $status = $request->query('status');
        $categoryId = $request->query('category_id');
        $stock = $request->query('stock');
        $perPage = (int) $request->query('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50], true) ? $perPage : 10;

        $products = $this->filteredProductsQuery($request)
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $categories = Category::query()->orderBy('name')->get();

        $cashSales = Payment::query()
            ->where('status', 'paid')
            ->where('method', 'cash')
            ->sum('amount');

        $websiteSales = Payment::query()
            ->where('status', 'paid')
            ->whereIn('method', ['card', 'transfer'])
            ->sum('amount');

        $discountTotal = Order::query()
            ->selectRaw('COALESCE(SUM(subtotal - total), 0) as discount_total')
            ->value('discount_total');

        $affiliateCustomers = Customer::query()
            ->has('orders', '>', 1)
            ->count();

        $stats = [
            'cashSales' => $cashSales,
            'websiteSales' => $websiteSales,
            'discountTotal' => $discountTotal,
            'affiliateCustomers' => $affiliateCustomers,
        ];

        return view('admin.products.index', compact(
            'products',
            'categories',
            'search',
            'status',
            'categoryId',
            'stock',
            'perPage',
            'stats'
        ));
    }

    public function export(Request $request, string $format): Response|StreamedResponse
    {
        $normalizedFormat = Str::lower(trim($format));
        if (!in_array($normalizedFormat, ['csv', 'excel', 'pdf'], true)) {
            abort(404);
        }

        $products = $this->filteredProductsQuery($request)
            ->orderBy('name')
            ->get();

        $exportedAt = now();
        $fileSuffix = $exportedAt->format('Ymd_His');
        $baseName = "products_{$fileSuffix}";

        if ($normalizedFormat === 'csv') {
            return $this->streamProductsCsv($products, "{$baseName}.csv");
        }

        if ($normalizedFormat === 'excel') {
            return response()
                ->view('admin.products.exports.excel', [
                    'products' => $products,
                    'exportedAt' => $exportedAt,
                ])
                ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
                ->header('Content-Disposition', 'attachment; filename="'.$baseName.'.xls"');
        }

        return Pdf::loadView('admin.products.exports.pdf', [
            'products' => $products,
            'exportedAt' => $exportedAt,
        ])->download("{$baseName}.pdf");
    }

    public function create(): View
    {
        $categories = Category::query()->orderBy('name')->get();

        return view('admin.products.create', compact('categories'));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $providedSku = isset($data['sku']) ? trim((string) $data['sku']) : '';
        $data['sku'] = $providedSku !== '' ? $providedSku : $this->generateUniqueSku();

        $initialStock = (int) ($data['stock_qty'] ?? 0);
        $data['is_active'] = (bool) $data['is_active'];
        $data['stock_qty'] = 0;

        $product = DB::transaction(function () use ($data, $initialStock): Product {
            $created = Product::query()->create($data);

            if ($initialStock > 0) {
                $inventory = app(InventoryService::class);
                $defaultWarehouseId = $inventory->resolveDefaultWarehouseId();

                $inventory->moveStock(
                    (int) $created->id,
                    $defaultWarehouseId,
                    $initialStock,
                    'Initial stock set on product creation'
                );
            }

            return $created;
        });

        // handle cover image upload
        if ($request->hasFile('cover_image')) {
            $path = \App\Support\ImageUploader::storeProductImage($request->file('cover_image'));
            $product->cover_image = $path;
            $product->save();
        }

        // handle additional images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = \App\Support\ImageUploader::storeProductImage($file);
                $product->images()->create(['image_path' => $path]);
            }
        }

        ActivityLogger::log('created', 'product', $product->id, ['name' => $product->name]);

        return redirect()
            ->route($this->resolveIndexRouteName())
            ->with('success', __('Product created successfully.'));
    }

    public function edit(Product $product): View
    {
        $categories = Category::query()->orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        $targetStock = (int) ($data['stock_qty'] ?? 0);
        $currentStock = (int) $product->stock_qty;
        $delta = $targetStock - $currentStock;
        $data['is_active'] = (bool) $data['is_active'];
        unset($data['stock_qty']);

        if ($delta < 0) {
            try {
                $inventory = app(InventoryService::class);
                $defaultWarehouseId = $inventory->resolveDefaultWarehouseId();
                $availableDefault = $inventory->availableInWarehouse((int) $product->id, $defaultWarehouseId);

                if ($availableDefault < abs($delta)) {
                    return redirect()
                        ->back()
                        ->withInput()
                        ->withErrors([
                            'stock_qty' => __('Not enough stock available.'),
                        ]);
                }
            } catch (StockConflictException) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors([
                        'stock_qty' => __('Not enough stock available.'),
                    ]);
            }
        }

        try {
            DB::transaction(function () use ($product, $data, $delta): void {
                $product->update($data);

                if ($delta !== 0) {
                    $inventory = app(InventoryService::class);
                    $defaultWarehouseId = $inventory->resolveDefaultWarehouseId();

                    $inventory->moveStock(
                        (int) $product->id,
                        $defaultWarehouseId,
                        $delta,
                        'Stock adjusted from product update'
                    );
                }
            });
        } catch (StockConflictException) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'stock_qty' => __('Not enough stock available.'),
                ]);
        }

        // cover image handling
        if ($request->boolean('remove_cover')) {
            if ($product->cover_image) {
                Storage::disk('public')->delete($product->cover_image);
            }
            $product->cover_image = null;
            $product->save();
        }

        if ($request->hasFile('cover_image')) {
            // remove old
            if ($product->cover_image) {
                Storage::disk('public')->delete($product->cover_image);
            }
            $path = \App\Support\ImageUploader::storeProductImage($request->file('cover_image'));
            $product->cover_image = $path;
            $product->save();
        }

        // delete selected gallery images
        if ($request->filled('deleted_image_ids')) {
            foreach ($request->input('deleted_image_ids') as $id) {
                $product->images()->where('id', $id)->get()->each->delete();
            }
        }

        // reorder existing images
        if ($request->filled('images_orders')) {
            foreach ($request->input('images_orders') as $entry) {
                $img = $product->images()->find($entry['id']);
                if ($img) {
                    $img->sort_order = (int) $entry['sort_order'];
                    $img->save();
                }
            }
        }

        // add new images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = \App\Support\ImageUploader::storeProductImage($file);
                $product->images()->create(['image_path' => $path]);
            }
        }

        ActivityLogger::log('updated', 'product', $product->id, ['name' => $product->name]);

        return redirect()
            ->route($this->resolveIndexRouteName())
            ->with('success', __('Product updated successfully.'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        ActivityLogger::log('deleted', 'product', $product->id);

        return redirect()
            ->route($this->resolveIndexRouteName())
            ->with('success', __('Product deleted successfully.'));
    }

    public function toggle(Product $product): RedirectResponse
    {
        $product->is_active = ! $product->is_active;
        $product->save();

        ActivityLogger::log('updated', 'product', $product->id, ['is_active' => $product->is_active]);

        return redirect()->back()->with('success', __('Product status updated.'));
    }

    private function filteredProductsQuery(Request $request): Builder
    {
        $search = $request->query('q');
        $status = $request->query('status');
        $categoryId = $request->query('category_id');
        $stock = $request->query('stock');

        $statusValue = null;
        if (is_string($status) && $status !== '') {
            $normalized = Str::lower($status);
            if (in_array($normalized, ['active', '1', 'true'], true)) {
                $statusValue = true;
            } elseif (in_array($normalized, ['inactive', '0', 'false'], true)) {
                $statusValue = false;
            }
        }

        return Product::query()
            ->with('category')
            ->when($search, function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', '%' . $search . '%')
                        ->orWhere('sku', 'like', '%' . $search . '%');
                });
            })
            ->when($statusValue !== null, fn ($query) => $query->where('is_active', $statusValue))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->when($stock, function ($query) use ($stock): void {
                if ($stock === 'in') {
                    $query->where('stock_qty', '>', 10);
                } elseif ($stock === 'low') {
                    $query->whereBetween('stock_qty', [1, 10]);
                } elseif ($stock === 'out') {
                    $query->where('stock_qty', '<=', 0);
                }
            });
    }

    /**
     * @param \Illuminate\Support\Collection<int, \App\Models\Product> $products
     */
    private function streamProductsCsv($products, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($products): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                return;
            }

            // UTF-8 BOM for Arabic compatibility in Excel.
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                __('Product'),
                __('Category'),
                __('SKU'),
                __('Price'),
                __('Qty'),
                __('Status'),
            ]);

            foreach ($products as $product) {
                fputcsv($output, [
                    (string) $product->name,
                    (string) ($product->category?->name ?? ''),
                    (string) $product->sku,
                    (float) $product->price,
                    (int) $product->stock_qty,
                    $product->is_active ? __('Active') : __('Inactive'),
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function resolveIndexRouteName(): string
    {
        $routeName = request()->route()?->getName();

        if (is_string($routeName) && Str::startsWith($routeName, 'admin.catalog.products.')) {
            return 'admin.catalog.products.index';
        }

        return 'admin.products.index';
    }

    private function generateUniqueSku(): string
    {
        do {
            $sku = 'SKU-' . Str::upper(Str::random(8));
        } while (Product::query()->where('sku', $sku)->exists());

        return $sku;
    }
}
