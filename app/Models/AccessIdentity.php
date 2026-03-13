<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccessIdentity extends Model
{
    use HasFactory, SoftDeletes, HasBranchScope;

    public const SUBJECT_MEMBER = 'member';
    public const SUBJECT_STAFF = 'staff';

    protected $fillable = [
        'branch_id',
        'integration_type',
        'provider',
        'subject_type',
        'subject_id',
        'device_user_id',
        'card_number',
        'is_active',
        'fingerprint_enrolled_at',
        'device_synced_at',
        'original_valid_until',
        'valid_from',
        'valid_until',
        'disabled_at',
        'last_sync_error',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'fingerprint_enrolled_at' => 'datetime',
            'device_synced_at' => 'datetime',
            'original_valid_until' => 'date',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'disabled_at' => 'datetime',
            'integration_type' => 'string',
            'provider' => 'string',
        ];
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForMember($query)
    {
        return $query->where('subject_type', self::SUBJECT_MEMBER);
    }

    public function scopeForStaff($query)
    {
        return $query->where('subject_type', self::SUBJECT_STAFF);
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

    /**
     * Get the subject (Member or User) that this identity belongs to.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo('subject', 'subject_type', 'subject_id');
    }

    /**
     * Get member if subject_type is 'member'.
     * Note: Only use this when you've already filtered by subject_type='member'
     */
    public function member()
    {
        return $this->belongsTo(Member::class, 'subject_id');
    }

    /**
     * Get staff user if subject_type is 'staff'.
     * Note: Only use this when you've already filtered by subject_type='staff'
     */
    public function staff()
    {
        return $this->belongsTo(User::class, 'subject_id');
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }
}
