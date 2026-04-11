<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\PaymentTransaction;
use App\Services\Payments\PayPalCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PublicEventRegistrationController extends Controller
{
    public function index(): View
    {
        $events = Event::query()
            ->where('allow_non_members', true)
            ->where('status', 'scheduled')
            ->where('start_datetime', '>=', now()->startOfDay())
            ->orderBy('start_datetime')
            ->paginate(12);

        return view('public.events.index', [
            'events' => $events,
            'payment_currency' => $this->paymentCurrency(),
        ]);
    }

    public function show(Event $event): View
    {
        abort_unless($this->isPubliclyRegisterable($event), 404);

        $attending_count = $event->registrations()
            ->where('will_attend', true)
            ->whereIn('status', ['pending', 'confirmed', 'attended'])
            ->count();

        $is_full = $event->capacity !== null && $attending_count >= (int) $event->capacity;

        return view('public.events.show', [
            'event' => $event,
            'attending_count' => $attending_count,
            'is_full' => $is_full,
            'payment_currency' => $this->paymentCurrency(),
        ]);
    }

    public function register(
        Request $request,
        Event $event,
        PayPalCheckoutService $paypal_checkout
    ): RedirectResponse {
        abort_unless($this->isPubliclyRegisterable($event), 404);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50', 'required_without:email'],
            'email' => ['nullable', 'email', 'max:100', 'required_without:phone'],
            'will_attend' => ['required', 'boolean'],
        ], [
            'phone.required_without' => __('Please provide a phone number or email address.'),
            'email.required_without' => __('Please provide an email or phone number.'),
        ]);

        $will_attend = (bool) $validated['will_attend'];
        $email = blank($validated['email']) ? null : trim(strtolower((string) $validated['email']));
        $phone = blank($validated['phone']) ? null : trim((string) $validated['phone']);

        if ($will_attend && $this->isAtCapacity($event)) {
            return back()
                ->withErrors(['registration' => __('This event is already full.')])
                ->withInput();
        }

        if ($will_attend && $this->hasDuplicateRegistration($event, $email, $phone)) {
            return back()
                ->withErrors(['registration' => __('You already have an active registration for this event.')])
                ->withInput();
        }

        if ($event->payment_required && $will_attend && !$paypal_checkout->isConfigured()) {
            return back()
                ->withErrors(['payment' => __('PayPal checkout is not configured. Please contact support.')])
                ->withInput();
        }

        $registration = DB::transaction(function () use ($event, $validated, $email, $phone, $will_attend) {
            $registration = EventRegistration::create([
                'event_id' => $event->id,
                'branch_id' => $event->branch_id,
                'member_id' => null,
                'full_name' => trim((string) $validated['full_name']),
                'phone' => $phone,
                'email' => $email,
                'will_attend' => $will_attend,
                'status' => $event->payment_required && $will_attend ? 'pending' : ($will_attend ? 'confirmed' : 'cancelled'),
                'registration_datetime' => now(),
            ]);

            if ($event->payment_required && $will_attend) {
                $payment_transaction = PaymentTransaction::create([
                    'branch_id' => $event->branch_id,
                    'payer_type' => PaymentTransaction::PAYER_OTHER,
                    'payer_member_id' => null,
                    'payer_insurer_id' => null,
                    'amount' => (float) $event->price,
                    'currency' => $this->paymentCurrency(),
                    'payment_method' => 'other',
                    'reference' => null,
                    'paid_at' => now(),
                    'status' => PaymentTransaction::STATUS_PENDING,
                    'revenue_type' => PaymentTransaction::REVENUE_TYPE_EVENT,
                    'payable_type' => EventRegistration::class,
                    'payable_id' => $registration->id,
                    'notes' => __('PayPal payment initialized for public event registration.'),
                ]);

                $registration->update([
                    'payment_transaction_id' => $payment_transaction->id,
                ]);
            }

            return $registration;
        });

        if (!$event->payment_required || !$will_attend) {
            return redirect()
                ->route('public.events.success', $registration)
                ->with('success', __('Registration submitted successfully.'));
        }

        $registration->loadMissing('paymentTransaction');
        $payment_transaction = $registration->paymentTransaction;

        try {
            $order = $paypal_checkout->createOrder(
                amount: (float) $event->price,
                currency: $this->paymentCurrency(),
                return_url: route('public.events.payment.approved', $registration),
                cancel_url: route('public.events.payment.cancelled', $registration),
                description: __('Registration for :event', ['event' => $event->title]),
            );

            $payment_transaction?->update([
                'reference' => $order['order_id'],
                'notes' => $this->appendNote($payment_transaction->notes, 'PayPal order id: ' . $order['order_id']),
            ]);

            return redirect()->away($order['approval_url']);
        } catch (\Throwable $exception) {
            if ($payment_transaction) {
                $payment_transaction->update([
                    'status' => PaymentTransaction::STATUS_FAILED,
                    'notes' => $this->appendNote($payment_transaction->notes, 'PayPal initialization failed: ' . $exception->getMessage()),
                ]);
            }

            $registration->update(['status' => 'cancelled']);

            return back()
                ->withErrors(['payment' => __('Unable to initialize PayPal checkout. Please try again.')])
                ->withInput();
        }
    }

    public function paymentApproved(
        Request $request,
        EventRegistration $registration,
        PayPalCheckoutService $paypal_checkout
    ): RedirectResponse {
        $registration->loadMissing(['event', 'paymentTransaction']);
        $event = $registration->event;

        abort_unless($event && $event->allow_non_members, 404);

        $payment_transaction = $registration->paymentTransaction;

        if (!$payment_transaction) {
            return redirect()
                ->route('public.events.success', $registration)
                ->with('success', __('Registration submitted successfully.'));
        }

        if ($payment_transaction->status === PaymentTransaction::STATUS_PAID) {
            return redirect()->route('public.events.success', $registration);
        }

        $order_id = (string) $request->query('token', '');

        if ($order_id === '' || ($payment_transaction->reference && $payment_transaction->reference !== $order_id)) {
            return redirect()
                ->route('public.events.show', $event)
                ->withErrors(['payment' => __('Unable to verify this payment confirmation.')]);
        }

        try {
            $capture = $paypal_checkout->captureOrder($order_id);
            $status = strtoupper((string) ($capture['status'] ?? ''));

            if ($status !== 'COMPLETED') {
                $payment_transaction->update([
                    'status' => PaymentTransaction::STATUS_FAILED,
                    'notes' => $this->appendNote(
                        $payment_transaction->notes,
                        'PayPal capture did not complete. Status: ' . ($capture['status'] ?? 'unknown')
                    ),
                ]);

                $registration->update(['status' => 'cancelled']);

                return redirect()
                    ->route('public.events.show', $event)
                    ->withErrors(['payment' => __('Payment was not completed. Please try again.')]);
            }

            $payment_transaction->update([
                'status' => PaymentTransaction::STATUS_PAID,
                'payment_method' => 'other',
                'paid_at' => now(),
                'reference' => $order_id,
                'notes' => $this->appendNote(
                    $payment_transaction->notes,
                    'PayPal capture id: ' . ($capture['capture_id'] ?? 'N/A')
                ),
            ]);

            $registration->update(['status' => 'confirmed']);

            return redirect()
                ->route('public.events.success', $registration)
                ->with('success', __('Payment received and registration confirmed.'));
        } catch (\Throwable $exception) {
            $payment_transaction->update([
                'status' => PaymentTransaction::STATUS_FAILED,
                'notes' => $this->appendNote($payment_transaction->notes, 'PayPal capture failed: ' . $exception->getMessage()),
            ]);

            $registration->update(['status' => 'cancelled']);

            return redirect()
                ->route('public.events.show', $event)
                ->withErrors(['payment' => __('Payment confirmation failed. Please try again.')]);
        }
    }

    public function paymentCancelled(EventRegistration $registration): RedirectResponse
    {
        $registration->loadMissing(['event', 'paymentTransaction']);

        if (!$registration->event) {
            return redirect()->route('public.events.index');
        }

        if ($registration->paymentTransaction && $registration->paymentTransaction->status !== PaymentTransaction::STATUS_PAID) {
            $registration->paymentTransaction->update([
                'status' => PaymentTransaction::STATUS_FAILED,
                'notes' => $this->appendNote($registration->paymentTransaction->notes, 'PayPal payment was cancelled by visitor.'),
            ]);
        }

        if ($registration->status === 'pending') {
            $registration->update(['status' => 'cancelled']);
        }

        return redirect()
            ->route('public.events.show', $registration->event)
            ->withErrors(['payment' => __('Payment was cancelled. You can register again when ready.')]);
    }

    public function success(EventRegistration $registration): View
    {
        $registration->loadMissing(['event', 'paymentTransaction']);

        abort_unless($registration->event && $registration->event->allow_non_members, 404);

        return view('public.events.success', [
            'registration' => $registration,
            'event' => $registration->event,
            'payment_currency' => $this->paymentCurrency(),
        ]);
    }

    protected function isPubliclyRegisterable(Event $event): bool
    {
        return $event->allow_non_members
            && $event->status === 'scheduled'
            && $event->start_datetime
            && $event->start_datetime->isFuture();
    }

    protected function isAtCapacity(Event $event): bool
    {
        if ($event->capacity === null) {
            return false;
        }

        $attending_count = $event->registrations()
            ->where('will_attend', true)
            ->whereIn('status', ['pending', 'confirmed', 'attended'])
            ->count();

        return $attending_count >= (int) $event->capacity;
    }

    protected function hasDuplicateRegistration(Event $event, ?string $email, ?string $phone): bool
    {
        if (blank($email) && blank($phone)) {
            return false;
        }

        return $event->registrations()
            ->where('will_attend', true)
            ->whereIn('status', ['pending', 'confirmed', 'attended'])
            ->where(function ($query) use ($email, $phone) {
                if ($email && $phone) {
                    $query
                        ->where('email', $email)
                        ->orWhere('phone', $phone);
                    return;
                }

                if ($email) {
                    $query->where('email', $email);
                    return;
                }

                if ($phone) {
                    $query->where('phone', $phone);
                }
            })
            ->exists();
    }

    protected function paymentCurrency(): string
    {
        return strtoupper((string) config('services.paypal.currency', config('app.currency', 'USD')));
    }

    protected function appendNote(?string $existing, string $note): string
    {
        if (blank($existing)) {
            return $note;
        }

        return $existing . PHP_EOL . $note;
    }
}
