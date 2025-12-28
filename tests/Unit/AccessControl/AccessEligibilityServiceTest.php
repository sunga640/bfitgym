<?php

namespace Tests\Unit\AccessControl;

use App\Models\Insurer;
use App\Models\Member;
use App\Models\MemberInsurance;
use App\Models\MemberSubscription;
use App\Models\MembershipPackage;
use App\Services\AccessControl\AccessEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AccessEligibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_member_denied(): void
    {
        $member = Member::factory()->inactive()->create();

        $service = app(AccessEligibilityService::class);

        $this->assertFalse($service->is_member_allowed($member));
        $this->assertNull($service->allowed_until($member));
    }

    public function test_active_subscription_valid_allowed(): void
    {
        $today = Carbon::parse('2025-01-10');
        Carbon::setTestNow($today);

        $member = Member::factory()->create(['status' => 'active']);
        $package = MembershipPackage::factory()->active()->create(['branch_id' => $member->branch_id]);

        MemberSubscription::create([
            'branch_id' => $member->branch_id,
            'member_id' => $member->id,
            'membership_package_id' => $package->id,
            'renewed_from_id' => null,
            'start_date' => $today->copy()->subMonth()->toDateString(),
            'end_date' => $today->copy()->addDays(10)->toDateString(),
            'status' => 'active',
            'auto_renew' => false,
            'notes' => null,
        ]);

        $service = app(AccessEligibilityService::class);

        $this->assertTrue($service->is_member_allowed($member));
        $this->assertTrue($service->allowed_until($member)->isSameDay($today->copy()->addDays(10)));

        Carbon::setTestNow();
    }

    public function test_subscription_expired_denied_unless_insurance_valid(): void
    {
        $today = Carbon::parse('2025-01-10');
        Carbon::setTestNow($today);

        $member = Member::factory()->create(['status' => 'active']);
        $package = MembershipPackage::factory()->active()->create(['branch_id' => $member->branch_id]);

        MemberSubscription::create([
            'branch_id' => $member->branch_id,
            'member_id' => $member->id,
            'membership_package_id' => $package->id,
            'renewed_from_id' => null,
            'start_date' => $today->copy()->subMonth()->toDateString(),
            'end_date' => $today->copy()->subDay()->toDateString(),
            'status' => 'active',
            'auto_renew' => false,
            'notes' => null,
        ]);

        $service = app(AccessEligibilityService::class);

        $this->assertFalse($service->is_member_allowed($member));

        $insurer = Insurer::create([
            'name' => 'Test Insurer',
            'contact_person' => null,
            'phone' => null,
            'email' => null,
            'address' => null,
            'status' => 'active',
        ]);

        MemberInsurance::create([
            'member_id' => $member->id,
            'insurer_id' => $insurer->id,
            'policy_number' => 'POL-123',
            'coverage_type' => null,
            'start_date' => $today->copy()->subMonth()->toDateString(),
            'end_date' => $today->copy()->addMonth()->toDateString(),
            'status' => 'active',
            'notes' => null,
        ]);

        $this->assertTrue($service->is_member_allowed($member));

        Carbon::setTestNow();
    }

    public function test_insurance_valid_date_range_allowed(): void
    {
        $today = Carbon::parse('2025-01-10');
        Carbon::setTestNow($today);

        $member = Member::factory()->create(['status' => 'active']);

        $insurer = Insurer::create([
            'name' => 'Test Insurer',
            'contact_person' => null,
            'phone' => null,
            'email' => null,
            'address' => null,
            'status' => 'active',
        ]);

        $end = $today->copy()->addDays(20);

        MemberInsurance::create([
            'member_id' => $member->id,
            'insurer_id' => $insurer->id,
            'policy_number' => 'POL-456',
            'coverage_type' => null,
            'start_date' => $today->copy()->subDays(3)->toDateString(),
            'end_date' => $end->toDateString(),
            'status' => 'active',
            'notes' => null,
        ]);

        $service = app(AccessEligibilityService::class);

        $this->assertTrue($service->is_member_allowed($member));
        $this->assertTrue($service->allowed_until($member)->isSameDay($end));

        Carbon::setTestNow();
    }

    public function test_insurance_end_date_null_allowed_until_null(): void
    {
        $today = Carbon::parse('2025-01-10');
        Carbon::setTestNow($today);

        $member = Member::factory()->create(['status' => 'active']);

        $insurer = Insurer::create([
            'name' => 'Test Insurer',
            'contact_person' => null,
            'phone' => null,
            'email' => null,
            'address' => null,
            'status' => 'active',
        ]);

        MemberInsurance::create([
            'member_id' => $member->id,
            'insurer_id' => $insurer->id,
            'policy_number' => 'POL-789',
            'coverage_type' => null,
            'start_date' => $today->copy()->subDays(10)->toDateString(),
            'end_date' => null,
            'status' => 'active',
            'notes' => null,
        ]);

        $service = app(AccessEligibilityService::class);

        $this->assertTrue($service->is_member_allowed($member));
        $this->assertNull($service->allowed_until($member));

        Carbon::setTestNow();
    }
}
