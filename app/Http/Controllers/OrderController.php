<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Coupon;
use App\Models\ProductVariant;
use App\Services\WompiService;
use App\Services\NOWPaymentsService;
use App\Services\MailjetService;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected WompiService $wompiService;
    protected NOWPaymentsService $nowPaymentsService;
    protected MailjetService $mailjetService;
    protected StripeService $stripeService;

    public function __construct(
        WompiService $wompiService, 
        NOWPaymentsService $nowPaymentsService,
        MailjetService $mailjetService,
        StripeService $stripeService
    ) {
        $this->wompiService = $wompiService;
        $this->nowPaymentsService = $nowPaymentsService;
        $this->mailjetService = $mailjetService;
        $this->stripeService = $stripeService;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Si es admin, mostrar todas las órdenes con información del usuario
        if ($user->role === 'admin') {
            $orders = Order::with([
                'items.product.images', 
                'items.product.category',
                'items.product.team',
                'items.productVariant.size', 
                'address', 
                'coupon',
                'user:id,name,email' // Incluir información del usuario
            ])
            ->orderBy('created_at', 'desc')
            ->get();
        } else {
            // Si es cliente, solo sus propias órdenes
            $orders = $user->orders()
                ->with([
                    'items.product.images',
                    'items.product.category',
                    'items.product.team',
                    'items.productVariant.size',
                    'address',
                    'coupon'
                ])
                ->orderBy('created_at', 'desc')
                ->get();
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        
        // Si no es admin y no es su orden, denegar acceso
        if ($user->role !== 'admin' && $order->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }

        // Si es admin, incluir información del usuario
        if ($user->role === 'admin') {
            $order->load([
                'items.product.images', 
                'items.product.category',
                'items.product.team',
                'items.productVariant.size', 
                'address', 
                'coupon',
                'user:id,name,email'
            ]);
        } else {
            $order->load([
                'items.product.images',
                'items.product.category',
                'items.product.team',
                'items.productVariant.size',
                'address',
                'coupon'
            ]);
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $order
        ]);
    }

    public function store(Request $request): JsonResponse
{
    $validated = $request->validate([
        'address_id' => 'required|uuid|exists:addresses,id',
        'coupon_code' => 'nullable|string|exists:coupons,code',
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|uuid|exists:products,id',
        'items.*.product_variant_id' => 'required|uuid|exists:product_variants,id',
        'items.*.quantity' => 'required|integer|min:1',
        'items.*.customization_name' => 'nullable|string|max:255',
        'items.*.customization_number' => 'nullable|string|max:50',
        'payment_gateway' => 'required|in:wompi,nowpayments,paymentnow,stripe',
        'crypto_currency' => 'nullable|string',
        'redirect_url' => 'nullable|url',
        'notes' => 'nullable|string',
    ]);

    return DB::transaction(function () use ($validated, $request) {
        // Calculate subtotal
        $subtotal = 0;
        $orderItems = [];

        foreach ($validated['items'] as $item) {
            $productVariant = ProductVariant::with('product')->findOrFail($item['product_variant_id']);

            // Usar el precio según la pasarela de pago
            // Wompi usa COP, Stripe y NOWPayments usan USD
            if ($validated['payment_gateway'] === 'wompi') {
                $unitPrice = $productVariant->product->price_cop;
            } else {
                // stripe, nowpayments, paymentnow usan USD
                $unitPrice = $productVariant->product->price_usd;
            }
            
            $totalPrice = $unitPrice * $item['quantity'];
            $subtotal += $totalPrice;

            $orderItems[] = [
                'product_id' => $item['product_id'],
                'product_variant_id' => $item['product_variant_id'],
                'product_name' => $productVariant->product->name,
                'product_size' => $productVariant->size->name,
                'unit_price' => $unitPrice,
                'quantity' => $item['quantity'],
                'total_price' => $totalPrice,
                'customization_name' => $item['customization_name'] ?? null,
                'customization_number' => $item['customization_number'] ?? null,
            ];
        }

        // Apply coupon if provided
        $discountAmount = 0;
        $couponId = null;
        
        if (!empty($validated['coupon_code'])) {
            $coupon = Coupon::where('code', strtoupper($validated['coupon_code']))->first();
            
            if (!$coupon || !$coupon->isValid()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid or expired coupon'
                ], 400);
            }

            $discountAmount = $coupon->calculateDiscount($subtotal);
            $couponId = $coupon->id;
            $coupon->increment('used_count');
        }

        $taxAmount = 0; // Sin IVA (0%)
        $shippingAmount = 0; // Envío gratis
        $totalAmount = max(0, $subtotal + $taxAmount + $shippingAmount - $discountAmount);
        
        // Redondear a 2 decimales para evitar problemas de precisión
        $totalAmount = round($totalAmount, 2);

        // Log para debugging del cálculo
        Log::info('Order Amount Calculation', [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'shipping_amount' => $shippingAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'total_amount_type' => gettype($totalAmount),
        ]);

        // Mapear payment_gateway para compatibilidad con la base de datos
        $paymentGateway = $validated['payment_gateway'];
        if ($paymentGateway === 'nowpayments') {
            $paymentGateway = 'paymentnow';
        }

        // Create order with status 'pending'
        $order = $request->user()->orders()->create([
            'address_id' => $validated['address_id'],
            'coupon_id' => $couponId,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'shipping_amount' => $shippingAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'status' => 'pending', // Cambiar a pending hasta confirmar pago
            'payment_gateway' => $paymentGateway,
            'payment_data' => null,
            'paid_at' => null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Create order items
        $order->items()->createMany($orderItems);

        // Preparar datos de pago según la pasarela
        $paymentData = null;
        $paymentUrls = [];

        if ($validated['payment_gateway'] === 'wompi') {
            // Wompi - Widget/Checkout integration
            // Construir redirect_url con el order_id en el path
            
            if (isset($validated['redirect_url'])) {
                // Si el cliente envía redirect_url explícita, usarla tal cual
                $redirectUrl = $validated['redirect_url'];
            } else {
                // Usar configuración o default
                $baseUrl = config('services.wompi.redirect_url')
                    ?? config('app.frontend_url') . '/order-confirmation';
                
                // Concatenar el order_id al final del path
                // Ejemplo: https://ecommerce-grancamerino.vercel.app/order-confirmation/{orderId}
                $redirectUrl = rtrim($baseUrl, '/') . '/' . $order->id;
            }
            
            // IMPORTANTE: Wompi en sandbox NO acepta https://localhost
            // Solo acepta http://localhost (sin SSL)
            // Convertir automáticamente si es necesario
            if (str_contains($redirectUrl, 'https://localhost')) {
                $redirectUrl = str_replace('https://localhost', 'http://localhost', $redirectUrl);
                Log::warning('Wompi: Converted https://localhost to http://localhost', [
                    'original_url' => $validated['redirect_url'] ?? 'config',
                    'converted_url' => $redirectUrl
                ]);
            }
            
            $paymentData = $this->wompiService->createPaymentData(
                $order->id,
                $totalAmount,
                $request->user()->email,
                $redirectUrl
            );

            // Log para debug
            Log::info('Wompi Payment Data Generated', [
                'order_id' => $order->id,
                'amount' => $totalAmount,
                'amount_in_cents' => $paymentData['amount_in_cents'],
                'reference' => $paymentData['reference'],
                'signature' => $paymentData['signature'],
                'redirect_url' => $redirectUrl,
            ]);

            // Construir URL completa del checkout (sin customer name/phone)
            $checkoutUrl = $this->wompiService->buildCheckoutUrl(
                $paymentData,
                null, // NO enviar nombre
                null  // NO enviar teléfono
            );

            Log::info('Wompi Checkout URL Generated', ['url' => $checkoutUrl]);

            // URLs para el cliente
            $paymentUrls = [
                'checkout_url' => $checkoutUrl,
                'widget_script' => 'https://checkout.wompi.co/widget.js',
            ];

            $order->update([
                'payment_reference' => $order->id,
                'payment_data' => $paymentData,
            ]);

        } elseif ($validated['payment_gateway'] === 'nowpayments') {
            // NOWPayments - Crypto payment
            $cryptoCurrency = $validated['crypto_currency'] ?? null;
            
            // Verificar configuración
            if (empty(config('services.nowpayments.api_key'))) {
                Log::error('NOWPayments: API key not configured');
                $order->delete();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment gateway not configured. Please contact support.'
                ], 500);
            }
            
            // Usar invoice para obtener una URL de pago amigable
            $nowPaymentData = [
                'price_amount' => $totalAmount,
                'price_currency' => 'usd',
                'ipn_callback_url' => route('nowpayments.webhook'),
                'order_id' => $order->id,
                'order_description' => "Order #{$order->order_number}",
            ];
            
            // Si se especifica una criptomoneda, agregarla
            if ($cryptoCurrency) {
                $nowPaymentData['pay_currency'] = strtolower($cryptoCurrency);
            }

            Log::info('NOWPayments: Creating invoice for order', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'amount' => $totalAmount,
                'data' => $nowPaymentData,
            ]);

            $invoice = $this->nowPaymentsService->createInvoice($nowPaymentData);

            if ($invoice) {
                $paymentData = [
                    'invoice_id' => $invoice['id'] ?? null,
                    'invoice_url' => $invoice['invoice_url'] ?? null,
                    'order_id' => $invoice['order_id'] ?? null,
                    'order_description' => $invoice['order_description'] ?? null,
                    'price_amount' => $invoice['price_amount'] ?? null,
                    'price_currency' => $invoice['price_currency'] ?? null,
                    'pay_currency' => $invoice['pay_currency'] ?? null,
                    'created_at' => $invoice['created_at'] ?? null,
                ];

                // URLs para el cliente
                $paymentUrls = [
                    'payment_url' => $invoice['invoice_url'] ?? null,
                    'invoice_url' => $invoice['invoice_url'] ?? null,
                ];

                $order->update([
                    'payment_reference' => $invoice['id'] ?? null,
                    'payment_data' => $paymentData,
                ]);
            } else {
                Log::error('NOWPayments: Failed to create invoice', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'amount' => $totalAmount,
                ]);
                
                $order->delete();

                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create crypto payment. Please check the logs or try again later.',
                    'debug' => [
                        'api_key_configured' => !empty(config('services.nowpayments.api_key')),
                        'ipn_secret_configured' => !empty(config('services.nowpayments.ipn_secret')),
                        'base_url' => config('services.nowpayments.url'),
                        'webhook_url' => route('nowpayments.webhook'),
                    ]
                ], 500);
            }
        } elseif ($validated['payment_gateway'] === 'stripe') {
            // Stripe - Checkout Session
            
            // Verificar configuración
            if (empty(config('services.stripe.secret_key'))) {
                Log::error('Stripe: Secret key not configured');
                $order->delete();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment gateway not configured. Please contact support.'
                ], 500);
            }

            // Construir URLs de éxito y cancelación
            if (isset($validated['redirect_url'])) {
                $successUrl = $validated['redirect_url'];
            } else {
                $baseUrl = config('app.frontend_url', 'http://localhost:3000');
                $successUrl = rtrim($baseUrl, '/') . '/order-confirmation/' . $order->id;
            }
            
            $cancelUrl = isset($validated['cancel_url']) 
                ? $validated['cancel_url'] 
                : rtrim(config('app.frontend_url', 'http://localhost:3000'), '/') . '/checkout';

            // Preparar line items para Stripe
            $stripeItems = [];
            foreach ($orderItems as $item) {
                $description = "Talla: {$item['product_size']}";
                if ($item['customization_name'] || $item['customization_number']) {
                    $description .= " | ";
                    if ($item['customization_name']) {
                        $description .= "Nombre: {$item['customization_name']} ";
                    }
                    if ($item['customization_number']) {
                        $description .= "Número: {$item['customization_number']}";
                    }
                }
                
                $stripeItems[] = [
                    'name' => $item['product_name'],
                    'description' => $description,
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity']
                ];
            }

            $lineItems = $this->stripeService->formatLineItems($stripeItems, 'usd');

            Log::info('Stripe: Creating checkout session', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'amount' => $totalAmount,
                'items_count' => count($lineItems),
            ]);

            $session = $this->stripeService->createCheckoutSession([
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_email' => $request->user()->email,
                'line_items' => $lineItems,
                'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $cancelUrl,
            ]);

            if ($session) {
                $paymentData = [
                    'session_id' => $session['id'],
                    'payment_status' => $session['payment_status'],
                    'amount_total' => $session['amount_total'],
                    'currency' => $session['currency'],
                    'url' => $session['url'],
                ];

                // URLs para el cliente
                $paymentUrls = [
                    'checkout_url' => $session['url'],
                    'session_id' => $session['id'],
                ];

                $order->update([
                    'payment_reference' => $session['id'],
                    'payment_data' => $paymentData,
                ]);
            } else {
                Log::error('Stripe: Failed to create checkout session', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'amount' => $totalAmount,
                ]);
                
                $order->delete();

                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create Stripe checkout session. Please try again later.',
                    'debug' => [
                        'secret_key_configured' => !empty(config('services.stripe.secret_key')),
                        'webhook_secret_configured' => !empty(config('services.stripe.webhook_secret')),
                    ]
                ], 500);
            }
        }

        // Enviar correo de confirmación de orden
        try {
            $this->mailjetService->sendOrderConfirmation($order);
        } catch (\Exception $e) {
            Log::error('Failed to send order confirmation email', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            // No fallar la creación de la orden si el correo falla
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Order created successfully',
            'data' => [
                'order' => $order->load(['items', 'address', 'coupon']),
                'payment_gateway' => $validated['payment_gateway'],
                'payment_data' => $paymentData,
                'payment_urls' => $paymentUrls,
                // Agregar campos directos para facilitar acceso en frontend
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total_amount' => $totalAmount,
                // Agregar URL de checkout directamente en el nivel superior para fácil acceso
                'checkout_url' => $paymentUrls['checkout_url'] ?? null,
            ],
            // DEBUG: Información de la firma para verificar
            'debug' => [
                'signature_sent_to_wompi' => $paymentData['signature'] ?? null,
                'reference' => $paymentData['reference'] ?? null,
                'amount_in_cents' => $paymentData['amount_in_cents'] ?? null,
                'currency' => $paymentData['currency'] ?? null,
                'integrity_secret_configured' => !empty(config('services.wompi.integrity_secret')),
                'integrity_secret_length' => strlen(config('services.wompi.integrity_secret') ?? ''),
                'concatenation_formula' => 'reference + amount_in_cents + currency + integrity_secret',
                'concatenation_example' => ($paymentData['reference'] ?? '') . 
                    ($paymentData['amount_in_cents'] ?? '') . 
                    ($paymentData['currency'] ?? '') . 
                    substr(config('services.wompi.integrity_secret') ?? '', 0, 10) . '...',
            ]
        ], 201);
    });
}

public function confirmPayment(Request $request, string $orderId): JsonResponse
{
    $validated = $request->validate([
        'transaction_id' => 'required|string',
        'status' => 'required|string',
        'payment_gateway' => 'required|string',
    ]);

    $order = Order::findOrFail($orderId);

    if ($validated['status'] === 'APPROVED') {
        $order->update([
            'status' => 'paid',
            'payment_data' => [
                'transaction_id' => $validated['transaction_id'],
                'gateway' => $validated['payment_gateway'],
            ],
            'paid_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Payment confirmed',
            'data' => $order
        ]);
    } else {
        $order->update(['status' => 'failed']);

        return response()->json([
            'status' => 'error',
            'message' => 'Payment failed'
        ], 400);
    }
}


public function wompiWebhook(Request $request): JsonResponse
{
    // Obtener payload
    $payload = $request->getContent();
    
    // Log del payload recibido
    Log::info('Wompi Webhook Received', [
        'payload_raw' => $payload,
        'payload_length' => strlen($payload),
    ]);
    
    // Verificar si el payload es JSON válido
    $decodedPayload = json_decode($payload, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        Log::error('Wompi: Invalid JSON payload', [
            'error' => json_last_error_msg(),
            'payload' => $payload
        ]);
        return response()->json(['error' => 'Invalid JSON'], 400);
    }

    $event = $decodedPayload['event'] ?? null;
    $data = $decodedPayload['data']['transaction'] ?? null;

    if (!$event || !$data) {
        Log::error('Wompi: Missing event or transaction data', [
            'event' => $event,
            'data' => $data
        ]);
        return response()->json(['error' => 'Missing data'], 400);
    }

    // Log del evento a procesar
    Log::info('Wompi: Processing event', [
        'event' => $event,
        'transaction_id' => $data['id'] ?? null,
        'transaction_status' => $data['status'] ?? null,
        'reference' => $data['reference'] ?? null,
    ]);

    // Procesar el evento según el tipo y estado
    if ($event === 'transaction.updated' && $data['status'] === 'APPROVED') {
        $order = Order::where('id', $data['reference'])->first();
        
        if ($order && $order->status === 'pending') {
            $oldStatus = $order->status;
            
            $order->update([
                'status' => 'paid',
                'payment_data' => array_merge($order->payment_data ?? [], [
                    'transaction_id' => $data['id'],
                    'payment_method' => $data['payment_method_type'],
                    'status' => $data['status'],
                    'status_message' => $data['status_message'] ?? null,
                    'amount_in_cents' => $data['amount_in_cents'] ?? null,
                    'currency' => $data['currency'] ?? null,
                ]),
                'paid_at' => now(),
            ]);

            Log::info('Wompi: Order marked as paid', [
                'order_id' => $order->id,
                'transaction_id' => $data['id'],
                'amount' => $data['amount_in_cents'] ?? null,
            ]);

            // Enviar correo de confirmación de pago
            try {
                $this->mailjetService->sendOrderStatusUpdate($order, $oldStatus, 'paid');
            } catch (\Exception $e) {
                Log::error('Failed to send payment confirmation email', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            Log::warning('Wompi: Order not found or not pending', [
                'reference' => $data['reference'],
                'order_status' => $order->status ?? 'not_found',
            ]);
        }
    } elseif ($event === 'transaction.updated' && in_array($data['status'], ['DECLINED', 'ERROR', 'VOIDED'])) {
        $order = Order::where('id', $data['reference'])->first();
        
        if ($order && $order->status === 'pending') {
            $oldStatus = $order->status;
            
            $order->update([
                'status' => 'failed',
                'payment_data' => array_merge($order->payment_data ?? [], [
                    'transaction_id' => $data['id'],
                    'payment_method' => $data['payment_method_type'],
                    'status' => $data['status'],
                    'status_message' => $data['status_message'] ?? null,
                    'failure_reason' => 'Payment ' . strtolower($data['status']),
                ]),
            ]);

            Log::info('Wompi: Order marked as failed', [
                'order_id' => $order->id,
                'transaction_id' => $data['id'],
                'failure_status' => $data['status'],
            ]);

            // Enviar correo de pago fallido
            try {
                $this->mailjetService->sendOrderStatusUpdate($order, $oldStatus, 'failed');
            } catch (\Exception $e) {
                Log::error('Failed to send payment failure email', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            Log::warning('Wompi: Order not found or not pending for failure', [
                'reference' => $data['reference'],
                'order_status' => $order->status ?? 'not_found',
            ]);
        }
    } else {
        Log::info('Wompi: Event not processed', [
            'event' => $event,
            'status' => $data['status'] ?? null,
            'reason' => 'Not a transaction.updated event or status not handled',
        ]);
    }

    // Siempre responder éxito para que Wompi no reintente
    return response()->json(['status' => 'success']);
}

public function nowPaymentsWebhook(Request $request): JsonResponse
{
    // Verificar firma IPN
    $signature = $request->header('x-nowpayments-sig');
    $payload = $request->getContent();
    
    if (!$this->nowPaymentsService->verifyIPNSignature($payload, $signature)) {
        Log::warning('NOWPayments: Invalid IPN signature');
        return response()->json(['error' => 'Invalid signature'], 401);
    }

    $data = $request->all();
    $orderId = $data['order_id'] ?? null;
    $paymentStatus = $data['payment_status'] ?? null;

    if (!$orderId) {
        return response()->json(['error' => 'Missing order_id'], 400);
    }

    $order = Order::find($orderId);
    
    if (!$order) {
        Log::warning('NOWPayments: Order not found', ['order_id' => $orderId]);
        return response()->json(['error' => 'Order not found'], 404);
    }

    // Actualizar datos de pago
    $order->payment_data = array_merge($order->payment_data ?? [], [
        'payment_id' => $data['payment_id'] ?? null,
        'payment_status' => $paymentStatus,
        'pay_amount' => $data['pay_amount'] ?? null,
        'pay_currency' => $data['pay_currency'] ?? null,
        'actually_paid' => $data['actually_paid'] ?? null,
        'outcome_amount' => $data['outcome_amount'] ?? null,
        'outcome_currency' => $data['outcome_currency'] ?? null,
        'updated_at' => now()->toISOString(),
    ]);

    // Log del webhook para debugging
    Log::info('NOWPayments Webhook Received', [
        'order_id' => $orderId,
        'payment_status' => $paymentStatus,
        'payment_id' => $data['payment_id'] ?? null,
        'pay_amount' => $data['pay_amount'] ?? null,
        'actually_paid' => $data['actually_paid'] ?? null,
    ]);

    // Estados de NOWPayments: waiting, confirming, confirmed, sending, partially_paid, finished, failed, refunded, expired
    $oldStatus = $order->status;
    $statusChanged = false;
    
    switch ($paymentStatus) {
        case 'finished':
            if ($order->status !== 'paid') {
                $order->status = 'paid';
                $order->paid_at = now();
                $statusChanged = true;
            }
            break;
            
        case 'partially_paid':
            $order->status = 'pending';
            break;
            
        case 'failed':
        case 'expired':
        case 'refunded':
            if ($order->status !== 'failed') {
                $order->status = 'failed';
                $statusChanged = true;
            }
            break;
            
        case 'waiting':
        case 'confirming':
        case 'confirmed':
        case 'sending':
            $order->status = 'pending';
            break;
    }

    $order->save();

    // Enviar correo si el estado cambió
    if ($statusChanged) {
        try {
            $this->mailjetService->sendOrderStatusUpdate($order, $oldStatus, $order->status);
        } catch (\Exception $e) {
            Log::error('Failed to send NOWPayments status update email', [
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $order->status,
                'error' => $e->getMessage()
            ]);
        }
    }

    return response()->json(['status' => 'success']);
}

    /**
     * Stripe Webhook Handler
     */
    public function stripeWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        // Verificar firma del webhook
        if (!$this->stripeService->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Stripe: Invalid webhook signature');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event = json_decode($payload, true);
        
        if (!$event || !isset($event['type'])) {
            Log::error('Stripe: Invalid webhook payload');
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        Log::info('Stripe Webhook Received', [
            'event_type' => $event['type'],
            'event_id' => $event['id'] ?? null,
        ]);

        // Procesar eventos de Stripe
        switch ($event['type']) {
            case 'checkout.session.completed':
                $session = $event['data']['object'];
                $orderId = $session['client_reference_id'] ?? $session['metadata']['order_id'] ?? null;

                if (!$orderId) {
                    Log::warning('Stripe: No order_id in checkout.session.completed');
                    return response()->json(['error' => 'No order_id'], 400);
                }

                $order = Order::find($orderId);

                if (!$order) {
                    Log::warning('Stripe: Order not found', ['order_id' => $orderId]);
                    return response()->json(['error' => 'Order not found'], 404);
                }

                if ($order->status === 'pending' && $session['payment_status'] === 'paid') {
                    $oldStatus = $order->status;

                    $order->update([
                        'status' => 'paid',
                        'payment_data' => array_merge($order->payment_data ?? [], [
                            'session_id' => $session['id'],
                            'payment_intent' => $session['payment_intent'] ?? null,
                            'payment_status' => $session['payment_status'],
                            'amount_total' => $session['amount_total'],
                            'currency' => $session['currency'],
                            'customer_email' => $session['customer_email'] ?? null,
                        ]),
                        'paid_at' => now(),
                    ]);

                    Log::info('Stripe: Order marked as paid', [
                        'order_id' => $order->id,
                        'session_id' => $session['id'],
                        'amount' => $session['amount_total'],
                    ]);

                    // Enviar correo de confirmación de pago
                    try {
                        $this->mailjetService->sendOrderStatusUpdate($order, $oldStatus, 'paid');
                    } catch (\Exception $e) {
                        Log::error('Failed to send Stripe payment confirmation email', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                break;

            case 'checkout.session.expired':
                $session = $event['data']['object'];
                $orderId = $session['client_reference_id'] ?? $session['metadata']['order_id'] ?? null;

                if ($orderId) {
                    $order = Order::find($orderId);
                    
                    if ($order && $order->status === 'pending') {
                        $oldStatus = $order->status;
                        
                        $order->update([
                            'status' => 'failed',
                            'payment_data' => array_merge($order->payment_data ?? [], [
                                'session_id' => $session['id'],
                                'payment_status' => 'expired',
                                'expired_at' => now()->toISOString(),
                            ]),
                        ]);

                        Log::info('Stripe: Order marked as failed (session expired)', [
                            'order_id' => $order->id,
                            'session_id' => $session['id'],
                        ]);

                        // Enviar correo de pago fallido
                        try {
                            $this->mailjetService->sendOrderStatusUpdate($order, $oldStatus, 'failed');
                        } catch (\Exception $e) {
                            Log::error('Failed to send Stripe payment failure email', [
                                'order_id' => $order->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
                break;

            case 'payment_intent.payment_failed':
                $paymentIntent = $event['data']['object'];
                $orderId = $paymentIntent['metadata']['order_id'] ?? null;

                if ($orderId) {
                    $order = Order::find($orderId);
                    
                    if ($order && $order->status === 'pending') {
                        $oldStatus = $order->status;
                        
                        $order->update([
                            'status' => 'failed',
                            'payment_data' => array_merge($order->payment_data ?? [], [
                                'payment_intent' => $paymentIntent['id'],
                                'payment_status' => 'failed',
                                'failure_message' => $paymentIntent['last_payment_error']['message'] ?? null,
                            ]),
                        ]);

                        Log::info('Stripe: Order marked as failed (payment failed)', [
                            'order_id' => $order->id,
                            'payment_intent' => $paymentIntent['id'],
                        ]);

                        // Enviar correo de pago fallido
                        try {
                            $this->mailjetService->sendOrderStatusUpdate($order, $oldStatus, 'failed');
                        } catch (\Exception $e) {
                            Log::error('Failed to send Stripe payment failure email', [
                                'order_id' => $order->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
                break;

            case 'payment_intent.succeeded':
                $paymentIntent = $event['data']['object'];
                $orderId = $paymentIntent['metadata']['order_id'] ?? null;

                if ($orderId) {
                    $order = Order::find($orderId);
                    
                    if ($order && $order->status === 'pending') {
                        $oldStatus = $order->status;
                        
                        $order->update([
                            'status' => 'paid',
                            'payment_data' => array_merge($order->payment_data ?? [], [
                                'payment_intent' => $paymentIntent['id'],
                                'payment_status' => 'succeeded',
                                'amount_received' => $paymentIntent['amount_received'],
                                'currency' => $paymentIntent['currency'],
                            ]),
                            'paid_at' => now(),
                        ]);

                        Log::info('Stripe: Order marked as paid (payment_intent.succeeded)', [
                            'order_id' => $order->id,
                            'payment_intent' => $paymentIntent['id'],
                            'amount' => $paymentIntent['amount_received'],
                        ]);

                        // Enviar correo de confirmación de pago
                        try {
                            $this->mailjetService->sendOrderStatusUpdate($order, $oldStatus, 'paid');
                        } catch (\Exception $e) {
                            Log::error('Failed to send Stripe payment confirmation email', [
                                'order_id' => $order->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
                break;

            default:
                Log::info('Stripe: Unhandled event type', ['type' => $event['type']]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Get available crypto currencies for NOWPayments
     */
    public function getCryptoCurrencies(): JsonResponse
    {
        $currencies = $this->nowPaymentsService->getAvailableCurrencies();
        
        if (!$currencies) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch crypto currencies'
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'data' => $currencies
        ]);
    }

    /**
     * Get crypto payment estimate
     */
    public function getCryptoEstimate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string',
        ]);

        $estimate = $this->nowPaymentsService->getEstimatedPrice(
            $validated['amount'],
            'usd',
            strtolower($validated['currency'])
        );

        if (!$estimate) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get estimate'
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'data' => $estimate
        ]);
    }

    /**
     * Check payment status manually
     */
    public function checkPaymentStatus(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }

        $status = null;

        if ($order->payment_gateway === 'wompi' && $order->payment_data['transaction_id'] ?? null) {
            $status = $this->wompiService->getTransaction($order->payment_data['transaction_id']);
        } elseif ($order->payment_gateway === 'nowpayments' && $order->payment_reference) {
            $status = $this->nowPaymentsService->getPaymentStatus($order->payment_reference);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'order_status' => $order->status,
                'payment_status' => $status,
            ]
        ]);
    }

    /**
     * Test Wompi signature generation (for debugging)
     */
    public function testWompiSignature(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference' => 'required|string',
            'amount' => 'required|numeric',
        ]);

        $amountInCents = (int)($validated['amount'] * 100);
        $signature = $this->wompiService->generateIntegritySignature(
            $validated['reference'],
            $amountInCents
        );

        $paymentData = $this->wompiService->createPaymentData(
            $validated['reference'],
            $validated['amount'],
            'test@example.com',
            'https://example.com/result'
        );

        $checkoutUrl = $this->wompiService->buildCheckoutUrl($paymentData, 'Test User', '1234567890');

        return response()->json([
            'status' => 'success',
            'data' => [
                'reference' => $validated['reference'],
                'amount' => $validated['amount'],
                'amount_in_cents' => $amountInCents,
                'signature' => $signature,
                'payment_data' => $paymentData,
                'checkout_url' => $checkoutUrl,
                'integrity_secret_configured' => !empty(config('services.wompi.integrity_secret')),
                'instructions' => [
                    'frontend' => 'Use checkout_url directly: window.location.href = response.data.checkout_url',
                    'do_not' => 'Do NOT build the URL manually in frontend',
                ]
            ]
        ]);
    }

    /**
     * Get order checkout URL (for debugging)
     */
    public function getOrderCheckoutUrl(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }

        if ($order->payment_gateway !== 'wompi') {
            return response()->json([
                'status' => 'error',
                'message' => 'This endpoint is only for Wompi orders'
            ], 400);
        }

        $paymentData = $order->payment_data;
        
        if (!$paymentData) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment data not found'
            ], 404);
        }

        $checkoutUrl = $this->wompiService->buildCheckoutUrl(
            $paymentData,
            $request->user()->name ?? null,
            $request->user()->phone ?? null
        );

        return response()->json([
            'status' => 'success',
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'checkout_url' => $checkoutUrl,
                'payment_data' => $paymentData,
            ]
        ]);
    }



    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        // Solo admins pueden actualizar el estado de las órdenes
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,paid,preparing,shipped,in_transit,delivered,cancelled,failed'
        ]);

        $oldStatus = $order->status;
        $newStatus = $validated['status'];

        $order->update(['status' => $newStatus]);

        // Update timestamps based on status
        if ($newStatus === 'paid' && !$order->paid_at) {
            $order->update(['paid_at' => now()]);
        } elseif ($newStatus === 'shipped' && !$order->shipped_at) {
            $order->update(['shipped_at' => now()]);
        } elseif ($newStatus === 'delivered' && !$order->delivered_at) {
            $order->update(['delivered_at' => now()]);
        }

        // Enviar correo de actualización de estado
        if ($oldStatus !== $newStatus) {
            try {
                $this->mailjetService->sendOrderStatusUpdate($order, $oldStatus, $newStatus);
            } catch (\Exception $e) {
                Log::error('Failed to send order status update email', [
                    'order_id' => $order->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'error' => $e->getMessage()
                ]);
                // No fallar la actualización si el correo falla
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Order status updated successfully',
            'data' => $order
        ]);
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }

        if ($order->status === 'shipped' || $order->status === 'delivered') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot cancel shipped or delivered order'
            ], 400);
        }

        $oldStatus = $order->status;
        $order->update(['status' => 'cancelled']);

        // Enviar correo de cancelación
        try {
            $this->mailjetService->sendOrderStatusUpdate($order, $oldStatus, 'cancelled');
        } catch (\Exception $e) {
            Log::error('Failed to send order cancellation email', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Order cancelled successfully',
            'data' => $order
        ]);
    }
}
