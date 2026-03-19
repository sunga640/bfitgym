<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class CvSecurityConnection extends Model
{
    use HasFactory;
    use HasBranchScope;

    public const STATUS_DISCONNECTED = 'disconnected';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAIRED = 'paired';
    public const STATUS_CONNECTED = 'connected';
    public const STATUS_ERROR = 'error';
    public const STATUS_DISABLED = 'disabled';

    public const PAIRING_UNPAIRED = 'unpaired';
    public const PAIRING_TOKEN_ISSUED = 'token_issued';
    public const PAIRING_PAIRED = 'paired';
    public const PAIRING_EXPIRED = 'expired';

    protected $table = 'cvsecurity_connections';

    protected $fillable = [
        'branch_id',
        'name',
        'status',
        'pairing_status',
        'agent_status',
        'cvsecurity_status',
        'agent_label',
        'cv_base_url',
        'cv_port',
        'cv_username',
        'cv_password_encrypted',
        'cv_api_token_encrypted',
        'poll_interval_seconds',
        'timezone',
        'notes',
        'agent_test_requested',
        'agent_sync_requested',
        'agent_event_pull_requested',
        'last_heartbeat_at',
        'last_sync_at',
        'last_event_at',
        'last_error_at',
        'last_tested_at',
        'last_error',
        'last_test_result',
        'disabled_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'cv_port' => 'integer',
            'poll_interval_seconds' => 'integer',
            'agent_test_requested' => 'boolean',
            'agent_sync_requested' => 'boolean',
            'agent_event_pull_requested' => 'boolean',
            'last_heartbeat_at' => 'datetime',
            'last_sync_at' => 'datetime',
            'last_event_at' => 'datetime',
            'last_error_at' => 'datetime',
            'last_tested_at' => 'datetime',
            'disabled_at' => 'datetime',
            'last_test_result' => 'array',
        ];
    }

    protected function cvPassword(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->cv_password_encrypted ? Crypt::decryptString($this->cv_password_encrypted) : null,
            set: fn ($value) => ['cv_password_encrypted' => $value ? Crypt::encryptString((string) $value) : null],
        );
    }

    protected function cvApiToken(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->cv_api_token_encrypted ? Crypt::decryptString($this->cv_api_token_encrypted) : null,
            set: fn ($value) => ['cv_api_token_encrypted' => $value ? Crypt::encryptString((string) $value) : null],
        );
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function agents(): HasMany
    {
        return $this->hasMany(CvSecurityAgent::class, 'cvsecurity_connection_id');
    }

    public function pairingTokens(): HasMany
    {
        return $this->hasMany(CvSecurityPairingToken::class, 'cvsecurity_connection_id');
    }

    public function syncState(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CvSecuritySyncState::class, 'cvsecurity_connection_id');
    }

    public function syncItems(): HasMany
    {
        return $this->hasMany(CvSecurityMemberSyncItem::class, 'cvsecurity_connection_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(CvSecurityEvent::class, 'cvsecurity_connection_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(CvSecurityActivityLog::class, 'cvsecurity_connection_id');
    }
}

