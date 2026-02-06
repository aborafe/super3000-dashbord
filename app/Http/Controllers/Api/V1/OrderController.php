<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::query()->with(['partner', 'items.product']);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($paymentStatus = $request->string('payment_status')->toString()) {
            $query->where('payment_status', $paymentStatus);
        }

        $orders = $query->paginate(
            perPage: (int) $request->integer('per_page', 15)
        );

        return OrderResource::collection($orders)
            ->additional([
                'meta' => [
                    'message' => 'Orders list.',
                ],
            ])
            ->response();
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['partner', 'items.product', 'payments']);

        return (new OrderResource($order))
            ->additional([
                'meta' => [
                    'message' => 'Order details.',
                ],
            ])
            ->response();
    }
}
