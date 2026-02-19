<?php

namespace App\Domain\Orders;

class OrderStatusStateMachine
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_RETURNED = 'returned';
    public const LEGACY_STATUS_PAID = 'paid';

    /**
     * @var array<string, array<int, string>>
     */
    private const TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_APPROVED, self::STATUS_CANCELLED],
        self::STATUS_APPROVED => [self::STATUS_SHIPPED, self::STATUS_CANCELLED],
        self::STATUS_SHIPPED => [self::STATUS_DELIVERED, self::STATUS_RETURNED],
        self::STATUS_DELIVERED => [self::STATUS_RETURNED],
        self::STATUS_CANCELLED => [],
        self::STATUS_RETURNED => [],
    ];

    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_SHIPPED,
            self::STATUS_DELIVERED,
            self::STATUS_CANCELLED,
            self::STATUS_RETURNED,
        ];
    }

    public static function normalize(string $status): string
    {
        $normalized = strtolower(trim($status));

        if ($normalized === self::LEGACY_STATUS_PAID) {
            return self::STATUS_APPROVED;
        }

        if (in_array($normalized, self::statuses(), true)) {
            return $normalized;
        }

        return self::STATUS_PENDING;
    }

    public static function canTransition(string $from, string $to): bool
    {
        $from = self::normalize($from);
        $to = self::normalize($to);

        if ($from === $to) {
            return true;
        }

        return in_array($to, self::nextStatuses($from), true);
    }

    /**
     * @return array<int, string>
     */
    public static function nextStatuses(string $from): array
    {
        $from = self::normalize($from);

        return self::TRANSITIONS[$from] ?? [];
    }
}
