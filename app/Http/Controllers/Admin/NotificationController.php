<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Notifications\AdminMessageNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = max(10, min(100, $request->integer('per_page', 20)));
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $unreadCount = $user->unreadNotifications()->count();

        return view('admin.notifications.index', compact('notifications', 'unreadCount'));
    }

    public function compose(): View
    {
        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $customers = Customer::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'is_active']);

        $roles = Role::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.notifications.compose', compact('users', 'customers', 'roles'));
    }

    public function show(Request $request, string $notification): View
    {
        /** @var DatabaseNotification $target */
        $target = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        if ($target->read_at === null) {
            $target->markAsRead();
            $target->refresh();
        }

        $payload = is_array($target->data) ? $target->data : [];
        $title = (string) ($payload['title'] ?? class_basename($target->type));
        $message = (string) ($payload['message'] ?? '');
        $notificationType = (string) ($payload['type'] ?? '');
        $locale = (string) app()->getLocale();
        $template = 'generic';

        $orderDetails = null;
        if ($notificationType === 'new_order_created') {
            $template = 'new_order';
            $order = $this->resolveNotificationOrder($payload);
            $orderDetails = [
                'order_no' => (string) ($order?->order_no ?? ($payload['order_no'] ?? '-')),
                'customer_name' => (string) ($order?->customer?->name ?: ($payload['customer_name'] ?? '-')),
                'invoice_value' => (float) ($order?->total ?? $payload['invoice_value'] ?? 0),
                'order_date' => $order?->created_at,
                'order_url' => $this->resolveNotificationRouteUrl($payload, $locale),
            ];
        }

        $messageDetails = null;
        if ($notificationType === 'admin_message') {
            $template = 'message';
            $messageDetails = [
                'sender_name' => (string) ($payload['sender_name'] ?? __('System')),
                'old_profile' => is_array($payload['old_profile'] ?? null) ? $payload['old_profile'] : null,
                'new_profile' => is_array($payload['new_profile'] ?? null) ? $payload['new_profile'] : null,
                'changed_fields' => is_array($payload['changed_fields'] ?? null) ? $payload['changed_fields'] : null,
            ];
        }

        return view('admin.notifications.show', [
            'notification' => $target,
            'title' => $title,
            'message' => $message,
            'template' => $template,
            'orderDetails' => $orderDetails,
            'messageDetails' => $messageDetails,
        ]);
    }

    public function live(Request $request): JsonResponse
    {
        $user = $request->user();
        $locale = (string) app()->getLocale();
        $notifications = $user->notifications()
            ->latest()
            ->limit(5)
            ->get();

        $payload = $notifications
            ->map(function ($notification) use ($locale): array {
                $data = is_array($notification->data) ? $notification->data : [];

                return [
                    'id' => (string) $notification->id,
                    'title' => (string) ($data['title'] ?? class_basename($notification->type)),
                    'message' => (string) ($data['message'] ?? ''),
                    'is_read' => $notification->read_at !== null,
                    'created_at_human' => (string) optional($notification->created_at)->diffForHumans(),
                    'show_url' => route('admin.notifications.show', [
                        'locale' => $locale,
                        'notification' => (string) $notification->id,
                    ]),
                    'mark_read_url' => route('admin.notifications.read', [
                        'locale' => $locale,
                        'notification' => (string) $notification->id,
                    ]),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'status' => true,
            'data' => [
                'unread_count' => (int) $user->unreadNotifications()->count(),
                'notifications' => $payload,
                'urls' => [
                    'index' => route('admin.notifications.index', ['locale' => $locale]),
                    'mark_all' => route('admin.notifications.read-all', ['locale' => $locale]),
                ],
            ],
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipients' => ['required', 'array'],
            'recipients.*' => ['string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $targets = collect($data['recipients'])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        [$users, $customers] = $this->resolveRecipientBuckets($targets);

        if ($users->isEmpty() && $customers->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors([
                    'recipients' => __('No valid recipients selected.'),
                ]);
        }

        $notification = new AdminMessageNotification(
            (string) $data['title'],
            (string) $data['message'],
            (string) ($request->user()?->name ?? __('System')),
            $request->user()?->getKey()
        );

        $recipients = $users
            ->concat($customers)
            ->unique(fn (User|Customer $recipient): string => get_class($recipient).':'.$recipient->getKey())
            ->values();

        // Ensure dashboard-sent notifications are persisted immediately even if queue workers are down.
        Notification::sendNow($recipients, $notification);

        $totalRecipients = $users->count() + $customers->count();

        return redirect()
            ->route('admin.notifications.compose', ['locale' => app()->getLocale()])
            ->with('success', __('Notification sent to :count recipients.', ['count' => $totalRecipients]));
    }

    public function markAsRead(Request $request, string $notification): JsonResponse|RedirectResponse
    {
        $target = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->first();

        if ($target && $target->read_at === null) {
            $target->markAsRead();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => true,
                'data' => null,
            ]);
        }

        return back();
    }

    public function markAllAsRead(Request $request): JsonResponse|RedirectResponse
    {
        $request->user()->unreadNotifications()->update([
            'read_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => true,
                'data' => null,
            ]);
        }

        return back();
    }

    /**
     * @return array{0: \Illuminate\Support\Collection<int, \App\Models\User>, 1: \Illuminate\Support\Collection<int, \App\Models\Customer>}
     */
    private function resolveRecipientBuckets(Collection $targets): array
    {
        $users = collect();
        $customers = collect();

        $sendAllUsers = $targets->contains('all')
            || $targets->contains('all:users')
            || $targets->contains('all:everyone');
        $sendAllCustomers = $targets->contains('all:customers') || $targets->contains('all:everyone');

        if ($sendAllUsers) {
            $users = $users->merge(User::query()->get());
        }

        if ($sendAllCustomers) {
            $customers = $customers->merge(
                Customer::query()
                    ->where('is_active', true)
                    ->get()
            );
        }

        $userIds = $targets
            ->filter(fn (string $target): bool => str_starts_with($target, 'user:'))
            ->map(fn (string $target): int => (int) substr($target, strlen('user:')))
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($userIds->isNotEmpty()) {
            $users = $users->merge(
                User::query()
                    ->whereIn('id', $userIds->all())
                    ->get()
            );
        }

        $customerIds = $targets
            ->filter(fn (string $target): bool => str_starts_with($target, 'customer:'))
            ->map(fn (string $target): int => (int) substr($target, strlen('customer:')))
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($customerIds->isNotEmpty()) {
            $customers = $customers->merge(
                Customer::query()
                    ->whereIn('id', $customerIds->all())
                    ->where('is_active', true)
                    ->get()
            );
        }

        $roleNames = $targets
            ->filter(fn (string $target): bool => str_starts_with($target, 'role:'))
            ->map(fn (string $target): string => trim(substr($target, strlen('role:'))))
            ->filter()
            ->unique()
            ->values();

        foreach ($roleNames as $roleName) {
            $users = $users->merge(User::role($roleName)->get());
        }

        return [
            $users->unique('id')->values(),
            $customers->unique('id')->values(),
        ];
    }

    private function resolveNotificationOrder(array $payload): ?Order
    {
        $orderId = isset($payload['order_id']) ? (int) $payload['order_id'] : 0;
        if ($orderId > 0) {
            return Order::query()->with('customer')->find($orderId);
        }

        $orderNo = trim((string) ($payload['order_no'] ?? ''));
        if ($orderNo === '') {
            return null;
        }

        return Order::query()
            ->with('customer')
            ->where('order_no', $orderNo)
            ->first();
    }

    private function resolveNotificationRouteUrl(array $payload, string $locale): ?string
    {
        $routeName = trim((string) ($payload['route'] ?? ''));
        if ($routeName === '' || !Route::has($routeName)) {
            return null;
        }

        $routeParams = (array) ($payload['route_params'] ?? []);

        return route($routeName, array_merge($routeParams, ['locale' => $locale]));
    }
}
