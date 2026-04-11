<?php

use App\Livewire\Subscriptions\Show as SubscriptionShow;
use App\Models\Branch;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\MembershipPackage;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Revenue\RevenueReportService;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    foreach (['view subscriptions', 'delete subscriptions', 'edit subscriptions', 'renew subscriptions', 'switch branches'] as $permission_name) {
        Permission::firstOrCreate(['name' => $permission_name, 'guard_name' => 'web']);
    }

    $this->branch = Branch::factory()->create();

    $this->member = Member::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->package = MembershipPackage::factory()->active()->create([
        'branch_id' => $this->branch->id,
    ]);
});

it('allows authorized users to soft delete a subscription', function () {
    $user = User::factory()->create([
        'branch_id' => $this->branch->id,
    ]);
    $user->givePermissionTo(['view subscriptions', 'delete subscriptions']);

    $subscription = MemberSubscription::create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'membership_package_id' => $this->package->id,
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(20),
        'status' => 'active',
        'auto_renew' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(SubscriptionShow::class, ['subscription' => $subscription])
        ->call('deleteSubscription')
        ->assertRedirect(route('subscriptions.index'));

    $this->assertSoftDeleted('member_subscriptions', ['id' => $subscription->id]);
});

it('does not delete a subscription when user lacks delete permission', function () {
    $user = User::factory()->create([
        'branch_id' => $this->branch->id,
    ]);
    $user->givePermissionTo(['view subscriptions']);

    $subscription = MemberSubscription::create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'membership_package_id' => $this->package->id,
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(20),
        'status' => 'active',
        'auto_renew' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(SubscriptionShow::class, ['subscription' => $subscription])
        ->call('deleteSubscription');

    expect($subscription->fresh()?->deleted_at)->toBeNull();
});

it('excludes deleted subscriptions membership revenue from revenue reports', function () {
    $subscription = MemberSubscription::create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'membership_package_id' => $this->package->id,
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(20),
        'status' => 'active',
        'auto_renew' => false,
    ]);

    PaymentTransaction::create([
        'branch_id' => $this->branch->id,
        'payer_type' => PaymentTransaction::PAYER_MEMBER,
        'payer_member_id' => $this->member->id,
        'payer_insurer_id' => null,
        'amount' => 100000,
        'currency' => 'TZS',
        'payment_method' => 'cash',
        'reference' => 'SUB-DEL-001',
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
        'amount' => 25000,
        'currency' => 'TZS',
        'payment_method' => 'cash',
        'reference' => 'POS-001',
        'paid_at' => now(),
        'status' => PaymentTransaction::STATUS_PAID,
        'revenue_type' => PaymentTransaction::REVENUE_TYPE_POS,
        'payable_type' => null,
        'payable_id' => null,
        'notes' => null,
    ]);

    /** @var RevenueReportService $service */
    $service = app(RevenueReportService::class);
    $from = now()->subDays(30)->startOfDay();
    $to = now()->addDay()->endOfDay();

    $before_delete = $service->getRevenueBySource($this->branch->id, $from, $to);
    expect($before_delete['membership'])->toBe(100000.0);
    expect($before_delete['total'])->toBe(125000.0);

    $subscription->delete();

    $after_delete = $service->getRevenueBySource($this->branch->id, $from, $to);
    expect($after_delete['membership'])->toBe(0.0);
    expect($after_delete['pos'])->toBe(25000.0);
    expect($after_delete['total'])->toBe(25000.0);

    $transactions = $service->getTransactions($this->branch->id, [
        'status' => PaymentTransaction::STATUS_PAID,
        'from_date' => $from->toDateString(),
        'to_date' => $to->toDateString(),
        'per_page' => 50,
    ]);

    expect($transactions->pluck('reference')->all())->not->toContain('SUB-DEL-001');
    expect($transactions->pluck('reference')->all())->toContain('POS-001');
});
