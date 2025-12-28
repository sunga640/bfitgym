<?php

namespace App\Providers;

use App\Models\AccessControlDevice;
use App\Models\AccessControlAgent;
use App\Models\AccessControlAgentEnrollment;
use App\Models\AccessIdentity;
use App\Models\Branch;
use App\Models\ClassBooking;
use App\Models\ClassSession;
use App\Models\ClassType;
use App\Models\Equipment;
use App\Models\EquipmentAllocation;
use App\Models\Insurer;
use App\Models\Location;
use App\Models\Member;
use App\Models\MemberInsurance;
use App\Models\MemberSubscription;
use App\Models\MembershipPackage;
use App\Models\User;
use App\Models\WorkoutPlan;
use App\Observers\MemberInsuranceObserver;
use App\Observers\MemberObserver;
use App\Observers\MemberSubscriptionObserver;
use App\Policies\AccessControlDevicePolicy;
use App\Policies\AccessControlAgentPolicy;
use App\Policies\AccessControlAgentEnrollmentPolicy;
use App\Policies\AccessIdentityPolicy;
use App\Policies\BranchPolicy;
use App\Policies\ClassBookingPolicy;
use App\Policies\ClassSessionPolicy;
use App\Policies\ClassTypePolicy;
use App\Policies\EquipmentAllocationPolicy;
use App\Policies\EquipmentPolicy;
use App\Policies\InsurerPolicy;
use App\Policies\LocationPolicy;
use App\Policies\MemberPolicy;
use App\Policies\MemberSubscriptionPolicy;
use App\Policies\MembershipPackagePolicy;
use App\Policies\UserPolicy;
use App\Policies\WorkoutPlanPolicy;
use App\Services\BranchContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register BranchContext as a singleton
        $this->app->singleton(BranchContext::class, function ($app) {
            return new BranchContext();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Rate limiter for agent endpoints (apply with "throttle:agent")
        RateLimiter::for('agent', function (Request $request) {
            $agent_uuid = (string) $request->header('X-Agent-UUID', '');

            return Limit::perMinute(120)->by($agent_uuid !== '' ? $agent_uuid : $request->ip());
        });

        // Register policies
        Gate::policy(Branch::class, BranchPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Member::class, MemberPolicy::class);
        Gate::policy(MembershipPackage::class, MembershipPackagePolicy::class);
        Gate::policy(MemberSubscription::class, MemberSubscriptionPolicy::class);
        Gate::policy(Insurer::class, InsurerPolicy::class);
        Gate::policy(ClassType::class, ClassTypePolicy::class);
        Gate::policy(ClassSession::class, ClassSessionPolicy::class);
        Gate::policy(ClassBooking::class, ClassBookingPolicy::class);
        Gate::policy(Equipment::class, EquipmentPolicy::class);
        Gate::policy(EquipmentAllocation::class, EquipmentAllocationPolicy::class);
        Gate::policy(AccessControlDevice::class, AccessControlDevicePolicy::class);
        Gate::policy(AccessControlAgent::class, AccessControlAgentPolicy::class);
        Gate::policy(AccessControlAgentEnrollment::class, AccessControlAgentEnrollmentPolicy::class);
        Gate::policy(AccessIdentity::class, AccessIdentityPolicy::class);
        Gate::policy(Location::class, LocationPolicy::class);
        Gate::policy(WorkoutPlan::class, WorkoutPlanPolicy::class);

        // Model observers (must not call device/network; only enqueue outbox commands)
        Member::observe(MemberObserver::class);
        MemberSubscription::observe(MemberSubscriptionObserver::class);
        MemberInsurance::observe(MemberInsuranceObserver::class);

        // Super-admin bypass for all gates
        Gate::before(function (User $user, string $ability) {
            if ($user->hasRole('super-admin')) {
                return true;
            }
        });
    }
}
