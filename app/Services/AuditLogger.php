<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    public function record(
        ?User $actor,
        string $action,
        ?Model $auditable = null,
        array $metadata = [],
        ?string $ip = null
    ): AuditLog {
        return AuditLog::create([
            'actor_id'       => $actor?->id,
            'action'         => $action,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id'   => $auditable?->getKey(),
            'metadata'       => $metadata !== [] ? $metadata : null,
            'ip_address'     => $ip,
        ]);
    }
}
