<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory, SoftDeletes, HasBranchScope;

    protected $fillable = [
        'branch_id',
        'user_id',
        'member_no',
        'first_name',
        'last_name',
        'gender',
        'dob',
        'phone',
        'email',
        'address',
        'status',
        'has_insurance',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'has_insurance' => 'boolean',
        ];
    }

    public static function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', trim($phone));

        if ($digits === null || $digits === '') {
            return '';
        }

        if (str_starts_with($digits, '0')) {
            return '255' . substr($digits, 1);
        }

        if (str_starts_with($digits, '255')) {
            return $digits;
        }

        return $digits;
    }

    public static function normalizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $normalized = strtolower(trim($email));

        return $normalized === '' ? null : $normalized;
    }

    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = static::normalizePhone($value);
    }

    public function setEmailAttribute(?string $value): void
    {
        $this->attributes['email'] = static::normalizeEmail($value);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeWithInsurance($query)
    {
        return $query->where('has_insurance', true);
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(MemberSubscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(MemberSubscription::class)
            ->where('status', 'active')
            ->latest('start_date');
    }

    public function insurances(): HasMany
    {
        return $this->hasMany(MemberInsurance::class);
    }

    public function activeInsurance()
    {
        return $this->hasOne(MemberInsurance::class)
            ->where('status', 'active')
            ->latest('start_date');
    }

    public function classBookings(): HasMany
    {
        return $this->hasMany(ClassBooking::class);
    }

    public function workoutPlans(): HasMany
    {
        return $this->hasMany(MemberWorkoutPlan::class);
    }

    public function eventRegistrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function accessIdentities(): HasMany
    {
        return $this->hasMany(AccessIdentity::class, 'subject_id')
            ->where('subject_type', 'member');
    }

    public function posSales(): HasMany
    {
        return $this->hasMany(PosSale::class);
    }

    // -------------------------------------------------------------------------
    // Fingerprint/Access Control Helper Methods
    // -------------------------------------------------------------------------

    /**
     * Check if member has an active subscription with end_date >= today.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('end_date', '>=', now()->startOfDay())
            ->exists();
    }

    /**
     * Check if member has an enrolled fingerprint.
     * An enrolled fingerprint means is_active=true AND fingerprint_enrolled_at is not null.
     */
    public function hasActiveFingerprint(): bool
    {
        return $this->accessIdentities()
            ->where('is_active', true)
            ->whereNotNull('fingerprint_enrolled_at')
            ->exists();
    }

    /**
     * Check if member has a registered access identity (may or may not be enrolled).
     */
    public function hasAccessIdentity(): bool
    {
        return $this->accessIdentities()->exists();
    }

    /**
     * Get the member's active access identity (if any).
     */
    public function getActiveAccessIdentity(): ?AccessIdentity
    {
        return $this->accessIdentities()
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get the member's current active subscription (if any).
     */
    public function getActiveSubscription(): ?MemberSubscription
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('end_date', '>=', now()->startOfDay())
            ->latest('start_date')
            ->first();
    }

    /**
     * Check if member can have fingerprint enrolled.
     * Requires: active subscription AND no existing enrolled fingerprint.
     */
    public function canEnrollFingerprint(): bool
    {
        return $this->hasActiveSubscription() && !$this->hasActiveFingerprint();
    }
}
