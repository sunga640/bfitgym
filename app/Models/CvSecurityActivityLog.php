<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CvSecurityActivityLog extends Model
{
    use HasFactory;
    use HasBranchScope;

    protected $table = 'cvsecurity_activity_logs';

    protected $fillable = [
        'cvsecurity_connection_id',
        'branch_id',
        'agent_id',
        'level',
        'event',
        'message',
        'context',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'occurred_at' => 'datetime',
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

    public function agent(): BelongsTo
    {
        return $this->belongsTo(CvSecurityAgent::class, 'agent_id');
    }
}

