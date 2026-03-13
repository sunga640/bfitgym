<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

class AccessControlAgentEnrollment extends Model
{
    use HasFactory;
    use HasBranchScope;

    protected $table = 'access_control_agent_enrollments';

    // Status constants
    public const STATUS_ACTIVE = 'active';
    public const STATUS_USED = 'used';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'branch_id',
        'integration_type',
        'provider',
        'access_control_agent_id',
        'code',
        'code_hash',
        'status',
        'label',
        'expires_at',
        'created_by',
        'used_at',
        'used_by_agent_id',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'integration_type' => 'string',
            'provider' => 'string',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Agent pre-created when enrollment is generated.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(AccessControlAgent::class, 'access_control_agent_id');
    }

    /**
     * Agent that used this enrollment code (may be same as pre-created agent).
     */
    public function usedByAgent(): BelongsTo
    {
        return $this->belongsTo(AccessControlAgent::class, 'used_by_agent_id');
    }

    /**
     * Devices pre-assigned to be managed by the agent after enrollment.
     */
    public function devices(): BelongsToMany
    {
        return $this->belongsToMany(
            related: AccessControlDevice::class,
            table: 'access_control_enrollment_devices',
            foreignPivotKey: 'access_control_agent_enrollment_id',
            relatedPivotKey: 'access_control_device_id',
        )->withTimestamps();
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeUsable($query)
    {
        return $query->active()->notExpired();
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
    // Accessors & Helpers
    // -------------------------------------------------------------------------

    /**
     * Check if enrollment is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if enrollment is usable (active + not expired).
     */
    public function isUsable(): bool
    {
        return $this->status === self::STATUS_ACTIVE && !$this->isExpired();
    }

    /**
     * Get computed status considering expiry.
     */
    public function getComputedStatusAttribute(): string
    {
        if ($this->status === self::STATUS_ACTIVE && $this->isExpired()) {
            return self::STATUS_EXPIRED;
        }

        return $this->status;
    }

    /**
     * Mask the code for display (show only last 8 chars).
     */
    public function getMaskedCodeAttribute(): string
    {
        if (!$this->code) {
            return '********';
        }

        $length = strlen($this->code);
        if ($length <= 8) {
            return $this->code;
        }

        return str_repeat('*', $length - 8) . substr($this->code, -8);
    }

    /**
     * Get time remaining until expiry.
     */
    public function getTimeRemainingAttribute(): ?string
    {
        if (!$this->expires_at) {
            return null;
        }

        if ($this->isExpired()) {
            return 'Expired';
        }

        return $this->expires_at->diffForHumans(['parts' => 2]);
    }

    // -------------------------------------------------------------------------
    // Instance Methods
    // -------------------------------------------------------------------------

    /**
     * Mark enrollment as used by an agent.
     */
    public function markUsed(AccessControlAgent $agent): void
    {
        $this->update([
            'status' => self::STATUS_USED,
            'used_at' => now(),
            'used_by_agent_id' => $agent->id,
        ]);
    }

    /**
     * Mark enrollment as revoked.
     */
    public function revoke(): void
    {
        $this->update([
            'status' => self::STATUS_REVOKED,
        ]);
    }
}
