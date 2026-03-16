<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZktecoMemberDeviceAccess extends Model
{
    use HasFactory;

    protected $table = 'zkteco_member_device_access';

    protected $fillable = [
        'zkteco_member_map_id',
        'zkteco_device_id',
        'access_granted',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'access_granted' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function memberMap(): BelongsTo
    {
        return $this->belongsTo(ZktecoMemberMap::class, 'zkteco_member_map_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(ZktecoDevice::class, 'zkteco_device_id');
    }
}

