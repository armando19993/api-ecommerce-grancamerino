<?php

// Webhook endpoint para testing que salta la verificación de firma
// Solo para desarrollo y pruebas

Route::post('/webhooks/wompi-dev', function (Request $request) {
    $payload = $request->getContent();
    
    Log::info('Wompi Dev Webhook - Signature Bypassed', [
        'payload_raw' => $payload,
        'environment' => app()->environment(),
    ]);
    
    // Verificar si el payload es JSON válido
    $decodedPayload = json_decode($payload, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        Log::error('Wompi Dev: Invalid JSON payload', [
            'error' => json_last_error_msg(),
            'payload' => $payload
        ]);
        return response()->json(['error' => 'Invalid JSON'], 400);
    }
    
    $event = $decodedPayload['event'] ?? null;
    $data = $decodedPayload['data']['transaction'] ?? null;
    
    if (!$event || !$data) {
        Log::error('Wompi Dev: Missing event or transaction data', [
            'event' => $event,
            'data' => $data
        ]);
        return response()->json(['error' => 'Missing data'], 400);
    }
    
    Log::info('Wompi Dev: Processing event', [
        'event' => $event,
        'transaction_id' => $data['id'] ?? null,
        'transaction_status' => $data['status'] ?? null,
        'reference' => $data['reference'] ?? null,
    ]);
    
    // Procesar el evento
    if ($event === 'transaction.updated' && $data['status'] === 'APPROVED') {
        $order = Order::where('id', $data['reference'])->first();
        
        if ($order && $order->status === 'pending') {
            $order->update([
                'status' => 'paid',
                'payment_data' => array_merge($order->payment_data ?? [], [
                    'transaction_id' => $data['id'],
                    'payment_method' => $data['payment_method_type'],
                    'status' => $data['status'],
                    'status_message' => $data['status_message'] ?? null,
                ]),
                'paid_at' => now(),
            ]);
            
            Log::info('Wompi Dev: Order marked as paid', [
                'order_id' => $order->id,
                'transaction_id' => $data['id'],
            ]);
        } else {
            Log::warning('Wompi Dev: Order not found or not pending', [
                'reference' => $data['reference'],
                'order_status' => $order->status ?? 'not_found',
            ]);
        }
    } elseif ($event === 'transaction.updated' && in_array($data['status'], ['DECLINED', 'ERROR', 'VOIDED'])) {
        $order = Order::where('id', $data['reference'])->first();
        
        if ($order && $order->status === 'pending') {
            $order->update(['status' => 'failed']);
            
            Log::info('Wompi Dev: Order marked as failed', [
                'order_id' => $order->id,
                'transaction_id' => $data['id'],
            ]);
        }
    }
    
    return response()->json(['status' => 'success']);
});
