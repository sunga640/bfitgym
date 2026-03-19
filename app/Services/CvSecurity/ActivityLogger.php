<?php

namespace App\Services\CvSecurity;

use App\Models\CvSecurityActivityLog;
use App\Models\CvSecurityAgent;
use App\Models\CvSecurityConnection;

class ActivityLogger
{
    /**
     * @param array<string, mixed>|null $context
     */
    public function log(
        ?CvSecurityConnection $connection,
        string $level,
        string $event,
        ?string $message = null,
        ?array $context = null,
        ?CvSecurityAgent $agent = null,
    ): CvSecurityActivityLog {
        return CvSecurityActivityLog::query()->create([
            'cvsecurity_connection_id' => $connection?->id,
            'branch_id' => $connection?->branch_id,
            'agent_id' => $agent?->id,
            'level' => $level,
            'event' => $event,
            'message' => $message,
            'context' => $context,
            'occurred_at' => now(),
        ]);
    }
}

