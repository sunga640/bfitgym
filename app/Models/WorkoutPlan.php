<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkoutPlan extends Model
{
    use HasFactory, SoftDeletes, HasBranchScope;

    protected $fillable = [
        'branch_id',
        'name',
        'level',
        'description',
        'total_weeks',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'total_weeks' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function days(): HasMany
    {
        return $this->hasMany(WorkoutPlanDay::class)->orderBy('day_index');
    }

    public function memberWorkoutPlans(): HasMany
    {
        return $this->hasMany(MemberWorkoutPlan::class);
    }
}

