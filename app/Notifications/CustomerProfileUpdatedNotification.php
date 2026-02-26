<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CustomerProfileUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param array<string, string> $oldProfile
     * @param array<string, string> $newProfile
     * @param array<int, string> $changedFields
     */
    public function __construct(
        public string $customerName,
        public int $customerId,
        public array $oldProfile,
        public array $newProfile,
        public array $changedFields,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->payload();
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'type' => 'customer_profile_updated',
            'title' => __('Customer profile updated'),
            'message' => __('Customer :name updated account profile details.', [
                'name' => $this->customerName,
            ]),
            'sender_name' => $this->customerName,
            'sender_id' => $this->customerId,
            'old_profile' => $this->oldProfile,
            'new_profile' => $this->newProfile,
            'changed_fields' => $this->changedFields,
        ];
    }
}
