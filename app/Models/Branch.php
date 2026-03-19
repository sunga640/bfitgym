<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'address',
        'city',
        'country',
        'phone',
        'email',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Scope a query to only include active branches.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * Get all users belonging to this branch.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all members belonging to this branch.
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /**
     * Get all membership packages for this branch.
     */
    public function membershipPackages(): HasMany
    {
        return $this->hasMany(MembershipPackage::class);
    }

    /**
     * Get all member subscriptions for this branch.
     */
    public function memberSubscriptions(): HasMany
    {
        return $this->hasMany(MemberSubscription::class);
    }

    /**
     * Get all payment transactions for this branch.
     */
    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * Get all class types for this branch.
     */
    public function classTypes(): HasMany
    {
        return $this->hasMany(ClassType::class);
    }

    /**
     * Get all class sessions for this branch.
     */
    public function classSessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }

    /**
     * Get all class bookings for this branch.
     */
    public function classBookings(): HasMany
    {
        return $this->hasMany(ClassBooking::class);
    }

    /**
     * Get all workout plans for this branch.
     */
    public function workoutPlans(): HasMany
    {
        return $this->hasMany(WorkoutPlan::class);
    }

    /**
     * Get all member workout plans for this branch.
     */
    public function memberWorkoutPlans(): HasMany
    {
        return $this->hasMany(MemberWorkoutPlan::class);
    }

    /**
     * Get all events for this branch.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get all event registrations for this branch.
     */
    public function eventRegistrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * Get all access control devices for this branch.
     */
    public function accessControlDevices(): HasMany
    {
        return $this->hasMany(AccessControlDevice::class);
    }

    /**
     * Get all access control agents for this branch.
     */
    public function accessControlAgents(): HasMany
    {
        return $this->hasMany(AccessControlAgent::class);
    }

    /**
     * Get all access control agent enrollments for this branch.
     */
    public function accessControlAgentEnrollments(): HasMany
    {
        return $this->hasMany(AccessControlAgentEnrollment::class);
    }

    /**
     * Get all access identities for this branch.
     */
    public function accessIdentities(): HasMany
    {
        return $this->hasMany(AccessIdentity::class);
    }

    /**
     * Get all access logs for this branch.
     */
    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }

    /**
     * Get integration-level access control configuration records.
     */
    public function accessIntegrationConfigs(): HasMany
    {
        return $this->hasMany(AccessIntegrationConfig::class);
    }

    public function zktecoConnections(): HasMany
    {
        return $this->hasMany(ZktecoConnection::class);
    }

    public function zktecoDevices(): HasMany
    {
        return $this->hasMany(ZktecoDevice::class);
    }

    public function zktecoBranchMappings(): HasMany
    {
        return $this->hasMany(ZktecoBranchMapping::class);
    }

    public function zktecoSyncRuns(): HasMany
    {
        return $this->hasMany(ZktecoSyncRun::class);
    }

    public function zktecoAccessEvents(): HasMany
    {
        return $this->hasMany(ZktecoAccessEvent::class);
    }

    public function zktecoMemberMaps(): HasMany
    {
        return $this->hasMany(ZktecoMemberMap::class);
    }

    public function cvSecurityConnections(): HasMany
    {
        return $this->hasMany(CvSecurityConnection::class, 'branch_id');
    }

    public function cvSecurityAgents(): HasMany
    {
        return $this->hasMany(CvSecurityAgent::class, 'branch_id');
    }

    public function cvSecurityEvents(): HasMany
    {
        return $this->hasMany(CvSecurityEvent::class, 'branch_id');
    }

    public function cvSecuritySyncItems(): HasMany
    {
        return $this->hasMany(CvSecurityMemberSyncItem::class, 'branch_id');
    }

    /**
     * Get all expense categories for this branch.
     */
    public function expenseCategories(): HasMany
    {
        return $this->hasMany(ExpenseCategory::class);
    }

    /**
     * Get all expenses for this branch.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get all branch products (inventory) for this branch.
     */
    public function branchProducts(): HasMany
    {
        return $this->hasMany(BranchProduct::class);
    }

    /**
     * Get all purchase orders for this branch.
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Get all stock adjustments for this branch.
     */
    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    /**
     * Get all POS sales for this branch.
     */
    public function posSales(): HasMany
    {
        return $this->hasMany(PosSale::class);
    }

    /**
     * Get all locations for this branch.
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Get all equipment allocations for this branch.
     */
    public function equipmentAllocations(): HasMany
    {
        return $this->hasMany(EquipmentAllocation::class);
    }

    /**
     * Get the settings for this branch.
     */
    public function setting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(BranchSetting::class);
    }

    /**
     * Get or create the settings for this branch.
     */
    public function getOrCreateSetting(): BranchSetting
    {
        return $this->setting ?? $this->setting()->create([
            'currency' => 'TZS',
            'module_pos_enabled' => true,
            'module_classes_enabled' => true,
            'module_insurance_enabled' => true,
            'module_access_control_enabled' => true,
        ]);
    }
}
