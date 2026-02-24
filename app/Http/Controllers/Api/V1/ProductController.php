<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\ProductResource;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ProductController extends ApiController
{
    private const FILTER_ALL = 'all';
    private const FILTER_POPULAR = 'popular';
    private const FILTER_BEST = 'best';
    private const FILTER_NEW = 'new';
    private const FILTER_PRICE = 'price';

    private const SORT_NEWEST = 'newest';
    private const SORT_BEST_SELLING = 'best_selling';
    private const SORT_PRICE_ASC = 'price_asc';
    private const SORT_PRICE_DESC = 'price_desc';
    private const MIN_AVAILABLE_QTY = 1;

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 20)));
        $filter = $this->normalizeFilter($request->input('filter'));
        $sort = $this->resolveSort($filter, $request->input('sort'));

        $productsQuery = Product::query()
            ->select('products.*')
            ->with([
                'category',
                'images' => function ($q) {
                    $q->orderBy('sort_order');
                },
            ])
            ->withCount('images')
            ->where('is_active', true);

        $this->applyInStockFilter($productsQuery);
        $this->includeSoldQty($productsQuery);

        if ($request->filled('category_id')) {
            $productsQuery->where('category_id', (int) $request->input('category_id'));
        }

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));

            $productsQuery->where(function ($query) use ($search): void {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('sku', 'like', '%' . $search . '%');
            });
        }

        if ($filter === self::FILTER_POPULAR) {
            $productsQuery->whereHas('orderItems.order', function (Builder $orderQuery): void {
                $orderQuery->whereIn('status', $this->bestSellerStatuses());
            });
        }

        $this->applySort($productsQuery, $sort);

        $products = $productsQuery
            ->paginate($perPage)
            ->withQueryString();

        // transform resource array to truncate description for listing
        $data = ProductResource::collection($products->getCollection())->resolve($request);
        foreach ($data as &$item) {
            if (isset($item['description'])) {
                $item['description'] = \Illuminate\Support\Str::limit($item['description'], 120);
            }
        }

        return $this->success(
            $data,
            200,
            'Products fetched',
            array_merge(
                $this->paginationMeta($products),
                [
                    'filters' => [
                        'applied_filter' => $filter,
                        'applied_sort' => $sort,
                        'available_filters' => [
                            self::FILTER_ALL,
                            self::FILTER_POPULAR,
                            self::FILTER_BEST,
                            self::FILTER_NEW,
                            self::FILTER_PRICE,
                        ],
                        'available_sorts' => [
                            self::SORT_NEWEST,
                            self::SORT_BEST_SELLING,
                            self::SORT_PRICE_ASC,
                            self::SORT_PRICE_DESC,
                        ],
                    ],
                ]
            )
        );
    }

    public function show(Product $product): JsonResponse
    {
        if (! $this->isProductVisibleInApi($product)) {
            return $this->error('Product not found', 404);
        }

        $product->loadMissing(['category', 'images']);

        return $this->success(new ProductResource($product), 200, 'Product fetched');
    }

    public function store(
        \App\Http\Requests\Api\V1\ProductRequest $request
    ): JsonResponse {
        $data = $request->validated();
        $data['is_active'] = (bool) $data['is_active'];

        $product = Product::query()->create($data);

        // cover upload
        if ($request->hasFile('cover_image')) {
            $product->cover_image = \App\Support\ImageUploader::storeProductImage($request->file('cover_image'));
            $product->save();
        }

        // additional images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $product->images()->create([
                    'image_path' => \App\Support\ImageUploader::storeProductImage($file),
                ]);
            }
        }

        $product->load(['category', 'images']);

        return $this->success(new ProductResource($product), 201, 'Product created');
    }

    public function update(
        \App\Http\Requests\Api\V1\ProductRequest $request,
        Product $product
    ): JsonResponse {
        $data = $request->validated();
        $data['is_active'] = (bool) $data['is_active'];

        $product->update($data);

        if ($request->boolean('remove_cover')) {
            if ($product->cover_image) {
                Storage::disk('public')->delete($product->cover_image);
            }
            $product->cover_image = null;
            $product->save();
        }

        if ($request->hasFile('cover_image')) {
            if ($product->cover_image) {
                Storage::disk('public')->delete($product->cover_image);
            }
            $product->cover_image = \App\Support\ImageUploader::storeProductImage($request->file('cover_image'));
            $product->save();
        }

        if ($request->filled('deleted_image_ids')) {
            foreach ($request->input('deleted_image_ids') as $id) {
                $product->images()->where('id', $id)->get()->each->delete();
            }
        }

        if ($request->filled('images_orders')) {
            foreach ($request->input('images_orders') as $entry) {
                $img = $product->images()->find($entry['id']);
                if ($img) {
                    $img->sort_order = (int) $entry['sort_order'];
                    $img->save();
                }
            }
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $product->images()->create([
                    'image_path' => \App\Support\ImageUploader::storeProductImage($file),
                ]);
            }
        }

        $product->load(['category', 'images']);

        return $this->success(new ProductResource($product), 200, 'Product updated');
    }

    private function includeSoldQty(Builder $query): void
    {
        $query->withSum([
            'orderItems as sold_qty' => function (Builder $itemQuery): void {
                $itemQuery->whereHas('order', function (Builder $orderQuery): void {
                    $orderQuery->whereIn('status', $this->bestSellerStatuses());
                });
            },
        ], 'qty');
    }

    private function applyInStockFilter(Builder $query): void
    {
        if (Schema::hasTable('product_stocks')) {
            $query->where(function (Builder $stockQuery): void {
                $stockQuery
                    ->whereRaw(
                        '(SELECT COALESCE(SUM(ps.qty), 0) FROM product_stocks ps WHERE ps.product_id = products.id) >= ?',
                        [self::MIN_AVAILABLE_QTY]
                    )
                    ->orWhere('stock_qty', '>=', self::MIN_AVAILABLE_QTY);
            });

            return;
        }

        $query->where('stock_qty', '>=', self::MIN_AVAILABLE_QTY);
    }

    private function isProductVisibleInApi(Product $product): bool
    {
        if (! (bool) $product->is_active) {
            return false;
        }

        if (Schema::hasTable('product_stocks')) {
            $stockQty = (int) $product->stocks()->sum('qty');
            if ($stockQty >= self::MIN_AVAILABLE_QTY) {
                return true;
            }
        }

        return (int) $product->stock_qty >= self::MIN_AVAILABLE_QTY;
    }

    private function normalizeFilter(mixed $value): string
    {
        $filter = strtolower(trim((string) $value));

        return match ($filter) {
            self::FILTER_POPULAR,
            self::FILTER_BEST,
            self::FILTER_NEW,
            self::FILTER_PRICE => $filter,
            default => self::FILTER_ALL,
        };
    }

    private function resolveSort(string $filter, mixed $sortValue): string
    {
        $sort = strtolower(trim((string) $sortValue));

        if ($sort === '') {
            return match ($filter) {
                self::FILTER_POPULAR,
                self::FILTER_BEST => self::SORT_BEST_SELLING,
                self::FILTER_PRICE => self::SORT_PRICE_ASC,
                self::FILTER_NEW,
                self::FILTER_ALL => self::SORT_NEWEST,
                default => self::SORT_NEWEST,
            };
        }

        return match ($sort) {
            'newest', 'latest', 'new' => self::SORT_NEWEST,
            'best_selling', 'best-selling', 'popular', 'top', 'best' => self::SORT_BEST_SELLING,
            'price_asc', 'price-low', 'price_low', 'price', 'cheapest' => self::SORT_PRICE_ASC,
            'price_desc', 'price-high', 'price_high', 'expensive' => self::SORT_PRICE_DESC,
            default => self::SORT_NEWEST,
        };
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            self::SORT_BEST_SELLING => $query
                ->orderByDesc('sold_qty')
                ->orderByDesc('id'),
            self::SORT_PRICE_ASC => $query
                ->orderBy('price')
                ->orderByDesc('id'),
            self::SORT_PRICE_DESC => $query
                ->orderByDesc('price')
                ->orderByDesc('id'),
            default => $query
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
        };
    }

    /**
     * @return array<int, string>
     */
    private function bestSellerStatuses(): array
    {
        return [
            Order::STATUS_APPROVED,
            Order::STATUS_SHIPPED,
            Order::STATUS_DELIVERED,
            Order::STATUS_PAID,
        ];
    }
}
