<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class AccessControlAgent extends Model
{
    use HasFactory;
    use HasBranchScope;
    use SoftDeletes;

    // Status constants
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REVOKED = 'revoked';

    // Online threshold (minutes)
    public const ONLINE_THRESHOLD_MINUTES = 2;

    protected $fillable = [
        'branch_id',
        'uuid',
        'name',
        'os',
        'app_version',
        'status',
        'supported_providers',
        'default_provider',
        'secret_hash',
        'last_seen_at',
        'last_ip',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'supported_providers' => 'array',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Enrollments that created this agent (from access_control_agent_id).
     */
    public function createdByEnrollments(): HasMany
    {
        return $this->hasMany(AccessControlAgentEnrollment::class, 'access_control_agent_id');
    }

    /**
     * Enrollments used by this agent.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(AccessControlAgentEnrollment::class, 'used_by_agent_id');
    }

    /**
     * Devices assigned to this agent via pivot table.
     */
    public function devices(): BelongsToMany
    {
        return $this->belongsToMany(
            related: AccessControlDevice::class,
            table: 'access_control_agent_devices',
            foreignPivotKey: 'access_control_agent_id',
            relatedPivotKey: 'access_control_device_id',
        )
            ->using(AccessControlAgentDevice::class)
            ->withPivot(['branch_id'])
            ->withTimestamps();
    }

    /**
     * Devices where this agent is the primary agent.
     */
    public function primaryDevices(): HasMany
    {
        return $this->hasMany(AccessControlDevice::class, 'access_control_agent_id');
    }

    /**
     * Commands claimed by this agent.
     */
    public function claimedCommands(): HasMany
    {
        return $this->hasMany(AccessControlDeviceCommand::class, 'claimed_by_agent_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeRevoked($query)
    {
        return $query->where('status', self::STATUS_REVOKED);
    }

    public function scopeOnline($query)
    {
        return $query->where('last_seen_at', '>=', now()->subMinutes(self::ONLINE_THRESHOLD_MINUTES));
    }

    public function scopeOffline($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('last_seen_at')
                ->orWhere('last_seen_at', '<', now()->subMinutes(self::ONLINE_THRESHOLD_MINUTES));
        });
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Check if agent is currently online (last seen within threshold).
     */
    public function getIsOnlineAttribute(): bool
    {
        return !$this->isLastSeenStale(self::ONLINE_THRESHOLD_MINUTES);
    }

    /**
     * Get status badge info for display.
     */
    public function getStatusBadgeAttribute(): array
    {
        if ($this->status === self::STATUS_REVOKED) {
            return ['label' => 'Revoked', 'variant' => 'danger'];
        }

        if ($this->is_online) {
            return ['label' => 'Online', 'variant' => 'success'];
        }

        return ['label' => 'Offline', 'variant' => 'warning'];
    }

    // -------------------------------------------------------------------------
    // Instance Methods
    // -------------------------------------------------------------------------

    /**
     * Consider agent offline if last seen is older than threshold.
     */
    public function isLastSeenStale(int $threshold_minutes): bool
    {
        if (!$this->last_seen_at) {
            return true;
        }

        return Carbon::parse($this->last_seen_at)->diffInMinutes(now()) > $threshold_minutes;
    }

    /**
     * Revoke the agent's access.
     */
    public function revoke(): void
    {
        $this->update([
            'status' => self::STATUS_REVOKED,
            'secret_hash' => '', // Clear the token hash
        ]);
    }

    /**
     * Record a heartbeat from the agent.
     */
    public function recordHeartbeat(?string $ip = null, ?string $error = null): void
    {
        $this->update([
            'last_seen_at' => now(),
            'last_ip' => $ip ?? $this->last_ip,
            'last_error' => $error,
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function providerList(): array
    {
        $providers = $this->supported_providers ?? [];

        if (empty($providers) && $this->default_provider) {
            $providers = [$this->default_provider];
        }

        if (empty($providers)) {
            $providers = [AccessControlDevice::PROVIDER_HIKVISION_AGENT];
        }

        return array_values(array_unique(array_filter($providers)));
    }

    public function supportsProvider(string $provider): bool
    {
        return in_array($provider, $this->providerList(), true);
    }
}
