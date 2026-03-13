<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccessControlDeviceCommand extends Model
{
    use HasUuids;
    use HasBranchScope;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'access_control_device_commands';

    protected $fillable = [
        'id',
        'branch_id',
        'integration_type',
        'provider',
        'access_control_device_id',
        'claimed_by_agent_id',
        'subject_type',
        'subject_id',
        'type',
        'priority',
        'status',
        'attempts',
        'max_attempts',
        'available_at',
        'claimed_at',
        'processing_started_at',
        'finished_at',
        'superseded_at',
        'payload',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'available_at' => 'datetime',
            'claimed_at' => 'datetime',
            'processing_started_at' => 'datetime',
            'finished_at' => 'datetime',
            'superseded_at' => 'datetime',
            'integration_type' => 'string',
            'provider' => 'string',
        ];
    }

    // ---------------------------------------------------------------------
    // Status constants
    // ---------------------------------------------------------------------

    public const STATUS_PENDING = 'pending';
    public const STATUS_CLAIMED = 'claimed';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_SUPERSEDED = 'superseded';

    // ---------------------------------------------------------------------
    // Type constants (cloud canonical)
    // ---------------------------------------------------------------------

    public const TYPE_PERSON_UPSERT = 'person_upsert';
    public const TYPE_PERSON_DISABLE = 'person_disable';
    public const TYPE_PERSON_DELETE = 'person_delete';
    public const TYPE_CARD_SET = 'card_set';
    public const TYPE_ACCESS_SET_VALIDITY = 'access_set_validity';
    public const TYPE_LOGS_PULL = 'logs_pull';

    /**
     * @deprecated Fingerprint enrollment is now done manually via device web dashboard.
     *             Use TYPE_PERSON_UPSERT for user sync instead.
     */
    public const TYPE_ENROLL_FINGERPRINT = 'enroll_fingerprint';

    public function scopeForIntegration($query, string $integration_type)
    {
        return $query->where('integration_type', $integration_type);
    }

    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(AccessControlDevice::class, 'access_control_device_id');
    }

    public function claimedByAgent(): BelongsTo
    {
        return $this->belongsTo(AccessControlAgent::class, 'claimed_by_agent_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(AccessControlCommandAudit::class, 'command_id', 'id');
    }
}
