<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkoutPlanDay extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workout_plan_id',
        'day_index',
        'day_of_week',
        'label',
        'is_rest_day',
    ];

    protected function casts(): array
    {
        return [
            'day_index' => 'integer',
            'day_of_week' => 'integer',
            'is_rest_day' => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForDayOfWeek($query, int $day)
    {
        return $query->where('day_of_week', $day);
    }

    public function scopeRestDays($query)
    {
        return $query->where('is_rest_day', true);
    }

    public function scopeWorkoutDays($query)
    {
        return $query->where('is_rest_day', false);
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function workoutPlan(): BelongsTo
    {
        return $this->belongsTo(WorkoutPlan::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(WorkoutActivity::class)->orderBy('order');
    }
}

