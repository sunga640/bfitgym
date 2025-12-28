<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'currency',
        'timezone',
        'module_pos_enabled',
        'module_classes_enabled',
        'module_insurance_enabled',
        'module_access_control_enabled',
    ];

    protected function casts(): array
    {
        return [
            'module_pos_enabled' => 'boolean',
            'module_classes_enabled' => 'boolean',
            'module_insurance_enabled' => 'boolean',
            'module_access_control_enabled' => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}

