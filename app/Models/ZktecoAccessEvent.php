<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZktecoAccessEvent extends Model
{
    use HasFactory;
    use HasBranchScope;

    public const DIRECTION_IN = 'in';
    public const DIRECTION_OUT = 'out';
    public const DIRECTION_UNKNOWN = 'unknown';

    protected $fillable = [
        'branch_id',
        'zkteco_connection_id',
        'zkteco_device_id',
        'member_id',
        'remote_event_id',
        'event_fingerprint',
        'remote_personnel_id',
        'direction',
        'event_status',
        'occurred_at',
        'matched_member',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'matched_member' => 'boolean',
            'raw_payload' => 'array',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(ZktecoConnection::class, 'zkteco_connection_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(ZktecoDevice::class, 'zkteco_device_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}

