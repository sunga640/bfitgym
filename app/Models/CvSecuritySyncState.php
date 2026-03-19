<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CvSecuritySyncState extends Model
{
    use HasFactory;
    use HasBranchScope;

    protected $table = 'cvsecurity_sync_states';

    protected $fillable = [
        'cvsecurity_connection_id',
        'branch_id',
        'events_cursor',
        'last_member_sync_at',
        'last_event_pull_at',
        'last_success_at',
        'pending_members_count',
        'failed_members_count',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'last_member_sync_at' => 'datetime',
            'last_event_pull_at' => 'datetime',
            'last_success_at' => 'datetime',
            'pending_members_count' => 'integer',
            'failed_members_count' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(CvSecurityConnection::class, 'cvsecurity_connection_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}

