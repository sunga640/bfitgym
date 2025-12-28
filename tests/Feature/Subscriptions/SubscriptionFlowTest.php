<?php

use App\Livewire\Subscriptions\Form as SubscriptionForm;
use App\Models\Branch;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\MembershipPackage;
use App\Models\User;
use App\Services\Memberships\SubscriptionService;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    foreach (['view subscriptions', 'create subscriptions', 'renew subscriptions'] as $permission_name) {
        Permission::firstOrCreate(['name' => $permission_name, 'guard_name' => 'web']);
    }

    $this->branch = Branch::factory()->create();

    $this->user = User::factory()->create([
        'branch_id' => $this->branch->id,
    ]);
    $this->user->givePermissionTo(['view subscriptions', 'create subscriptions', 'renew subscriptions']);

    $this->member = Member::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->package = MembershipPackage::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'price' => 100000,
        'duration_type' => 'months',
        'duration_value' => 1,
    ]);
});

it('creates a member subscription with payment', function () {
    $this->actingAs($this->user);

    Livewire::test(SubscriptionForm::class)
        ->set('member_id', $this->member->id)
        ->set('membership_package_id', $this->package->id)
        ->set('start_date', now()->format('Y-m-d'))
        ->set('auto_renew', true)
        ->set('amount', '100000')
        ->set('currency', 'TZS')
        ->set('payment_method', 'cash')
        ->set('paid_at', now()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $subscription = MemberSubscription::first();

    expect($subscription)->not->toBeNull();
    expect($subscription->status)->toBe('active');
    expect($subscription->paymentTransactions)->toHaveCount(1);
    expect($subscription->paymentTransactions->first()->amount)->toEqual(100000.0);
});

it('renews an existing subscription and links it to previous cycle', function () {
    $this->actingAs($this->user);

    /** @var SubscriptionService $service */
    $service = app(SubscriptionService::class);

    $existing = $service->startSubscription($this->member, $this->package, [
        'start_date' => now()->subMonth()->format('Y-m-d'),
        'auto_renew' => false,
        'notes' => null,
        'amount' => 100000,
        'currency' => 'TZS',
        'payment_method' => 'cash',
        'reference' => 'INV-001',
        'paid_at' => now()->subMonth()->format('Y-m-d\TH:i'),
    ]);

    Livewire::test(SubscriptionForm::class, [
        'subscription' => $existing,
        'isRenewal' => true,
    ])
        ->set('membership_package_id', $this->package->id)
        ->set('start_date', now()->format('Y-m-d'))
        ->set('auto_renew', true)
        ->set('amount', '100000')
        ->set('currency', 'TZS')
        ->set('payment_method', 'cash')
        ->set('paid_at', now()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $new_subscription = MemberSubscription::latest('id')->first();

    expect($new_subscription->renewed_from_id)->toBe($existing->id);
    expect($new_subscription->start_date->isAfter($existing->end_date))->toBeTrue();
    expect($new_subscription->paymentTransactions)->toHaveCount(1);
});

