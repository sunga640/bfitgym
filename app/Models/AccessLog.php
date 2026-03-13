<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessLog extends Model
{
    use HasFactory, HasBranchScope;

    public const DIRECTION_IN = 'in';
    public const DIRECTION_OUT = 'out';
    public const DIRECTION_UNKNOWN = 'unknown';

    public const SUBJECT_MEMBER = 'member';
    public const SUBJECT_STAFF = 'staff';

    protected $fillable = [
        'branch_id',
        'integration_type',
        'provider',
        'access_control_device_id',
        'access_identity_id',
        'device_event_uid',
        'subject_type',
        'subject_id',
        'direction',
        'event_timestamp',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'event_timestamp' => 'datetime',
            'raw_payload' => 'array',
            'device_event_uid' => 'string',
            'integration_type' => 'string',
            'provider' => 'string',
        ];
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForMember($query, int $member_id)
    {
        return $query->where('subject_type', self::SUBJECT_MEMBER)
            ->where('subject_id', $member_id);
    }

    public function scopeForStaff($query, int $user_id)
    {
        return $query->where('subject_type', self::SUBJECT_STAFF)
            ->where('subject_id', $user_id);
    }

    public function scopeBetweenDates($query, $from, $to)
    {
        return $query->whereBetween('event_timestamp', [$from, $to]);
    }

    public function scopeEntries($query)
    {
        return $query->where('direction', self::DIRECTION_IN);
    }

    public function scopeExits($query)
    {
        return $query->where('direction', self::DIRECTION_OUT);
    }

    public function scopeForIntegration($query, string $integration_type)
    {
        return $query->where('integration_type', $integration_type);
    }

    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function accessControlDevice(): BelongsTo
    {
        return $this->belongsTo(AccessControlDevice::class);
    }

    public function accessIdentity(): BelongsTo
    {
        return $this->belongsTo(AccessIdentity::class);
    }

    /**
     * Get the member if subject_type is 'member'.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'subject_id');
    }

    /**
     * Get the staff user if subject_type is 'staff'.
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subject_id');
    }
}
