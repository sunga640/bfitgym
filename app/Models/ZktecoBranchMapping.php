<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZktecoBranchMapping extends Model
{
    use HasFactory;
    use HasBranchScope;

    protected $fillable = [
        'branch_id',
        'zkteco_connection_id',
        'zkteco_device_id',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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

    public function device(): BelongsTo
    {
        return $this->belongsTo(ZktecoDevice::class, 'zkteco_device_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

