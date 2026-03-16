<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ZktecoMemberMap extends Model
{
    use HasFactory;
    use HasBranchScope;

    public const BIOMETRIC_PENDING = 'pending';
    public const BIOMETRIC_ENROLLED = 'enrolled';
    public const BIOMETRIC_UNKNOWN = 'unknown';

    protected $fillable = [
        'branch_id',
        'zkteco_connection_id',
        'member_id',
        'remote_personnel_id',
        'remote_personnel_code',
        'biometric_status',
        'access_active',
        'last_synced_at',
        'last_error',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'access_active' => 'boolean',
            'last_synced_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(ZktecoConnection::class, 'zkteco_connection_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function deviceAccess(): HasMany
    {
        return $this->hasMany(ZktecoMemberDeviceAccess::class, 'zkteco_member_map_id');
    }
}

