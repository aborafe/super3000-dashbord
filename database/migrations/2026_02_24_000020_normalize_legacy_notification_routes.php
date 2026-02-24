<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        DB::table('notifications')
            ->select(['id', 'data'])
            ->orderBy('id')
            ->chunk(200, function ($rows): void {
                foreach ($rows as $row) {
                    $payload = is_array($row->data)
                        ? $row->data
                        : (json_decode((string) $row->data, true) ?: null);

                    if (!is_array($payload)) {
                        continue;
                    }

                    $routeValue = trim((string) ($payload['route'] ?? ''));
                    if ($routeValue === '' || !str_starts_with($routeValue, '/order/')) {
                        continue;
                    }

                    $orderId = isset($payload['order_id']) ? (int) $payload['order_id'] : 0;
                    if ($orderId <= 0 && preg_match('#^/order/(\d+)#', $routeValue, $matches) === 1) {
                        $orderId = (int) ($matches[1] ?? 0);
                    }

                    if ($orderId <= 0) {
                        continue;
                    }

                    $routeParams = is_array($payload['route_params'] ?? null) ? $payload['route_params'] : [];
                    $routeParams['order'] = $orderId;

                    $payload['legacy_route'] = $payload['legacy_route'] ?? $routeValue;
                    $payload['route'] = 'admin.orders.show';
                    $payload['route_params'] = $routeParams;

                    DB::table('notifications')
                        ->where('id', $row->id)
                        ->update([
                            'data' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: this migration normalizes legacy payloads safely.
    }
};

