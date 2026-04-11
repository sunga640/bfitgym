<?php

use App\Models\Branch;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->branch = Branch::factory()->create();

    config()->set('services.paypal.client_id', 'paypal-client-id');
    config()->set('services.paypal.secret', 'paypal-secret');
    config()->set('services.paypal.base_url', 'https://api-m.sandbox.paypal.com');
    config()->set('services.paypal.currency', 'USD');
});

it('registers a visitor for a free public event without creating payment', function () {
    $event = Event::create([
        'branch_id' => $this->branch->id,
        'title' => 'Free Community Fitness Day',
        'description' => 'Open workout and nutrition tips.',
        'type' => 'public',
        'location' => 'Main Hall',
        'start_datetime' => now()->addDays(3)->setTime(10, 0),
        'end_datetime' => now()->addDays(3)->setTime(12, 0),
        'payment_required' => false,
        'price' => null,
        'capacity' => 50,
        'allow_non_members' => true,
        'status' => 'scheduled',
    ]);

    $response = $this->post(route('public.events.register', $event), [
        'full_name' => 'Jane Visitor',
        'phone' => '+255700123456',
        'email' => 'jane@example.com',
        'will_attend' => 1,
    ]);

    $registration = EventRegistration::first();

    $response->assertRedirect(route('public.events.success', $registration));

    expect($registration)->not->toBeNull();
    expect($registration->status)->toBe('confirmed');
    expect($registration->will_attend)->toBeTrue();
    expect($registration->payment_transaction_id)->toBeNull();
});

it('initializes paypal checkout for paid event registrations', function () {
    Http::fake([
        'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
            'access_token' => 'access-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], 200),
        'https://api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
            'id' => 'ORDER-123',
            'status' => 'CREATED',
            'links' => [
                ['rel' => 'approve', 'href' => 'https://www.paypal.com/checkoutnow?token=ORDER-123'],
            ],
        ], 201),
    ]);

    $event = Event::create([
        'branch_id' => $this->branch->id,
        'title' => 'Paid Bootcamp',
        'description' => 'Weekend bootcamp.',
        'type' => 'paid',
        'location' => 'Studio B',
        'start_datetime' => now()->addDays(2)->setTime(8, 30),
        'end_datetime' => now()->addDays(2)->setTime(11, 30),
        'payment_required' => true,
        'price' => 25.00,
        'capacity' => 20,
        'allow_non_members' => true,
        'status' => 'scheduled',
    ]);

    $response = $this->post(route('public.events.register', $event), [
        'full_name' => 'Peter Paid Visitor',
        'phone' => '+255700987654',
        'email' => 'peter@example.com',
        'will_attend' => 1,
    ]);

    $response->assertRedirect('https://www.paypal.com/checkoutnow?token=ORDER-123');

    $registration = EventRegistration::first();
    $payment = PaymentTransaction::first();

    expect($registration)->not->toBeNull();
    expect($payment)->not->toBeNull();
    expect($registration->status)->toBe('pending');
    expect($registration->payment_transaction_id)->toBe($payment->id);
    expect($payment->status)->toBe(PaymentTransaction::STATUS_PENDING);
    expect($payment->revenue_type)->toBe(PaymentTransaction::REVENUE_TYPE_EVENT);
    expect($payment->reference)->toBe('ORDER-123');
});

it('captures approved paypal payment and confirms the registration', function () {
    Http::fake([
        'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
            'access_token' => 'access-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], 200),
        'https://api-m.sandbox.paypal.com/v2/checkout/orders/ORDER-OK/capture' => Http::response([
            'status' => 'COMPLETED',
            'purchase_units' => [
                [
                    'payments' => [
                        'captures' => [
                            ['id' => 'CAPTURE-789'],
                        ],
                    ],
                ],
            ],
        ], 201),
    ]);

    $event = Event::create([
        'branch_id' => $this->branch->id,
        'title' => 'Paid Nutrition Workshop',
        'description' => 'Workshop with coach.',
        'type' => 'paid',
        'location' => 'Conference Room',
        'start_datetime' => now()->addDays(4)->setTime(14, 0),
        'end_datetime' => now()->addDays(4)->setTime(16, 0),
        'payment_required' => true,
        'price' => 15.00,
        'capacity' => 40,
        'allow_non_members' => true,
        'status' => 'scheduled',
    ]);

    $registration = EventRegistration::create([
        'event_id' => $event->id,
        'branch_id' => $event->branch_id,
        'member_id' => null,
        'full_name' => 'Capture Test Visitor',
        'phone' => '+255700555123',
        'email' => 'capture@example.com',
        'will_attend' => true,
        'status' => 'pending',
        'registration_datetime' => now(),
    ]);

    $payment = PaymentTransaction::create([
        'branch_id' => $event->branch_id,
        'payer_type' => PaymentTransaction::PAYER_OTHER,
        'payer_member_id' => null,
        'payer_insurer_id' => null,
        'amount' => 15.00,
        'currency' => 'USD',
        'payment_method' => 'other',
        'reference' => 'ORDER-OK',
        'paid_at' => now(),
        'status' => PaymentTransaction::STATUS_PENDING,
        'revenue_type' => PaymentTransaction::REVENUE_TYPE_EVENT,
        'payable_type' => EventRegistration::class,
        'payable_id' => $registration->id,
        'notes' => 'Pending PayPal payment',
    ]);

    $registration->update([
        'payment_transaction_id' => $payment->id,
    ]);

    $response = $this->get(route('public.events.payment.approved', [
        'registration' => $registration,
        'token' => 'ORDER-OK',
    ]));

    $response->assertRedirect(route('public.events.success', $registration));

    expect($payment->fresh()->status)->toBe(PaymentTransaction::STATUS_PAID);
    expect($registration->fresh()->status)->toBe('confirmed');
});
