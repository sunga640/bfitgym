<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AccessControlAgentDevice extends Pivot
{
    use HasBranchScope;

    protected $table = 'access_control_agent_devices';

    public $timestamps = true;

    protected $fillable = [
        'branch_id',
        'access_control_agent_id',
        'access_control_device_id',
    ];
}
