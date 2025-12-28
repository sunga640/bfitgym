<?php

namespace App\Models\Traits;

use App\Models\Branch;
use App\Models\Scopes\BranchScope;
use App\Services\BranchContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasBranchScope
{
    /**
     * Boot the trait.
     */
    public static function bootHasBranchScope(): void
    {
        // Apply global scope for branch filtering
        static::addGlobalScope(new BranchScope());

        // Auto-set branch_id on creating
        static::creating(function ($model) {
            if (empty($model->branch_id)) {
                $branch_context = app(BranchContext::class);
                $model->branch_id = $branch_context->getCurrentBranchId();
            }
        });
    }

    /**
     * Get the branch that owns the model.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Scope a query to a specific branch.
     */
    public function scopeForBranch($query, int $branch_id)
    {
        return $query->withoutGlobalScope(BranchScope::class)
            ->where('branch_id', $branch_id);
    }

    /**
     * Scope a query to ignore branch scoping (use with caution).
     */
    public function scopeWithoutBranchScope($query)
    {
        return $query->withoutGlobalScope(BranchScope::class);
    }

    /**
     * Scope a query to include all branches (alias for withoutBranchScope).
     */
    public function scopeAllBranches($query)
    {
        return $query->withoutGlobalScope(BranchScope::class);
    }
}

