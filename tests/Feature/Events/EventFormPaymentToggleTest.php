<?php

use App\Livewire\Events\Form as EventForm;
use App\Models\Branch;
use App\Models\Event;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'create events', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'switch branches', 'guard_name' => 'web']);

    $this->branch = Branch::factory()->create();

    $this->user = User::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->user->givePermissionTo('create events');
    $this->actingAs($this->user);
});

it('requires price when payment is enabled', function () {
    Livewire::test(EventForm::class)
        ->set('title', 'Weekend Bootcamp')
        ->set('type', 'paid')
        ->set('start_date', now()->addDays(2)->format('Y-m-d'))
        ->set('start_time', '10:00')
        ->set('payment_required', true)
        ->set('price', '')
        ->call('save')
        ->assertHasErrors(['price']);

    expect(Event::count())->toBe(0);
});

it('does not validate price when payment is disabled', function () {
    Livewire::test(EventForm::class)
        ->set('title', 'Community Yoga')
        ->set('type', 'public')
        ->set('start_date', now()->addDays(2)->format('Y-m-d'))
        ->set('start_time', '09:00')
        ->set('payment_required', false)
        ->set('price', '')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $event = Event::first();

    expect($event)->not->toBeNull();
    expect($event->payment_required)->toBeFalse();
    expect($event->price)->toBeNull();
});

it('clears stale price value when payment toggle is turned off', function () {
    Livewire::test(EventForm::class)
        ->set('payment_required', true)
        ->set('price', '45.00')
        ->set('payment_required', false)
        ->assertSet('price', '');
});
