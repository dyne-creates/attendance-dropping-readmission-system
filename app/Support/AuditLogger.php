<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuditLogger
{
    public function record(
        string $event,
        ?string $subjectType = null,
        ?int $subjectId = null,
        string $result = 'success',
        ?int $actorId = null,
    ): void {
        $context = array_filter([
            'event' => $event,
            'actor_id' => $actorId ?? Auth::id(),
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'result' => $result,
        ], fn (mixed $value): bool => $value !== null);

        Log::channel('audit')->info('audit.event', $context);
    }
}
