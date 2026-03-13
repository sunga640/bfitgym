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
    foreach (['view subscriptions', 'create subscriptions', 'edit subscriptions', 'renew subscriptions', 'switch branches'] as $permission_name) {
        Permission::firstOrCreate(['name' => $permission_name, 'guard_name' => 'web']);
    }

    $this->branch = Branch::factory()->create();

    $this->user = User::factory()->create([
        'branch_id' => $this->branch->id,
    ]);
    $this->user->givePermissionTo(['view subscriptions', 'create subscriptions', 'edit subscriptions', 'renew subscriptions', 'switch branches']);

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

it('edits an existing subscription without creating a new cycle', function () {
    $this->actingAs($this->user);

    /** @var SubscriptionService $service */
    $service = app(SubscriptionService::class);

    $existing = $service->startSubscription($this->member, $this->package, [
        'start_date' => now()->subDays(10)->format('Y-m-d'),
        'auto_renew' => false,
        'notes' => 'Initial note',
        'amount' => 100000,
        'currency' => 'TZS',
        'payment_method' => 'cash',
        'reference' => 'INV-EDIT-001',
        'paid_at' => now()->subDays(10)->format('Y-m-d\TH:i'),
    ]);

    $new_start_date = now()->subDays(5)->format('Y-m-d');

    Livewire::test(SubscriptionForm::class, [
        'subscription' => $existing,
    ])
        ->set('start_date', $new_start_date)
        ->set('auto_renew', true)
        ->set('notes', 'Updated note')
        ->set('amount', '120000')
        ->set('currency', 'TZS')
        ->set('payment_method', 'card')
        ->set('reference', 'INV-EDIT-002')
        ->set('paid_at', now()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('subscriptions.show', $existing));

    $existing->refresh();

    expect(MemberSubscription::count())->toBe(1);
    expect($existing->start_date->toDateString())->toBe($new_start_date);
    expect($existing->auto_renew)->toBeTrue();
    expect($existing->notes)->toBe('Updated note');
    expect($existing->paymentTransactions)->toHaveCount(1);
    expect((float) $existing->paymentTransactions->first()->amount)->toBe(120000.0);
    expect($existing->paymentTransactions->first()->payment_method)->toBe('card');
    expect($existing->paymentTransactions->first()->reference)->toBe('INV-EDIT-002');
});
