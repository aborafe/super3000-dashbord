<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Notifications\AdminMessageNotification;
use App\Notifications\OrderStatusChanged;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_index_returns_only_authenticated_customer_notifications(): void
    {
        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $otherCustomer = Customer::factory()->create();

        $customer->notify(new AdminMessageNotification('Order updated', 'Your order was updated.'));
        $otherCustomer->notify(new AdminMessageNotification('Other customer', 'Do not expose this.'));

        $response = $this->actingAsCustomerApi($customer)->getJson('/api/v2/notifications');

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('meta.unread_count', 1);
        $response->assertJsonPath('meta.realtime.channel', 'customer.'.$customer->id);
        $response->assertJsonPath('data.0.type', 'admin_message');
        $response->assertJsonPath('data.0.title', 'Order updated');

        $this->assertCount(1, $response->json('data'));
    }

    public function test_customer_can_mark_own_notification_as_read(): void
    {
        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $customer->notify(new AdminMessageNotification('Order updated', 'Your order changed.'));

        $notificationId = (string) $customer->notifications()->firstOrFail()->id;

        $response = $this->actingAsCustomerApi($customer)->patchJson('/api/v2/notifications/'.$notificationId.'/read');

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('data.id', $notificationId);

        $this->assertNotNull(
            DB::table('notifications')
                ->where('id', $notificationId)
                ->value('read_at')
        );
    }

    public function test_customer_cannot_mark_notification_that_does_not_belong_to_him(): void
    {
        $customer = Customer::factory()->create(['email' => 'customer-a@example.com']);

        $otherCustomer = Customer::factory()->create(['email' => 'customer-b@example.com']);
        $otherCustomer->notify(new AdminMessageNotification('Order updated', 'Other user notification.'));
        $otherNotificationId = (string) $otherCustomer->notifications()->firstOrFail()->id;

        $response = $this->actingAsCustomerApi($customer)->patchJson('/api/v2/notifications/'.$otherNotificationId.'/read');

        $response->assertStatus(404);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('meta.message', 'Notification not found');
    }

    public function test_customer_can_mark_all_notifications_as_read(): void
    {
        $customer = Customer::factory()->create(['email' => 'customer@example.com']);

        $customer->notify(new AdminMessageNotification('A', 'A message'));
        $customer->notify(new AdminMessageNotification('B', 'B message'));

        $markAllResponse = $this->actingAsCustomerApi($customer)->patchJson('/api/v2/notifications/read-all');

        $markAllResponse->assertStatus(200);
        $markAllResponse->assertJsonPath('status', true);
        $markAllResponse->assertJsonPath('data.updated', 2);

        $unreadResponse = $this->actingAsCustomerApi($customer)->getJson('/api/v2/notifications/unread-count');

        $unreadResponse->assertStatus(200);
        $unreadResponse->assertJsonPath('status', true);
        $unreadResponse->assertJsonPath('data.count', 0);
    }

    public function test_customer_notifications_use_broadcast_channel_for_realtime(): void
    {
        Notification::fake();

        $customer = Customer::factory()->create();
        $user = User::factory()->create();

        $customer->notify(new AdminMessageNotification('Customer title', 'Customer body'));
        $user->notify(new AdminMessageNotification('User title', 'User body'));

        Notification::assertSentTo(
            $customer,
            AdminMessageNotification::class,
            function (AdminMessageNotification $notification, array $channels): bool {
                sort($channels);

                return $channels === ['broadcast', 'database'];
            }
        );

        Notification::assertSentTo(
            $user,
            AdminMessageNotification::class,
            function (AdminMessageNotification $notification, array $channels): bool {
                sort($channels);

                return $channels === ['broadcast', 'database'];
            }
        );
    }

    public function test_order_status_change_notification_is_broadcast_for_customer_realtime(): void
    {
        Notification::fake();
        $this->seed(RolesAndPermissionsSeeder::class);

        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => Order::STATUS_PENDING,
        ]);

        $order->update(['status' => Order::STATUS_SHIPPED]);

        Notification::assertSentTo(
            $customer,
            OrderStatusChanged::class,
            function (OrderStatusChanged $notification, array $channels): bool {
                sort($channels);

                return $channels === ['broadcast', 'database'];
            }
        );
    }
}
