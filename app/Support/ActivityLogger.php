<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public static function log(string $action, string $entityType, int $entityId, array $meta = []): void
    {
        $authUser = Auth::user();
        $userId = $authUser instanceof User ? (int) $authUser->getKey() : null;

        ActivityLog::query()->create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'meta' => $meta ?: null,
            'created_at' => now(),
        ]);
    }
}
