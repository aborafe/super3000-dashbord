<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Notifications\AdminMessageNotification;
use App\Notifications\NewOrderCreated;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function createAdminUser(): User
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_admin_can_send_notification_to_specific_user_and_customer(): void
    {
        $admin = $this->createAdminUser();
        $targetUser = User::factory()->create();
        $targetCustomer = Customer::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->post(route('admin.notifications.send', ['locale' => 'en']), [
            'recipients' => [
                'user:'.$targetUser->id,
                'customer:'.$targetCustomer->id,
            ],
            'title' => 'Maintenance',
            'message' => 'Planned outage tonight.',
        ]);

        $response->assertRedirect(route('admin.notifications.compose', ['locale' => 'en']));

        $this->assertSame('Maintenance', (string) $targetUser->fresh()->notifications()->first()?->data['title']);
        $this->assertSame('Maintenance', (string) $targetCustomer->fresh()->notifications()->first()?->data['title']);
    }

    public function test_admin_can_send_notification_to_all_users_and_active_customers(): void
    {
        $admin = $this->createAdminUser();
        $staff = User::factory()->create();
        $activeCustomer = Customer::factory()->create(['is_active' => true]);
        $inactiveCustomer = Customer::factory()->create(['is_active' => false]);

        $response = $this->actingAs($admin)->post(route('admin.notifications.send', ['locale' => 'en']), [
            'recipients' => ['all:everyone'],
            'title' => 'Campaign',
            'message' => 'Global message',
        ]);

        $response->assertRedirect(route('admin.notifications.compose', ['locale' => 'en']));

        $this->assertSame('Campaign', (string) $admin->fresh()->notifications()->first()?->data['title']);
        $this->assertSame('Campaign', (string) $staff->fresh()->notifications()->first()?->data['title']);
        $this->assertSame('Campaign', (string) $activeCustomer->fresh()->notifications()->first()?->data['title']);
        $this->assertCount(0, $inactiveCustomer->fresh()->notifications);
    }

    public function test_admin_send_persists_notification_immediately_when_queue_is_database(): void
    {
        config()->set('queue.default', 'database');

        $admin = $this->createAdminUser();
        $targetCustomer = Customer::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->post(route('admin.notifications.send', ['locale' => 'en']), [
            'recipients' => [
                'customer:'.$targetCustomer->id,
            ],
            'title' => 'Immediate DB Save',
            'message' => 'Should persist without worker.',
        ]);

        $response->assertRedirect(route('admin.notifications.compose', ['locale' => 'en']));
        $this->assertSame('Immediate DB Save', (string) $targetCustomer->fresh()->notifications()->first()?->data['title']);
    }

    public function test_notifications_index_shows_database_notifications(): void
    {
        $admin = $this->createAdminUser();
        $admin->notify(new AdminMessageNotification('Dashboard alert', 'Inventory synced'));

        $response = $this->actingAs($admin)->get(route('admin.notifications.index', ['locale' => 'en']));

        $response->assertOk();
        $response->assertSeeText('Dashboard alert');
        $response->assertSeeText('Inventory synced');
    }

    public function test_notifications_index_truncates_long_subject_with_ellipsis(): void
    {
        $admin = $this->createAdminUser();
        $longMessage = str_repeat('This is a long notification body. ', 12);
        $expectedPreview = \Illuminate\Support\Str::limit(
            preg_replace('/\s+/', ' ', trim($longMessage)) ?? '',
            120,
            '...'
        );

        $admin->notify(new AdminMessageNotification('Long body', $longMessage));

        $response = $this->actingAs($admin)->get(route('admin.notifications.index', ['locale' => 'en']));

        $response->assertOk();
        $response->assertSeeText('Long body');
        $response->assertSeeText($expectedPreview);
    }

    public function test_notifications_show_page_displays_notification_and_marks_it_as_read(): void
    {
        $admin = $this->createAdminUser();
        $admin->notify(new AdminMessageNotification('Single notification', 'Full notification content for details page.', 'Operations Team'));
        $notificationId = (string) $admin->notifications()->latest()->firstOrFail()->id;

        $response = $this->actingAs($admin)->get(route('admin.notifications.show', [
            'locale' => 'en',
            'notification' => $notificationId,
        ]));

        $response->assertOk();
        $response->assertSeeText('Single notification');
        $response->assertSeeText('Full notification content for details page.');
        $response->assertSeeText('Operations Team');
        $this->assertNotNull($admin->fresh()->notifications()->findOrFail($notificationId)->read_at);
    }

    public function test_notifications_show_page_uses_new_order_template_when_notification_type_is_new_order(): void
    {
        $admin = $this->createAdminUser();
        $customer = Customer::factory()->create(['name' => 'Customer One']);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'order_no' => 'ORD-9999',
            'subtotal' => 275.50,
            'total' => 275.50,
        ]);

        $admin->notify(new NewOrderCreated($order));
        $notificationId = (string) $admin->notifications()->latest()->firstOrFail()->id;

        $response = $this->actingAs($admin)->get(route('admin.notifications.show', [
            'locale' => 'en',
            'notification' => $notificationId,
        ]));

        $response->assertOk();
        $response->assertSeeText('New Order');
        $response->assertSeeText('ORD-9999');
        $response->assertSeeText('Customer One');
        $response->assertSeeText('275.50');
    }

    public function test_admin_can_mark_single_and_all_notifications_as_read(): void
    {
        $admin = $this->createAdminUser();
        $admin->notify(new AdminMessageNotification('One', 'Body 1'));
        $admin->notify(new AdminMessageNotification('Two', 'Body 2'));

        $notificationId = (string) $admin->notifications()->latest()->firstOrFail()->id;

        $singleReadResponse = $this->actingAs($admin)->patch(
            route('admin.notifications.read', ['locale' => 'en', 'notification' => $notificationId])
        );
        $singleReadResponse->assertRedirect();

        $this->assertNotNull($admin->fresh()->notifications()->findOrFail($notificationId)->read_at);

        $markAllResponse = $this->actingAs($admin)->patch(route('admin.notifications.read-all', ['locale' => 'en']));
        $markAllResponse->assertRedirect();

        $this->assertSame(0, $admin->fresh()->unreadNotifications()->count());
    }

    public function test_notifications_live_endpoint_returns_realtime_payload(): void
    {
        $admin = $this->createAdminUser();
        $admin->notify(new AdminMessageNotification('Live title', 'Live body'));

        $response = $this->actingAs($admin)->getJson(route('admin.notifications.live', ['locale' => 'en']));

        $response->assertOk();
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('data.unread_count', 1);
        $response->assertJsonPath('data.notifications.0.title', 'Live title');
        $response->assertJsonPath('data.notifications.0.message', 'Live body');
        $response->assertJsonStructure([
            'status',
            'data' => [
                'unread_count',
                'notifications' => [
                    '*' => ['id', 'title', 'message', 'is_read', 'created_at_human', 'show_url', 'mark_read_url'],
                ],
                'urls' => ['index', 'mark_all'],
            ],
        ]);
    }
}
