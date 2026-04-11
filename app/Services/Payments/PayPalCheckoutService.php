<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayPalCheckoutService
{
    public function isConfigured(): bool
    {
        return filled(config('services.paypal.client_id'))
            && filled(config('services.paypal.secret'))
            && filled(config('services.paypal.base_url'));
    }

    /**
     * Create a PayPal checkout order and return the order id + approval URL.
     *
     * @return array{order_id: string, approval_url: string, status: string|null}
     */
    public function createOrder(
        float $amount,
        string $currency,
        string $return_url,
        string $cancel_url,
        string $description
    ): array {
        $response = $this->client()
            ->withToken($this->accessToken())
            ->post('/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'description' => str($description)->limit(120)->value(),
                        'amount' => [
                            'currency_code' => strtoupper($currency),
                            'value' => number_format($amount, 2, '.', ''),
                        ],
                    ],
                ],
                'application_context' => [
                    'return_url' => $return_url,
                    'cancel_url' => $cancel_url,
                    'user_action' => 'PAY_NOW',
                    'shipping_preference' => 'NO_SHIPPING',
                ],
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('PayPal order creation failed: ' . $response->body());
        }

        $payload = $response->json();
        $order_id = (string) ($payload['id'] ?? '');
        $status = $payload['status'] ?? null;

        $approval_url = collect($payload['links'] ?? [])
            ->firstWhere('rel', 'approve')['href'] ?? null;

        if ($order_id === '' || blank($approval_url)) {
            throw new RuntimeException('PayPal order response did not include approval details.');
        }

        return [
            'order_id' => $order_id,
            'approval_url' => (string) $approval_url,
            'status' => is_string($status) ? $status : null,
        ];
    }

    /**
     * Capture an approved PayPal order.
     *
     * @return array{status: string|null, capture_id: string|null, payload: array<string, mixed>}
     */
    public function captureOrder(string $order_id): array
    {
        $response = $this->client()
            ->withToken($this->accessToken())
            ->post('/v2/checkout/orders/' . $order_id . '/capture');

        if (!$response->successful()) {
            throw new RuntimeException('PayPal order capture failed: ' . $response->body());
        }

        $payload = $response->json();

        return [
            'status' => is_string($payload['status'] ?? null) ? $payload['status'] : null,
            'capture_id' => data_get($payload, 'purchase_units.0.payments.captures.0.id'),
            'payload' => is_array($payload) ? $payload : [],
        ];
    }

    protected function accessToken(): string
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('PayPal credentials are not configured.');
        }

        $response = $this->client()
            ->asForm()
            ->withBasicAuth(
                (string) config('services.paypal.client_id'),
                (string) config('services.paypal.secret')
            )
            ->post('/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('PayPal access token request failed: ' . $response->body());
        }

        $token = (string) ($response->json('access_token') ?? '');

        if ($token === '') {
            throw new RuntimeException('PayPal access token is missing in response.');
        }

        return $token;
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.paypal.base_url'), '/'))
            ->acceptJson()
            ->timeout((int) config('services.paypal.timeout', 20));
    }
}
