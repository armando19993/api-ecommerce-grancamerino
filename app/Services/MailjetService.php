<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MailjetService
{
    protected string $apiKey;
    protected string $apiSecret;
    protected string $fromEmail;
    protected string $fromName;

    public function __construct()
    {
        $this->apiKey = config('services.mailjet.key');
        $this->apiSecret = config('services.mailjet.secret');
        $this->fromEmail = config('services.mailjet.from_email', config('mail.from.address'));
        $this->fromName = config('services.mailjet.from_name', config('mail.from.name'));
    }

    public function sendOrderConfirmation($order)
    {
        $user = $order->user;
        $items = $order->items()->with('product', 'productVariant.size')->get();

        $itemsHtml = '';
        foreach ($items as $item) {
            $itemsHtml .= "
                <tr>
                    <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$item->product_name} - {$item->product_size}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>{$item->quantity}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>\${$item->unit_price}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>\${$item->total_price}</td>
                </tr>
            ";
        }

        $htmlContent = "
            <h2>¡Gracias por tu orden!</h2>
            <p>Hola {$user->name},</p>
            <p>Hemos recibido tu orden <strong>#{$order->order_number}</strong></p>
            
            <h3>Detalles de la orden:</h3>
            <table style='width: 100%; border-collapse: collapse;'>
                <thead>
                    <tr style='background-color: #f5f5f5;'>
                        <th style='padding: 10px; text-align: left;'>Producto</th>
                        <th style='padding: 10px; text-align: center;'>Cantidad</th>
                        <th style='padding: 10px; text-align: right;'>Precio</th>
                        <th style='padding: 10px; text-align: right;'>Total</th>
                    </tr>
                </thead>
                <tbody>
                    {$itemsHtml}
                </tbody>
            </table>
            
            <div style='margin-top: 20px; padding: 15px; background-color: #f9f9f9;'>
                <p><strong>Subtotal:</strong> \${$order->subtotal}</p>
                <p><strong>Descuento:</strong> \${$order->discount_amount}</p>
                <p><strong>Total:</strong> \${$order->total_amount}</p>
            </div>
            
            <p style='margin-top: 20px;'>Estado actual: <strong>{$this->getStatusLabel($order->status)}</strong></p>
            <p>Te notificaremos cuando tu orden cambie de estado.</p>
        ";

        return $this->sendEmail(
            $user->email,
            $user->name,
            "Confirmación de orden #{$order->order_number}",
            $htmlContent
        );
    }

    public function sendOrderStatusUpdate($order, $oldStatus, $newStatus)
    {
        $user = $order->user;

        $htmlContent = "
            <h2>Actualización de tu orden</h2>
            <p>Hola {$user->name},</p>
            <p>El estado de tu orden <strong>#{$order->order_number}</strong> ha cambiado.</p>
            
            <div style='margin: 20px 0; padding: 15px; background-color: #f0f9ff; border-left: 4px solid #3b82f6;'>
                <p><strong>Estado anterior:</strong> {$this->getStatusLabel($oldStatus)}</p>
                <p><strong>Estado actual:</strong> {$this->getStatusLabel($newStatus)}</p>
            </div>
            
            {$this->getStatusMessage($newStatus)}
            
            <p style='margin-top: 20px;'>Total de la orden: <strong>\${$order->total_amount}</strong></p>
        ";

        return $this->sendEmail(
            $user->email,
            $user->name,
            "Actualización de orden #{$order->order_number}",
            $htmlContent
        );
    }

    protected function sendEmail(string $toEmail, string $toName, string $subject, string $htmlContent)
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->post('https://api.mailjet.com/v3.1/send', [
                    'Messages' => [
                        [
                            'From' => [
                                'Email' => $this->fromEmail,
                                'Name' => $this->fromName
                            ],
                            'To' => [
                                [
                                    'Email' => $toEmail,
                                    'Name' => $toName
                                ]
                            ],
                            'Subject' => $subject,
                            'HTMLPart' => $htmlContent
                        ]
                    ]
                ]);

            if ($response->successful()) {
                Log::info('Email sent successfully', ['to' => $toEmail, 'subject' => $subject]);
                return true;
            }

            Log::error('Failed to send email', [
                'to' => $toEmail,
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('Email sending exception', [
                'to' => $toEmail,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    protected function getStatusLabel(string $status): string
    {
        return match($status) {
            'pending' => 'Pendiente',
            'paid' => 'Pagado',
            'preparing' => 'Preparando',
            'shipped' => 'Enviado',
            'in_transit' => 'En tránsito',
            'delivered' => 'Entregado',
            'cancelled' => 'Cancelado',
            'failed' => 'Fallido',
            default => ucfirst($status)
        };
    }

    protected function getStatusMessage(string $status): string
    {
        return match($status) {
            'paid' => '<p>¡Tu pago ha sido confirmado! Comenzaremos a preparar tu orden.</p>',
            'preparing' => '<p>Estamos preparando tu orden con mucho cuidado.</p>',
            'shipped' => '<p>¡Tu orden ha sido enviada! Pronto la recibirás.</p>',
            'in_transit' => '<p>Tu orden está en camino.</p>',
            'delivered' => '<p>¡Tu orden ha sido entregada! Esperamos que disfrutes tu compra.</p>',
            'cancelled' => '<p>Tu orden ha sido cancelada. Si tienes preguntas, contáctanos.</p>',
            'failed' => '<p>Hubo un problema con tu orden. Por favor contáctanos.</p>',
            default => ''
        };
    }
}
