<?php

use App\Livewire\Reports\Memberships as MembershipReport;
use App\Models\Branch;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\MembershipPackage;
use App\Models\User;
use App\Services\Memberships\SubscriptionService;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Carbon::setTestNow(Carbon::create(2026, 3, 4, 10, 0, 0));

    Permission::firstOrCreate(['name' => 'view membership reports', 'guard_name' => 'web']);

    $this->branch = Branch::factory()->create();

    $this->user = User::factory()->create([
        'branch_id' => $this->branch->id,
    ]);
    $this->user->givePermissionTo('view membership reports');

    $this->monthly_package = MembershipPackage::factory()->active()->monthly()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Monthly Premium',
        'price' => 120000,
    ]);

    $this->quarterly_package = MembershipPackage::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Quarterly Flex',
        'duration_type' => 'months',
        'duration_value' => 3,
        'price' => 300000,
    ]);

    $this->renewed_member = Member::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'member_no' => 'MB-001',
    ]);

    $this->pending_member = Member::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'Bob',
        'last_name' => 'Smith',
        'member_no' => 'MB-002',
    ]);

    $this->historical_member = Member::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'Legacy',
        'last_name' => 'Member',
        'member_no' => 'MB-099',
    ]);

    $this->actingAs($this->user);

    /** @var SubscriptionService $service */
    $service = app(SubscriptionService::class);

    $original_subscription = $service->startSubscription($this->renewed_member, $this->monthly_package, [
        'start_date' => now()->subMonths(2)->startOfMonth()->format('Y-m-d'),
        'auto_renew' => true,
        'notes' => 'Initial cycle',
        'amount' => 120000,
        'currency' => 'TZS',
        'payment_method' => 'cash',
        'reference' => 'SUB-OLD-001',
        'paid_at' => now()->subMonths(2)->startOfMonth()->format('Y-m-d\TH:i'),
    ]);

    $service->renewSubscription($original_subscription, $this->monthly_package, [
        'start_date' => now()->startOfMonth()->format('Y-m-d'),
        'auto_renew' => true,
        'notes' => 'Renewed for the current month',
        'amount' => 120000,
        'currency' => 'TZS',
        'payment_method' => 'cash',
        'reference' => 'SUB-NEW-001',
        'paid_at' => now()->startOfMonth()->format('Y-m-d\TH:i'),
    ]);

    $service->startSubscription($this->pending_member, $this->quarterly_package, [
        'start_date' => now()->addDays(10)->format('Y-m-d'),
        'auto_renew' => false,
        'notes' => 'Pending activation',
        'amount' => 300000,
        'currency' => 'TZS',
        'payment_method' => 'card',
        'reference' => 'SUB-PENDING-001',
        'paid_at' => now()->format('Y-m-d\TH:i'),
    ]);

    MemberSubscription::create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->historical_member->id,
        'membership_package_id' => $this->monthly_package->id,
        'start_date' => now()->subMonths(5)->startOfMonth(),
        'end_date' => now()->subMonths(4)->endOfMonth(),
        'status' => 'expired',
        'auto_renew' => false,
        'notes' => 'Historical record outside the reporting period',
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
});

it('renders membership report data for the selected period and supports filtering', function () {
    Livewire::test(MembershipReport::class)
        ->set('period', 'custom')
        ->set('date_from', now()->startOfMonth()->format('Y-m-d'))
        ->set('date_to', now()->endOfMonth()->format('Y-m-d'))
        ->assertSee('Membership revenue')
        ->assertSee('Jane Doe')
        ->assertSee('Bob Smith')
        ->assertSee('Monthly Premium')
        ->assertSee('Quarterly Flex')
        ->assertDontSee('Legacy Member')
        ->set('status_filter', 'pending')
        ->assertSee('Bob Smith')
        ->assertDontSee('Jane Doe')
        ->set('search', 'Quarterly Flex')
        ->assertSee('Bob Smith')
        ->assertDontSee('Jane Doe');
});
