<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AuditService
{
    public function record(?User $actor, string $action, string $entityType, ?string $entityId, array $metadata, ?Request $request = null): void
    {
        AuditLog::query()->create([
            'actor_user_id' => $actor?->getKey(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata_json' => $metadata,
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => Carbon::now(),
        ]);
    }
}

