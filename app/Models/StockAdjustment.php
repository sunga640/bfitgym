<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockAdjustment extends Model
{
    use HasFactory, SoftDeletes, HasBranchScope;

    public const TYPE_INCREASE = 'increase';
    public const TYPE_DECREASE = 'decrease';

    protected $fillable = [
        'branch_id',
        'branch_product_id',
        'adjustment_type',
        'quantity',
        'reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeIncreases($query)
    {
        return $query->where('adjustment_type', self::TYPE_INCREASE);
    }

    public function scopeDecreases($query)
    {
        return $query->where('adjustment_type', self::TYPE_DECREASE);
    }

    public function scopeForProduct($query, int $branch_product_id)
    {
        return $query->where('branch_product_id', $branch_product_id);
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function branchProduct(): BelongsTo
    {
        return $this->belongsTo(BranchProduct::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

