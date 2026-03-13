<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class AccessIntegrationConfig extends Model
{
    use HasFactory, HasBranchScope;

    public const MODE_PLATFORM = 'platform';
    public const MODE_AGENT = 'agent';

    public const HEALTH_HEALTHY = 'healthy';
    public const HEALTH_DEGRADED = 'degraded';
    public const HEALTH_DOWN = 'down';
    public const HEALTH_UNKNOWN = 'unknown';

    protected $fillable = [
        'branch_id',
        'integration_type',
        'mode',
        'provider',
        'is_enabled',
        'sync_enabled',
        'agent_fallback_enabled',
        'platform_base_url',
        'platform_username',
        'platform_password_encrypted',
        'platform_site_code',
        'platform_client_id',
        'platform_client_secret_encrypted',
        'last_sync_at',
        'last_health_check_at',
        'health_status',
        'last_health_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'sync_enabled' => 'boolean',
            'agent_fallback_enabled' => 'boolean',
            'last_sync_at' => 'datetime',
            'last_health_check_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    protected function platformPassword(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->platform_password_encrypted
                ? Crypt::decryptString($this->platform_password_encrypted)
                : null,
            set: fn($value) => ['platform_password_encrypted' => $value ? Crypt::encryptString($value) : null],
        );
    }

    protected function platformClientSecret(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->platform_client_secret_encrypted
                ? Crypt::decryptString($this->platform_client_secret_encrypted)
                : null,
            set: fn($value) => ['platform_client_secret_encrypted' => $value ? Crypt::encryptString($value) : null],
        );
    }

    public function scopeForIntegration($query, string $integration_type)
    {
        return $query->where('integration_type', $integration_type);
    }
}

