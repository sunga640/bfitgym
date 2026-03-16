<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZktecoSyncRun extends Model
{
    use HasFactory;
    use HasBranchScope;

    public const TYPE_CONNECTION_TEST = 'connection_test';
    public const TYPE_DEVICE_FETCH = 'device_fetch';
    public const TYPE_PERSONNEL_SYNC = 'personnel_sync';
    public const TYPE_ACCESS_SYNC = 'access_sync';
    public const TYPE_EVENT_SYNC = 'event_sync';

    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PARTIAL = 'partial';

    protected $fillable = [
        'branch_id',
        'zkteco_connection_id',
        'run_type',
        'status',
        'started_at',
        'finished_at',
        'records_total',
        'records_success',
        'records_failed',
        'error_message',
        'context',
        'triggered_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'records_total' => 'integer',
            'records_success' => 'integer',
            'records_failed' => 'integer',
            'context' => 'array',
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

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }
}

