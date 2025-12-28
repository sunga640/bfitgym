<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipPackage extends Model
{
    use HasFactory, SoftDeletes, HasBranchScope;

    protected $fillable = [
        'branch_id',
        'name',
        'description',
        'price',
        'duration_type',
        'duration_value',
        'is_renewable',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration_value' => 'integer',
            'is_renewable' => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(MemberSubscription::class);
    }

    // -------------------------------------------------------------------------
    // Accessors & Helpers
    // -------------------------------------------------------------------------

    /**
     * Get the formatted duration string (e.g., "1 Month", "3 Months").
     */
    public function getFormattedDurationAttribute(): string
    {
        $type = rtrim($this->duration_type, 's');

        if ($this->duration_value === 1) {
            return "1 " . ucfirst($type);
        }

        return "{$this->duration_value} " . ucfirst($this->duration_type);
    }

    /**
     * Check if the package is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the package has active subscriptions.
     */
    public function hasActiveSubscriptions(): bool
    {
        return $this->subscriptions()->where('status', 'active')->exists();
    }
}
