<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CvSecurityMemberSyncItem extends Model
{
    use HasFactory;
    use HasBranchScope;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';
    public const STATUS_RETRY = 'retry';

    protected $table = 'cvsecurity_member_sync_items';

    protected $fillable = [
        'cvsecurity_connection_id',
        'branch_id',
        'member_id',
        'claimed_by_agent_id',
        'sync_action',
        'desired_state',
        'external_person_id',
        'status',
        'attempts',
        'dedupe_key',
        'available_at',
        'claimed_at',
        'processed_at',
        'last_error_at',
        'last_error',
        'payload',
        'result',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'available_at' => 'datetime',
            'claimed_at' => 'datetime',
            'processed_at' => 'datetime',
            'last_error_at' => 'datetime',
            'payload' => 'array',
            'result' => 'array',
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

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function claimedByAgent(): BelongsTo
    {
        return $this->belongsTo(CvSecurityAgent::class, 'claimed_by_agent_id');
    }
}

