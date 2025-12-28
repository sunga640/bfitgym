<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes, HasBranchScope;

    protected $fillable = [
        'branch_id',
        'title',
        'description',
        'type',
        'location',
        'start_datetime',
        'end_datetime',
        'price',
        'capacity',
        'allow_non_members',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
            'price' => 'decimal:2',
            'capacity' => 'integer',
            'allow_non_members' => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getIsPaidAttribute(): bool
    {
        return $this->type === 'paid' && $this->price > 0;
    }

    public function getRemainingCapacityAttribute(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }

        return max(0, $this->capacity - $this->registrations()->whereIn('status', ['confirmed', 'attended'])->count());
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_datetime', '>', now());
    }

    public function scopePast($query)
    {
        return $query->where('start_datetime', '<', now());
    }

    public function scopePublic($query)
    {
        return $query->where('type', 'public');
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }
}

