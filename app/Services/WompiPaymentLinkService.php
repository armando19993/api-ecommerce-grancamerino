<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio alternativo usando Payment Links de Wompi
 * Más robusto que el Widget/Checkout directo
 */
class WompiPaymentLinkService
{
    private string $privateKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->privateKey = config('services.wompi.private_key');
        $this->baseUrl = config('services.wompi.url');
    }

    /**
     * Crear un Payment Link
     * Esta es una alternativa más robusta al checkout directo
     */
    public function createPaymentLink(array $data): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->privateKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/payment_links", [
                'name' => $data['name'] ?? 'Payment',
                'description' => $data['description'] ?? 'Order payment',
                'single_use' => true,
                'collect_shipping' => false,
                'currency' => 'COP',
                'amount_in_cents' => $data['amount_in_cents'],
                'redirect_url' => $data['redirect_url'] ?? null,
                'expires_at' => $data['expires_at'] ?? now()->addHours(24)->toIso8601String(),
            ]);
            
            if ($response->successful()) {
                $result = $response->json();
                Log::info('Wompi: Payment link created', ['link_id' => $result['data']['id'] ?? null]);
                return $result['data'] ?? null;
            }
            
            Log::error('Wompi: Failed to create payment link', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            return null;
            
        } catch (\Exception $e) {
            Log::error('Wompi: Exception creating payment link', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Obtener información de un Payment Link
     */
    public function getPaymentLink(string $linkId): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->privateKey,
            ])->get("{$this->baseUrl}/payment_links/{$linkId}");
            
            if ($response->successful()) {
                return $response->json()['data'] ?? null;
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Wompi: Exception getting payment link', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
