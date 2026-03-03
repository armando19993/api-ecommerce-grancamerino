<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WompiService
{
    private string $publicKey;
    private string $privateKey;
    private string $integritySecret;
    private string $baseUrl;

    public function __construct()
    {
        $this->publicKey = config('services.wompi.public_key');
        $this->privateKey = config('services.wompi.private_key');
        $this->integritySecret = config('services.wompi.integrity_secret');
        $this->baseUrl = config('services.wompi.url');
    }

    /**
     * Generate integrity signature for Wompi transaction
     */
    public function generateIntegritySignature(string $reference, int $amountInCents, string $currency = 'COP'): string
    {
        $concatenated = $reference . $amountInCents . $currency . $this->integritySecret;
        $signature = hash('sha256', $concatenated);
        
        // Log detallado para debugging
        Log::info('Wompi: Signature Generation', [
            'reference' => $reference,
            'amount_in_cents' => $amountInCents,
            'currency' => $currency,
            'integrity_secret_length' => strlen($this->integritySecret),
            'integrity_secret_preview' => substr($this->integritySecret, 0, 20) . '...',
            'concatenated' => $reference . $amountInCents . $currency . substr($this->integritySecret, 0, 10) . '...',
            'signature' => $signature,
        ]);
        
        return $signature;
    }

    /**
     * Get acceptance token (required for transactions)
     */
    public function getAcceptanceToken(): ?array
    {
        try {
            $response = Http::get("{$this->baseUrl}/merchants/{$this->publicKey}");
            
            if ($response->successful()) {
                $data = $response->json();
                return $data['data']['presigned_acceptance'] ?? null;
            }
            
            Log::error('Wompi: Failed to get acceptance token', ['response' => $response->json()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Wompi: Exception getting acceptance token', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Create payment data for Widget/Checkout integration
     * 
     * @param string $reference Unique payment reference
     * @param float $amount Amount in COP
     * @param string $customerEmail Customer email
     * @param string|null $redirectUrl Optional redirect URL (can be localhost in sandbox)
     * @return array Payment data for Wompi Widget/Checkout
     */
    public function createPaymentData(string $reference, float $amount, string $customerEmail, ?string $redirectUrl = null): array
    {
        // Convertir a centavos de forma segura
        // Wompi requiere el monto en centavos (COP)
        // Ejemplo: 300000 COP = 30000000 centavos
        
        // Asegurar que el monto sea numérico y positivo
        $amount = (float)$amount;
        
        if ($amount <= 0) {
            Log::error('Wompi: Invalid amount', ['amount' => $amount]);
            throw new \InvalidArgumentException('Amount must be greater than 0');
        }
        
        // Convertir a centavos: multiplicar por 100 y convertir a entero
        // Usar bcmath para mayor precisión con números grandes
        if (function_exists('bcmul')) {
            $amountInCents = (int)bcmul((string)$amount, '100', 0);
        } else {
            $amountInCents = (int)($amount * 100);
        }
        
        // Log detallado para debugging
        Log::info('Wompi: Creating payment data', [
            'reference' => $reference,
            'amount_original' => $amount,
            'amount_in_cents_calculated' => $amountInCents,
            'calculation_method' => function_exists('bcmul') ? 'bcmath' : 'standard',
            'redirect_url' => $redirectUrl,
        ]);
        
        $signature = $this->generateIntegritySignature($reference, $amountInCents);
        
        $paymentData = [
            'public_key' => $this->publicKey,
            'currency' => 'COP',
            'amount_in_cents' => $amountInCents,
            'reference' => $reference,
            'signature' => $signature,
            'signature:integrity' => $signature,
            'customer_email' => $customerEmail,
        ];

        // redirect_url es OPCIONAL según documentación de Wompi
        // Solo incluirlo si se proporciona explícitamente
        // En sandbox, localhost es permitido
        if ($redirectUrl) {
            $paymentData['redirect_url'] = $redirectUrl;
        }
        
        return $paymentData;
    }

    /**
     * Build complete checkout URL
     */
    public function buildCheckoutUrl(array $paymentData, ?string $customerName = null, ?string $customerPhone = null): string
    {
        // Construir URL manualmente para manejar correctamente los dos puntos en los nombres de parámetros
        $baseUrl = 'https://checkout.wompi.co/p/';
        
        $params = [];
        $params[] = 'public-key=' . urlencode($paymentData['public_key']);
        $params[] = 'currency=' . urlencode($paymentData['currency']);
        $params[] = 'amount-in-cents=' . urlencode($paymentData['amount_in_cents']);
        $params[] = 'reference=' . urlencode($paymentData['reference']);
        $params[] = 'signature:integrity=' . urlencode($paymentData['signature']);
        
        // Solo incluir redirect-url si existe y no es localhost
        if (!empty($paymentData['redirect_url'])) {
            $params[] = 'redirect-url=' . urlencode($paymentData['redirect_url']);
        }
        
        if (!empty($paymentData['customer_email'])) {
            $params[] = 'customer-data:email=' . urlencode($paymentData['customer_email']);
        }

        // NO incluir customer-data:full-name ni phone-number
        // Estos parámetros opcionales pueden causar error 403

        return $baseUrl . '?' . implode('&', $params);
    }

    /**
     * Create transaction via API (for direct integration)
     */
    public function createTransaction(array $data): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->privateKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/transactions", $data);
            
            if ($response->successful()) {
                return $response->json()['data'] ?? null;
            }
            
            Log::error('Wompi: Failed to create transaction', ['response' => $response->json()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Wompi: Exception creating transaction', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get transaction status
     */
    public function getTransaction(string $transactionId): ?array
    {
        try {
            $response = Http::get("{$this->baseUrl}/transactions/{$transactionId}");
            
            if ($response->successful()) {
                return $response->json()['data'] ?? null;
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Wompi: Exception getting transaction', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, ?string $signature = null): bool
    {
        // El webhook de Wompi envía la firma en el header X-Wompi-Signature
        // pero también puede venir en el body como signature.checksum
        if (!$signature) {
            return false;
        }
        
        // Para webhooks, usamos el events_secret
        $expectedSignature = hash_hmac('sha256', $payload, config('services.wompi.events_secret'));
        
        // Log para debugging
        Log::info('Wompi Webhook Signature Verification', [
            'provided_signature' => $signature,
            'expected_signature' => $expectedSignature,
            'payload_length' => strlen($payload),
            'events_secret_configured' => !empty(config('services.wompi.events_secret')),
            'signature_match' => hash_equals($expectedSignature, $signature),
        ]);
        
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify webhook signature using X-Event-Checksum header
     * 
     * Según la documentación oficial de Wompi:
     * https://docs.wompi.co/en/docs/colombia/eventos/
     * 
     * El checksum se calcula concatenando:
     * 1. Los valores de los campos en signature.properties (ej: transaction.id, transaction.status, transaction.amount_in_cents)
     * 2. El campo timestamp (UNIX timestamp del evento)
     * 3. El events_secret
     * 
     * Luego se aplica SHA256 (NO HMAC) a la concatenación
     */
    public function verifyEventChecksum(string $payload, ?string $checksum = null): bool
    {
        if (!$checksum) {
            Log::warning('Wompi: No checksum provided');
            return false;
        }
        
        $eventsSecret = config('services.wompi.events_secret');
        if (!$eventsSecret) {
            Log::error('Wompi: Events secret not configured');
            return false;
        }
        
        // Parsear el payload
        $decodedPayload = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Wompi: Invalid JSON in checksum verification');
            return false;
        }
        
        // Obtener el timestamp del evento
        $timestamp = $decodedPayload['timestamp'] ?? '';
        
        // Obtener las propiedades especificadas en signature.properties
        $properties = $decodedPayload['signature']['properties'] ?? [];
        
        if (empty($properties) || empty($timestamp)) {
            Log::error('Wompi: Missing signature properties or timestamp', [
                'properties' => $properties,
                'timestamp' => $timestamp,
            ]);
            return false;
        }
        
        // Paso 1: Concatenar los valores de las propiedades en el orden especificado
        $concatenated = '';
        foreach ($properties as $property) {
            $value = $this->getNestedValue($decodedPayload['data'], $property);
            if ($value === null) {
                Log::warning('Wompi: Property not found in payload', ['property' => $property]);
                return false;
            }
            $concatenated .= $value;
        }
        
        // Paso 2: Concatenar el timestamp
        $concatenated .= $timestamp;
        
        // Paso 3: Concatenar el events_secret
        $concatenated .= $eventsSecret;
        
        // Paso 4: Calcular SHA256 (NO HMAC) - exactamente como en la documentación
        $expectedChecksum = hash('sha256', $concatenated);
        
        $match = hash_equals($expectedChecksum, $checksum);
        
        // Log detallado para debugging
        Log::info('Wompi Event Checksum Verification', [
            'provided_checksum' => $checksum,
            'expected_checksum' => $expectedChecksum,
            'properties' => $properties,
            'timestamp' => $timestamp,
            'concatenated_preview' => substr($concatenated, 0, 100) . '...',
            'concatenated_length' => strlen($concatenated),
            'events_secret_length' => strlen($eventsSecret),
            'match' => $match,
            'calculation_method' => 'hash("sha256", concatenated_string)',
        ]);
        
        return $match;
    }

    /**
     * Get nested value from array using dot notation
     */
    private function getNestedValue(array $array, string $key)
    {
        $keys = explode('.', $key);
        $value = $array;
        
        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return null;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
}
