<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ZktecoDevice extends Model
{
    use HasFactory;
    use HasBranchScope;

    protected $fillable = [
        'zkteco_connection_id',
        'branch_id',
        'remote_device_id',
        'remote_name',
        'remote_type',
        'remote_status',
        'is_online',
        'is_assignable',
        'last_seen_at',
        'remote_payload',
    ];

    protected function casts(): array
    {
        return [
            'is_online' => 'boolean',
            'is_assignable' => 'boolean',
            'last_seen_at' => 'datetime',
            'remote_payload' => 'array',
        ];
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(ZktecoConnection::class, 'zkteco_connection_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function branchMappings(): HasMany
    {
        return $this->hasMany(ZktecoBranchMapping::class);
    }

    public function accessEvents(): HasMany
    {
        return $this->hasMany(ZktecoAccessEvent::class);
    }

    public function memberDeviceAccess(): HasMany
    {
        return $this->hasMany(ZktecoMemberDeviceAccess::class);
    }
}

