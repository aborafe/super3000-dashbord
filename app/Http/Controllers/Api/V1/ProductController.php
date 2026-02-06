<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()->with('category');

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search): void {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        $products = $query->paginate(
            perPage: (int) $request->integer('per_page', 15)
        );

        return ProductResource::collection($products)
            ->additional([
                'meta' => [
                    'message' => 'Products list.',
                ],
            ])
            ->response();
    }

    public function show(Product $product): JsonResponse
    {
        $product->load('category');

        return (new ProductResource($product))
            ->additional([
                'meta' => [
                    'message' => 'Product details.',
                ],
            ])
            ->response();
    }
}
