<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CvSecurityEvent extends Model
{
    use HasFactory;
    use HasBranchScope;

    protected $table = 'cvsecurity_events';

    protected $fillable = [
        'cvsecurity_connection_id',
        'branch_id',
        'agent_id',
        'member_id',
        'external_event_id',
        'external_person_id',
        'event_type',
        'direction',
        'occurred_at',
        'device_id',
        'door_id',
        'reader_id',
        'processing_status',
        'dedupe_hash',
        'raw_payload',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'received_at' => 'datetime',
            'raw_payload' => 'array',
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

    public function agent(): BelongsTo
    {
        return $this->belongsTo(CvSecurityAgent::class, 'agent_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}

