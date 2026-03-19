<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CvSecurityPairingToken extends Model
{
    use HasFactory;
    use HasBranchScope;

    protected $table = 'cvsecurity_pairing_tokens';

    protected $fillable = [
        'cvsecurity_connection_id',
        'branch_id',
        'created_by',
        'claimed_by_agent_id',
        'token_hash',
        'token_hint',
        'expires_at',
        'claimed_at',
        'revoked_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'claimed_at' => 'datetime',
            'revoked_at' => 'datetime',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function claimedByAgent(): BelongsTo
    {
        return $this->belongsTo(CvSecurityAgent::class, 'claimed_by_agent_id');
    }

    public function isUsable(): bool
    {
        return $this->claimed_at === null
            && $this->revoked_at === null
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }
}

