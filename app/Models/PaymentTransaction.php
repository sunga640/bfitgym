<?php

namespace App\Models;

use App\Models\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PaymentTransaction extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';

    public const PAYER_MEMBER = 'member';
    public const PAYER_INSURER = 'insurer';
    public const PAYER_OTHER = 'other';

    public const REVENUE_TYPE_MEMBERSHIP = 'membership';
    public const REVENUE_TYPE_CLASS_BOOKING = 'class_booking';
    public const REVENUE_TYPE_EVENT = 'event';
    public const REVENUE_TYPE_POS = 'pos';

    use HasFactory, HasBranchScope;

    protected $fillable = [
        'branch_id',
        'payer_type',
        'payer_member_id',
        'payer_insurer_id',
        'amount',
        'currency',
        'payment_method',
        'reference',
        'paid_at',
        'status',
        'revenue_type',
        'payable_type',
        'payable_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeForRevenueType($query, string $type)
    {
        return $query->where('revenue_type', $type);
    }

    public function scopeBetweenDates($query, $from, $to)
    {
        return $query->whereBetween('paid_at', [$from, $to]);
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function payerMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'payer_member_id');
    }

    public function payerInsurer(): BelongsTo
    {
        return $this->belongsTo(Insurer::class, 'payer_insurer_id');
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}

