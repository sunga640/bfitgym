<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class CvSecurityAgent extends Model
{
    use HasFactory;
    use HasBranchScope;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REVOKED = 'revoked';
    public const STATUS_OFFLINE = 'offline';

    protected $table = 'cvsecurity_agents';

    protected $fillable = [
        'cvsecurity_connection_id',
        'branch_id',
        'uuid',
        'display_name',
        'status',
        'os',
        'app_version',
        'last_ip',
        'auth_token_hash',
        'auth_token_encrypted',
        'paired_at',
        'last_seen_at',
        'last_heartbeat_at',
        'last_error_at',
        'last_error',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'paired_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
            'last_error_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(CvSecurityConnection::class, 'cvsecurity_connection_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function pairingTokens(): HasMany
    {
        return $this->hasMany(CvSecurityPairingToken::class, 'claimed_by_agent_id');
    }

    public function claimedSyncItems(): HasMany
    {
        return $this->hasMany(CvSecurityMemberSyncItem::class, 'claimed_by_agent_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(CvSecurityEvent::class, 'agent_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(CvSecurityActivityLog::class, 'agent_id');
    }

    public function decryptedAuthToken(): ?string
    {
        if (!$this->auth_token_encrypted) {
            return null;
        }

        return Crypt::decryptString($this->auth_token_encrypted);
    }
}

