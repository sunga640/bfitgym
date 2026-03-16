<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;

class AccessControlDevice extends Model
{
    use HasFactory, SoftDeletes, HasBranchScope;

    // -------------------------------------------------------------------------
    // Constants
    // -------------------------------------------------------------------------

    /** Device Models */
    public const MODEL_DS_K1T804A = 'DS-K1T804A';
    public const MODEL_DS_K1T808MFWX = 'DS-K1T808MFWX';
    public const MODEL_DS_K1T671M = 'DS-K1T671M';
    public const MODEL_DS_K1T341AM = 'DS-K1T341AM';

    /** Device Types */
    public const TYPE_ENTRY = 'entry';
    public const TYPE_EXIT = 'exit';
    public const TYPE_BIDIRECTIONAL = 'bidirectional';

    /** Status Values */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    /** Connection Status */
    public const CONNECTION_ONLINE = 'online';
    public const CONNECTION_OFFLINE = 'offline';
    public const CONNECTION_UNKNOWN = 'unknown';

    /** Integration Types */
    public const INTEGRATION_HIKVISION = 'hikvision';
    public const INTEGRATION_ZKTECO = 'zkteco';

    /** Providers */
    public const PROVIDER_HIKVISION_AGENT = 'hikvision_agent';
    public const PROVIDER_ZKBIO_PLATFORM = 'zkbio_platform';
    public const PROVIDER_ZKTECO_AGENT = 'zkteco_agent';

    // -------------------------------------------------------------------------
    // Properties
    // -------------------------------------------------------------------------

    protected $fillable = [
        'branch_id',
        'integration_type',
        'provider',
        'access_control_agent_id',
        'name',
        'device_model',
        'device_type',
        'serial_number',
        'ip_address',
        'port',
        'username',
        'password_encrypted',
        'location_id',
        'status',
        'connection_status',
        'last_sync_at',
        'last_heartbeat_at',
        'last_error',
        'auto_sync_enabled',
        'sync_interval_minutes',
        'logs_synced_until',
        'firmware_version',
        'mac_address',
        'capabilities',
        'supports_face_recognition',
        'supports_fingerprint',
        'supports_card',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'supports_face_recognition' => 'boolean',
            'supports_fingerprint' => 'boolean',
            'supports_card' => 'boolean',
            'auto_sync_enabled' => 'boolean',
            'sync_interval_minutes' => 'integer',
            'last_sync_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
            'logs_synced_until' => 'datetime',
            'capabilities' => 'array',
            'integration_type' => 'string',
            'provider' => 'string',
        ];
    }

    // -------------------------------------------------------------------------
    // Accessors & Mutators
    // -------------------------------------------------------------------------

    /**
     * Get/Set password with encryption.
     */
    protected function password(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->password_encrypted ? Crypt::decryptString($this->password_encrypted) : null,
            set: fn($value) => ['password_encrypted' => $value ? Crypt::encryptString($value) : null],
        );
    }

    /**
     * Check if device is online.
     */
    protected function isOnline(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->connection_status === self::CONNECTION_ONLINE,
        );
    }

    /**
     * Get human-readable device type label.
     */
    protected function deviceTypeLabel(): Attribute
    {
        return Attribute::make(
            get: fn() => match ($this->device_type) {
                self::TYPE_ENTRY => __('Entry Only'),
                self::TYPE_EXIT => __('Exit Only'),
                self::TYPE_BIDIRECTIONAL => __('Entry & Exit'),
                default => __('Unknown'),
            },
        );
    }

    /**
     * Get human-readable model name.
     */
    protected function modelName(): Attribute
    {
        return Attribute::make(
            get: fn() => match ($this->device_model) {
                self::MODEL_DS_K1T808MFWX => 'DS-K1T808MFWX (Face/Fingerprint/Card)',
                self::MODEL_DS_K1T804A => 'DS-K1T804A (Face/Card)',
                self::MODEL_DS_K1T671M => 'DS-K1T671M (Face Terminal)',
                self::MODEL_DS_K1T341AM => 'DS-K1T341AM (Fingerprint)',
                default => $this->device_model,
            },
        );
    }

    // -------------------------------------------------------------------------
    // Static Methods
    // -------------------------------------------------------------------------

    /**
     * Get available device models.
     */
    public static function deviceModels(): array
    {
        return [
            self::MODEL_DS_K1T808MFWX => 'DS-K1T808MFWX (Face/Fingerprint/Card)',
            self::MODEL_DS_K1T804A => 'DS-K1T804A (Face/Card)',
            self::MODEL_DS_K1T671M => 'DS-K1T671M (Face Terminal)',
            self::MODEL_DS_K1T341AM => 'DS-K1T341AM (Fingerprint)',
        ];
    }

    /**
     * Get device types.
     */
    public static function deviceTypes(): array
    {
        return [
            self::TYPE_ENTRY => __('Entry Only'),
            self::TYPE_EXIT => __('Exit Only'),
            self::TYPE_BIDIRECTIONAL => __('Entry & Exit'),
        ];
    }

    /**
     * Get default capabilities for a device model.
     */
    public static function defaultCapabilities(string $model): array
    {
        return match ($model) {
            self::MODEL_DS_K1T808MFWX => [
                'face_recognition' => true,
                'fingerprint' => true,
                'card' => true,
                'temperature' => true,
                'mask_detection' => true,
                'max_faces' => 50000,
                'max_fingerprints' => 5000,
                'max_cards' => 100000,
            ],
            self::MODEL_DS_K1T804A => [
                'face_recognition' => true,
                'fingerprint' => false,
                'card' => true,
                'temperature' => false,
                'mask_detection' => true,
                'max_faces' => 3000,
                'max_cards' => 10000,
            ],
            self::MODEL_DS_K1T671M => [
                'face_recognition' => true,
                'fingerprint' => false,
                'card' => false,
                'temperature' => true,
                'mask_detection' => true,
                'max_faces' => 6000,
            ],
            self::MODEL_DS_K1T341AM => [
                'face_recognition' => false,
                'fingerprint' => true,
                'card' => true,
                'temperature' => false,
                'max_fingerprints' => 3000,
                'max_cards' => 100000,
            ],
            default => [
                'face_recognition' => false,
                'fingerprint' => false,
                'card' => true,
            ],
        };
    }

    /**
     * Get supported providers for an integration type.
     *
     * @return array<int, string>
     */
    public static function providersForIntegration(string $integration_type): array
    {
        return match ($integration_type) {
            self::INTEGRATION_ZKTECO => [
                self::PROVIDER_ZKBIO_PLATFORM,
            ],
            default => [self::PROVIDER_HIKVISION_AGENT],
        };
    }

    public static function integrationTypeForProvider(?string $provider): string
    {
        return match ($provider) {
            self::PROVIDER_ZKBIO_PLATFORM, self::PROVIDER_ZKTECO_AGENT => self::INTEGRATION_ZKTECO,
            default => self::INTEGRATION_HIKVISION,
        };
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeOnline($query)
    {
        return $query->where('connection_status', self::CONNECTION_ONLINE);
    }

    public function scopeOffline($query)
    {
        return $query->where('connection_status', self::CONNECTION_OFFLINE);
    }

    public function scopeNeedingSync($query)
    {
        return $query->where('auto_sync_enabled', true)
            ->where(function ($q) {
                $q->whereNull('last_sync_at')
                    ->orWhereRaw('last_sync_at < DATE_SUB(NOW(), INTERVAL sync_interval_minutes MINUTE)');
            });
    }

    public function scopeForIntegration($query, string $integration_type)
    {
        return $query->where('integration_type', $integration_type);
    }

    public function scopeHikvision($query)
    {
        return $query->where('integration_type', self::INTEGRATION_HIKVISION);
    }

    public function scopeZkteco($query)
    {
        return $query->where('integration_type', self::INTEGRATION_ZKTECO);
    }

    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }

    public function deviceCommands(): HasMany
    {
        return $this->hasMany(AccessControlDeviceCommand::class, 'access_control_device_id');
    }

    /**
     * Optional primary agent pointer (per device).
     */
    public function primaryAgent(): BelongsTo
    {
        return $this->belongsTo(AccessControlAgent::class, 'access_control_agent_id');
    }

    /**
     * Agents that are allowed to manage this device.
     */
    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(
            related: AccessControlAgent::class,
            table: 'access_control_agent_devices',
            foreignPivotKey: 'access_control_device_id',
            relatedPivotKey: 'access_control_agent_id',
        )
            ->using(AccessControlAgentDevice::class)
            ->withPivot(['branch_id'])
            ->withTimestamps();
    }

    // -------------------------------------------------------------------------
    // Instance Methods
    // -------------------------------------------------------------------------

    /**
     * Mark device as online.
     */
    public function markOnline(): void
    {
        $this->update([
            'connection_status' => self::CONNECTION_ONLINE,
            'last_heartbeat_at' => now(),
            'last_error' => null,
        ]);
    }

    /**
     * Mark device as offline.
     */
    public function markOffline(?string $error = null): void
    {
        $this->update([
            'connection_status' => self::CONNECTION_OFFLINE,
            'last_error' => $error,
        ]);
    }

    /**
     * Update last sync timestamp.
     */
    public function recordSync(\DateTimeInterface $synced_until = null): void
    {
        $this->update([
            'last_sync_at' => now(),
            'logs_synced_until' => $synced_until ?? now(),
        ]);
    }

    /**
     * Check if device needs sync based on interval.
     */
    public function needsSync(): bool
    {
        if (!$this->auto_sync_enabled) {
            return false;
        }

        if (!$this->last_sync_at) {
            return true;
        }

        return $this->last_sync_at->addMinutes($this->sync_interval_minutes)->isPast();
    }

    /**
     * Get supported authentication methods as array.
     */
    public function supportedMethods(): array
    {
        $methods = [];

        if ($this->supports_face_recognition) {
            $methods[] = 'face';
        }
        if ($this->supports_fingerprint) {
            $methods[] = 'fingerprint';
        }
        if ($this->supports_card) {
            $methods[] = 'card';
        }

        return $methods;
    }

    /**
     * Consider device offline if last heartbeat is older than threshold.
     */
    public function isHeartbeatStale(int $threshold_minutes): bool
    {
        if (!$this->last_heartbeat_at) {
            return true;
        }

        return Carbon::parse($this->last_heartbeat_at)->diffInMinutes(now()) > $threshold_minutes;
    }
}
