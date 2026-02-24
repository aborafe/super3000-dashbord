<?php

namespace App\Notifications;

use App\Models\ProductStock;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LowStockAlert extends Notification
{
    use Queueable;

    public function __construct(public ProductStock $productStock)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->payload();
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload();
    }

    protected function payload(): array
    {
        $product = $this->productStock->product;
        $warehouse = $this->productStock->warehouse;

        return [
            'type' => 'low_stock_alert',
            'title' => __('Low stock alert'),
            'message' => __(':product is low in :warehouse (qty: :qty).', [
                'product' => $product?->name ?? __('Unknown product'),
                'warehouse' => $warehouse?->name ?? __('Warehouse'),
                'qty' => $this->productStock->qty,
            ]),
            'product_id' => $product?->id,
            'product_name' => (string) ($product?->name ?? ''),
            'warehouse_id' => $warehouse?->id,
            'warehouse_name' => (string) ($warehouse?->name ?? ''),
            'qty' => $this->productStock->qty,
            'route' => 'admin.catalog.products.edit',
            'route_params' => [
                'product' => $product?->id,
            ],
        ];
    }
}
