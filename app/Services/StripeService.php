<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StripeService
{
    protected string $secretKey;
    protected string $webhookSecret;
    protected string $apiUrl;

    public function __construct()
    {
        $this->secretKey = config('services.stripe.secret_key');
        $this->webhookSecret = config('services.stripe.webhook_secret');
        $this->apiUrl = 'https://api.stripe.com/v1';
    }

    /**
     * Create a Stripe Checkout Session
     */
    public function createCheckoutSession(array $data)
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->asForm()
                ->post("{$this->apiUrl}/checkout/sessions", [
                    'mode' => 'payment',
                    'success_url' => $data['success_url'],
                    'cancel_url' => $data['cancel_url'],
                    'client_reference_id' => $data['order_id'],
                    'customer_email' => $data['customer_email'],
                    'line_items' => $data['line_items'],
                    'payment_intent_data' => [
                        'metadata' => [
                            'order_id' => $data['order_id'],
                            'order_number' => $data['order_number'] ?? null,
                        ]
                    ],
                    'metadata' => [
                        'order_id' => $data['order_id'],
                        'order_number' => $data['order_number'] ?? null,
                    ]
                ]);

            if ($response->successful()) {
                Log::info('Stripe: Checkout session created', [
                    'order_id' => $data['order_id'],
                    'session_id' => $response->json()['id']
                ]);
                return $response->json();
            }

            Log::error('Stripe: Failed to create checkout session', [
                'order_id' => $data['order_id'],
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('Stripe: Exception creating checkout session', [
                'order_id' => $data['order_id'] ?? null,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Retrieve a Checkout Session
     */
    public function getCheckoutSession(string $sessionId)
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->get("{$this->apiUrl}/checkout/sessions/{$sessionId}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Stripe: Failed to retrieve checkout session', [
                'session_id' => $sessionId,
                'status' => $response->status()
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('Stripe: Exception retrieving checkout session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        // Si no hay webhook secret configurado, permitir en desarrollo (NO USAR EN PRODUCCIÓN)
        if (empty($this->webhookSecret)) {
            Log::warning('Stripe: No webhook secret configured - signature verification skipped');
            return config('app.env') === 'local' || config('app.env') === 'development';
        }

        try {
            // Stripe envía la firma en formato: t=timestamp,v1=signature
            $signatureParts = [];
            foreach (explode(',', $signature) as $part) {
                $keyValue = explode('=', $part, 2);
                if (count($keyValue) === 2) {
                    $signatureParts[$keyValue[0]] = $keyValue[1];
                }
            }

            if (!isset($signatureParts['t']) || !isset($signatureParts['v1'])) {
                Log::warning('Stripe: Missing timestamp or v1 signature', [
                    'signature_header' => $signature,
                    'parsed_parts' => $signatureParts
                ]);
                return false;
            }

            $timestamp = $signatureParts['t'];
            $expectedSignature = $signatureParts['v1'];

            // Construir el string firmado: timestamp.payload
            $signedPayload = $timestamp . '.' . $payload;

            // Calcular la firma esperada
            $computedSignature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);

            // Log para debugging
            Log::info('Stripe: Signature verification details', [
                'timestamp' => $timestamp,
                'expected_signature' => $expectedSignature,
                'computed_signature' => $computedSignature,
                'signed_payload_length' => strlen($signedPayload),
                'webhook_secret_length' => strlen($this->webhookSecret),
                'signatures_match' => hash_equals($computedSignature, $expectedSignature)
            ]);

            // Comparar las firmas
            if (!hash_equals($computedSignature, $expectedSignature)) {
                Log::warning('Stripe: Signature mismatch', [
                    'expected' => $expectedSignature,
                    'computed' => $computedSignature
                ]);
                return false;
            }

            // Verificar que el timestamp no sea muy antiguo (5 minutos)
            $currentTime = time();
            $timeDifference = abs($currentTime - (int)$timestamp);
            
            if ($timeDifference > 300) {
                Log::warning('Stripe: Timestamp too old', [
                    'timestamp' => $timestamp,
                    'current_time' => $currentTime,
                    'difference_seconds' => $timeDifference
                ]);
                return false;
            }

            Log::info('Stripe: Signature verification successful');
            return true;

        } catch (\Exception $e) {
            Log::error('Stripe: Error verifying webhook signature', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Format line items for Stripe
     */
    public function formatLineItems(array $items, string $currency = 'usd'): array
    {
        $lineItems = [];

        foreach ($items as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => $item['name'],
                        'description' => $item['description'] ?? null,
                    ],
                    'unit_amount' => (int)($item['unit_price'] * 100), // Stripe usa centavos
                ],
                'quantity' => $item['quantity'],
            ];
        }

        return $lineItems;
    }

    /**
     * Convert amount to cents for Stripe
     */
    public function toCents(float $amount): int
    {
        return (int)($amount * 100);
    }

    /**
     * Convert cents from Stripe to amount
     */
    public function fromCents(int $cents): float
    {
        return $cents / 100;
    }
}
