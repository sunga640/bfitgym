<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ZktecoConnection extends Model
{
    use HasFactory;
    use HasBranchScope;

    public const PROVIDER_ZKBIO_API = 'zkbio_api';

    public const STATUS_CONNECTED = 'connected';
    public const STATUS_DISCONNECTED = 'disconnected';
    public const STATUS_ERROR = 'error';
    public const STATUS_UNREACHABLE = 'unreachable';
    public const STATUS_UNSUPPORTED = 'unsupported';

    protected $fillable = [
        'branch_id',
        'provider',
        'status',
        'base_url',
        'port',
        'username',
        'password',
        'api_key',
        'ssl_enabled',
        'allow_self_signed',
        'timeout_seconds',
        'last_tested_at',
        'last_test_success_at',
        'last_personnel_sync_at',
        'last_event_sync_at',
        'disconnected_at',
        'last_error',
        'metadata',
    ];

    protected $hidden = [
        'password',
        'api_key',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'password' => 'encrypted',
            'api_key' => 'encrypted',
            'ssl_enabled' => 'boolean',
            'allow_self_signed' => 'boolean',
            'timeout_seconds' => 'integer',
            'last_tested_at' => 'datetime',
            'last_test_success_at' => 'datetime',
            'last_personnel_sync_at' => 'datetime',
            'last_event_sync_at' => 'datetime',
            'disconnected_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(ZktecoDevice::class);
    }

    public function branchMappings(): HasMany
    {
        return $this->hasMany(ZktecoBranchMapping::class);
    }

    public function syncRuns(): HasMany
    {
        return $this->hasMany(ZktecoSyncRun::class);
    }

    public function accessEvents(): HasMany
    {
        return $this->hasMany(ZktecoAccessEvent::class);
    }

    public function memberMaps(): HasMany
    {
        return $this->hasMany(ZktecoMemberMap::class);
    }

    public function scopeEnabled($query)
    {
        return $query->whereNotIn('status', [self::STATUS_DISCONNECTED]);
    }

    public function resolvedBaseUrl(): string
    {
        $url = trim((string) $this->base_url);

        if ($url === '') {
            return '';
        }

        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $scheme = $this->ssl_enabled ? 'https://' : 'http://';
            $url = $scheme . $url;
        }

        $parsed = parse_url($url);
        if (!$parsed) {
            return rtrim($url, '/');
        }

        $host = $parsed['host'] ?? null;
        if (!$host) {
            return rtrim($url, '/');
        }

        $scheme = $parsed['scheme'] ?? ($this->ssl_enabled ? 'https' : 'http');
        $port = $parsed['port'] ?? $this->port;
        $path = $parsed['path'] ?? '';

        $full = "{$scheme}://{$host}";
        if ($port) {
            $full .= ':' . $port;
        }

        if ($path !== '') {
            $full .= '/' . ltrim($path, '/');
        }

        return rtrim($full, '/');
    }

    public function host(): ?string
    {
        $parsed = parse_url($this->resolvedBaseUrl());

        return $parsed['host'] ?? null;
    }

    public function hasPrivateHost(): bool
    {
        $host = $this->host();

        if (!$host || !filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        return filter_var(
            $host,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}

