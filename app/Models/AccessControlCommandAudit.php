<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessControlCommandAudit extends Model
{
    use HasFactory;

    public const STATUS_RECEIVED = 'received';
    public const STATUS_STARTED = 'started';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';

    protected $table = 'access_control_command_audits';

    public $timestamps = false;

    protected $fillable = [
        'command_id',
        'agent_id',
        'status',
        'message',
        'result',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'result' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function command(): BelongsTo
    {
        return $this->belongsTo(AccessControlDeviceCommand::class, 'command_id', 'id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(AccessControlAgent::class, 'agent_id');
    }
}
