<?php

use App\Livewire\Dashboard;
use App\Models\Branch;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\MembershipPackage;
use App\Models\PaymentTransaction;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'switch branches', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'view dashboard analytics', 'guard_name' => 'web']);

    $this->branch = Branch::factory()->create();

    $this->user = User::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->user->givePermissionTo(['view dashboard analytics']);
    $this->actingAs($this->user);
});

it('excludes deleted subscriptions revenue from dashboard totals and chart breakdown', function () {
    $member = Member::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    $package = MembershipPackage::factory()->active()->create([
        'branch_id' => $this->branch->id,
    ]);

    $subscription = MemberSubscription::create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'membership_package_id' => $package->id,
        'start_date' => now()->subDays(7),
        'end_date' => now()->addDays(23),
        'status' => 'active',
        'auto_renew' => false,
    ]);

    PaymentTransaction::create([
        'branch_id' => $this->branch->id,
        'payer_type' => PaymentTransaction::PAYER_MEMBER,
        'payer_member_id' => $member->id,
        'payer_insurer_id' => null,
        'amount' => 120000,
        'currency' => 'TZS',
        'payment_method' => 'cash',
        'reference' => 'SUB-DASH-001',
        'paid_at' => now(),
        'status' => PaymentTransaction::STATUS_PAID,
        'revenue_type' => PaymentTransaction::REVENUE_TYPE_MEMBERSHIP,
        'payable_type' => MemberSubscription::class,
        'payable_id' => $subscription->id,
        'notes' => null,
    ]);

    PaymentTransaction::create([
        'branch_id' => $this->branch->id,
        'payer_type' => PaymentTransaction::PAYER_OTHER,
        'payer_member_id' => null,
        'payer_insurer_id' => null,
        'amount' => 30000,
        'currency' => 'TZS',
        'payment_method' => 'cash',
        'reference' => 'POS-DASH-001',
        'paid_at' => now(),
        'status' => PaymentTransaction::STATUS_PAID,
        'revenue_type' => PaymentTransaction::REVENUE_TYPE_POS,
        'payable_type' => null,
        'payable_id' => null,
        'notes' => null,
    ]);

    $subscription->delete();

    $component = Livewire::test(Dashboard::class);

    $monthly_revenue = $component->instance()->monthlyRevenue();
    $chart_data = $component->instance()->revenueChartData();

    expect($monthly_revenue)->toBe(30000.0);
    expect((float) ($chart_data['total'] ?? 0))->toBe(30000.0);
    expect((float) ($chart_data['by_type']['membership'] ?? 0))->toBe(0.0);
    expect((float) ($chart_data['by_type']['pos'] ?? 0))->toBe(30000.0);
});
