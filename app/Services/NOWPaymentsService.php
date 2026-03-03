<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NOWPaymentsService
{
    private string $apiKey;
    private string $ipnSecret;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.nowpayments.api_key');
        $this->ipnSecret = config('services.nowpayments.ipn_secret');
        $this->baseUrl = config('services.nowpayments.url');
    }

    /**
     * Get available currencies
     */
    public function getAvailableCurrencies(): ?array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/currencies");
            
            if ($response->successful()) {
                return $response->json()['currencies'] ?? [];
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('NOWPayments: Exception getting currencies', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get estimated price in crypto
     */
    public function getEstimatedPrice(float $amount, string $currencyFrom = 'usd', string $currencyTo = 'btc'): ?array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/estimate", [
                'amount' => $amount,
                'currency_from' => $currencyFrom,
                'currency_to' => $currencyTo,
            ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('NOWPayments: Exception getting estimate', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Create payment
     */
    public function createPayment(array $data): ?array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/payment", $data);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('NOWPayments: Failed to create payment', ['response' => $response->json()]);
            return null;
        } catch (\Exception $e) {
            Log::error('NOWPayments: Exception creating payment', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(string $paymentId): ?array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/payment/{$paymentId}");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('NOWPayments: Exception getting payment status', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Verify IPN callback signature
     */
    public function verifyIPNSignature(string $payload, string $signature): bool
    {
        $expectedSignature = hash_hmac('sha512', $payload, $this->ipnSecret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Create invoice (payment link)
     */
    public function createInvoice(array $data): ?array
    {
        try {
            Log::info('NOWPayments: Creating invoice', ['data' => $data]);
            
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/invoice", $data);
            
            Log::info('NOWPayments: Invoice response', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('NOWPayments: Failed to create invoice', [
                'status' => $response->status(),
                'response' => $response->json(),
                'data_sent' => $data,
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('NOWPayments: Exception creating invoice', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data_sent' => $data,
            ]);
            return null;
        }
    }

    /**
     * Get minimum payment amount for a currency
     */
    public function getMinimumAmount(string $currency): ?float
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/min-amount", [
                'currency_from' => 'usd',
                'currency_to' => $currency,
            ]);
            
            if ($response->successful()) {
                return $response->json()['min_amount'] ?? null;
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('NOWPayments: Exception getting minimum amount', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
