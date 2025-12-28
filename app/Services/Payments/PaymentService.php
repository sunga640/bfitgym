<?php

namespace App\Services\Payments;

use App\Models\ClassBooking;
use App\Models\EventRegistration;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\PaymentTransaction;
use App\Models\PosSale;
use Illuminate\Support\Carbon;

class PaymentService
{
    /**
     * Record a membership payment against a subscription.
     */
    public function recordMembershipPayment(Member $member, MemberSubscription $subscription, array $payload): PaymentTransaction
    {
        $paid_at = Carbon::parse($payload['paid_at']);

        return PaymentTransaction::create([
            'branch_id' => $member->branch_id,
            'payer_type' => PaymentTransaction::PAYER_MEMBER,
            'payer_member_id' => $member->id,
            'amount' => $payload['amount'],
            'currency' => strtoupper($payload['currency']),
            'payment_method' => $payload['payment_method'],
            'reference' => $payload['reference'] ?? null,
            'paid_at' => $paid_at,
            'status' => $payload['status'] ?? PaymentTransaction::STATUS_PAID,
            'revenue_type' => PaymentTransaction::REVENUE_TYPE_MEMBERSHIP,
            'payable_type' => MemberSubscription::class,
            'payable_id' => $subscription->id,
            'notes' => $payload['notes'] ?? null,
        ]);
    }

    /**
     * Record a POS sale payment.
     */
    public function recordPosSalePayment(PosSale $pos_sale, array $payload): PaymentTransaction
    {
        $paid_at = Carbon::parse($payload['paid_at'] ?? now());

        return PaymentTransaction::create([
            'branch_id' => $pos_sale->branch_id,
            'payer_type' => $pos_sale->member_id ? PaymentTransaction::PAYER_MEMBER : PaymentTransaction::PAYER_OTHER,
            'payer_member_id' => $pos_sale->member_id,
            'amount' => $pos_sale->total_amount,
            'currency' => strtoupper($payload['currency'] ?? app_currency()),
            'payment_method' => $payload['payment_method'],
            'reference' => $payload['reference'] ?? $pos_sale->sale_number,
            'paid_at' => $paid_at,
            'status' => PaymentTransaction::STATUS_PAID,
            'revenue_type' => PaymentTransaction::REVENUE_TYPE_POS,
            'payable_type' => PosSale::class,
            'payable_id' => $pos_sale->id,
            'notes' => $payload['notes'] ?? null,
        ]);
    }

    /**
     * Record a class booking payment.
     */
    public function recordClassBookingPayment(ClassBooking $booking, array $payload): PaymentTransaction
    {
        $paid_at = Carbon::parse($payload['paid_at'] ?? now());

        return PaymentTransaction::create([
            'branch_id' => $booking->branch_id,
            'payer_type' => PaymentTransaction::PAYER_MEMBER,
            'payer_member_id' => $booking->member_id,
            'amount' => $payload['amount'],
            'currency' => strtoupper($payload['currency'] ?? app_currency()),
            'payment_method' => $payload['payment_method'],
            'reference' => $payload['reference'] ?? null,
            'paid_at' => $paid_at,
            'status' => PaymentTransaction::STATUS_PAID,
            'revenue_type' => PaymentTransaction::REVENUE_TYPE_CLASS_BOOKING,
            'payable_type' => ClassBooking::class,
            'payable_id' => $booking->id,
            'notes' => $payload['notes'] ?? null,
        ]);
    }

    /**
     * Record an event registration payment.
     */
    public function recordEventPayment(EventRegistration $registration, array $payload): PaymentTransaction
    {
        $paid_at = Carbon::parse($payload['paid_at'] ?? now());

        return PaymentTransaction::create([
            'branch_id' => $registration->branch_id,
            'payer_type' => PaymentTransaction::PAYER_MEMBER,
            'payer_member_id' => $registration->member_id,
            'amount' => $payload['amount'],
            'currency' => strtoupper($payload['currency'] ?? app_currency()),
            'payment_method' => $payload['payment_method'],
            'reference' => $payload['reference'] ?? null,
            'paid_at' => $paid_at,
            'status' => PaymentTransaction::STATUS_PAID,
            'revenue_type' => PaymentTransaction::REVENUE_TYPE_EVENT,
            'payable_type' => EventRegistration::class,
            'payable_id' => $registration->id,
            'notes' => $payload['notes'] ?? null,
        ]);
    }

    /**
     * Refund a payment transaction.
     */
    public function refundPayment(PaymentTransaction $transaction, ?string $reason = null): PaymentTransaction
    {
        $transaction->update([
            'status' => PaymentTransaction::STATUS_FAILED,
            'notes' => $transaction->notes . "\n[REFUNDED] " . ($reason ?? 'Payment refunded'),
        ]);

        return $transaction;
    }

    /**
     * Get available payment methods.
     */
    public static function getPaymentMethods(): array
    {
        return [
            'cash' => 'Cash',
            'card' => 'Card',
            'mobile_money' => 'Mobile Money',
            'bank_transfer' => 'Bank Transfer',
            'mpesa' => 'M-Pesa',
            'tigopesa' => 'Tigo Pesa',
            'airtel_money' => 'Airtel Money',
        ];
    }
}


