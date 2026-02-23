<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 20)));
        $customer = $this->resolveCustomer($request);

        if (! $customer) {
            return $this->success(
                [],
                200,
                'Notifications fetched',
                array_merge($this->emptyPaginationMeta($perPage), [
                    'unread_count' => 0,
                    'realtime' => null,
                ])
            );
        }

        $notifications = $customer->notifications()
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return $this->success(
            collect($notifications->items())
                ->map(fn (DatabaseNotification $notification) => $this->transformNotification($notification))
                ->values()
                ->all(),
            200,
            'Notifications fetched',
            array_merge($this->paginationMeta($notifications), [
                'unread_count' => $customer->unreadNotifications()->count(),
                'realtime' => [
                    'channel' => 'customer.'.$customer->id,
                    'event' => 'Illuminate\\Notifications\\Events\\BroadcastNotificationCreated',
                ],
            ])
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $customer = $this->resolveCustomer($request);

        return $this->success([
            'count' => $customer?->unreadNotifications()->count() ?? 0,
        ], 200, 'Unread notifications count fetched');
    }

    public function markAsRead(string $notification, Request $request): JsonResponse
    {
        $customer = $this->resolveCustomer($request);
        if (! $customer) {
            return $this->error('Notification not found', 404);
        }

        $target = $customer->notifications()
            ->whereKey($notification)
            ->first();

        if (! $target) {
            return $this->error('Notification not found', 404);
        }

        if ($target->read_at === null) {
            $target->markAsRead();
            $target->refresh();
        }

        return $this->success(
            $this->transformNotification($target),
            200,
            'Notification marked as read'
        );
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $customer = $this->resolveCustomer($request);

        if (! $customer) {
            return $this->success([
                'updated' => 0,
            ], 200, 'All notifications marked as read');
        }

        $updated = $customer->unreadNotifications()->update([
            'read_at' => now(),
        ]);

        return $this->success([
            'updated' => (int) $updated,
        ], 200, 'All notifications marked as read');
    }

    protected function resolveCustomer(Request $request): ?Customer
    {
        $user = $request->user();
        if ($user instanceof Customer) {
            return $user;
        }

        $email = strtolower((string) $user?->email);

        if ($email === '') {
            return null;
        }

        return Customer::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();
    }

    protected function transformNotification(DatabaseNotification $notification): array
    {
        return [
            'id' => (string) $notification->id,
            'type' => (string) ($notification->data['type'] ?? class_basename($notification->type)),
            'title' => (string) ($notification->data['title'] ?? ''),
            'message' => (string) ($notification->data['message'] ?? ''),
            'data' => $notification->data,
            'read_at' => $notification->read_at?->toISOString(),
            'created_at' => $notification->created_at?->toISOString(),
        ];
    }

    protected function emptyPaginationMeta(int $perPage): array
    {
        return [
            'pagination' => [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $perPage,
                'total' => 0,
            ],
        ];
    }
}
