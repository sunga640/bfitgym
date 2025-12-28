<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkoutActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workout_plan_day_id',
        'exercise_id',
        'exercise_name',
        'sets',
        'reps',
        'duration_seconds',
        'notes',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'sets' => 'integer',
            'reps' => 'integer',
            'duration_seconds' => 'integer',
            'order' => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function workoutPlanDay(): BelongsTo
    {
        return $this->belongsTo(WorkoutPlanDay::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}

