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

    protected function getEmailTemplate($content, $title = '')
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        </head>
        <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f3f4f6;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #f3f4f6; padding: 20px 0;'>
                <tr>
                    <td align='center'>
                        <table width='600' cellpadding='0' cellspacing='0' style='background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                            <!-- Header -->
                            <tr>
                                <td style='background: linear-gradient(135deg, #075e38 0%, #0a7d4a 100%); padding: 40px 30px; text-align: center;'>
                                    <img src='https://res.cloudinary.com/dww5s0b7p/image/upload/v1772572062/Camerino-2_1_1_gvqdfd.png' alt='Gran Camerino' style='max-width: 180px; height: auto; margin-bottom: 20px;'>
                                    " . ($title ? "<h1 style='color: #ffffff; margin: 0; font-size: 28px; font-weight: bold;'>{$title}</h1>" : "") . "
                                </td>
                            </tr>
                            
                            <!-- Content -->
                            <tr>
                                <td style='padding: 40px 30px;'>
                                    {$content}
                                </td>
                            </tr>
                            
                            <!-- Footer -->
                            <tr>
                                <td style='background-color: #f9fafb; padding: 30px; text-align: center; border-top: 1px solid #e5e7eb;'>
                                    <p style='color: #6b7280; font-size: 13px; line-height: 1.6; margin: 0 0 10px 0;'>
                                        ¿Tienes preguntas? Contáctanos en cualquier momento.
                                    </p>
                                    <p style='color: #9ca3af; font-size: 12px; margin: 0;'>
                                        © 2024 Gran Camerino. Todos los derechos reservados.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";
    }

    public function sendOrderConfirmation($order)
    {
        $user = $order->user;
        $items = $order->items()->with('product', 'productVariant.size')->get();

        $itemsHtml = '';
        foreach ($items as $item) {
            $customization = '';
            if ($item->customization_name || $item->customization_number) {
                $customization = '<br><small style="color: #666; font-style: italic;">';
                if ($item->customization_name) {
                    $customization .= "👤 Nombre: {$item->customization_name} ";
                }
                if ($item->customization_number) {
                    $customization .= "🔢 Número: {$item->customization_number}";
                }
                $customization .= '</small>';
            }
            
            $itemsHtml .= "
                <tr>
                    <td style='padding: 15px 10px; border-bottom: 1px solid #e5e7eb;'>
                        <strong style='color: #1f2937;'>{$item->product_name}</strong><br>
                        <span style='color: #6b7280; font-size: 14px;'>Talla: {$item->product_size}</span>
                        {$customization}
                    </td>
                    <td style='padding: 15px 10px; border-bottom: 1px solid #e5e7eb; text-align: center; color: #1f2937;'>{$item->quantity}</td>
                    <td style='padding: 15px 10px; border-bottom: 1px solid #e5e7eb; text-align: right; color: #1f2937;'>\${$item->unit_price}</td>
                    <td style='padding: 15px 10px; border-bottom: 1px solid #e5e7eb; text-align: right; color: #075e38; font-weight: bold;'>\${$item->total_price}</td>
                </tr>
            ";
        }

        $content = "
            <p style='color: #1f2937; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;'>
                Hola <strong style='color: #075e38;'>{$user->name}</strong>,
            </p>
            <p style='color: #4b5563; font-size: 15px; line-height: 1.6; margin: 0 0 30px 0;'>
                Hemos recibido tu orden y estamos procesándola. A continuación encontrarás los detalles:
            </p>
            
            <!-- Order Number -->
            <div style='background-color: #f0fdf4; border-left: 4px solid #075e38; padding: 15px 20px; margin-bottom: 30px; border-radius: 4px;'>
                <p style='margin: 0; color: #065f46; font-size: 14px;'>Número de orden</p>
                <p style='margin: 5px 0 0 0; color: #075e38; font-size: 24px; font-weight: bold;'>#{$order->order_number}</p>
            </div>
            
            <h2 style='color: #075e38; font-size: 20px; margin: 0 0 20px 0; border-bottom: 2px solid #075e38; padding-bottom: 10px;'>
                Detalles de tu pedido
            </h2>
            
            <!-- Products Table -->
            <table width='100%' cellpadding='0' cellspacing='0' style='border-collapse: collapse; margin-bottom: 30px;'>
                <thead>
                    <tr style='background-color: #f9fafb;'>
                        <th style='padding: 12px 10px; text-align: left; color: #075e38; font-size: 14px; font-weight: bold; border-bottom: 2px solid #075e38;'>Producto</th>
                        <th style='padding: 12px 10px; text-align: center; color: #075e38; font-size: 14px; font-weight: bold; border-bottom: 2px solid #075e38;'>Cant.</th>
                        <th style='padding: 12px 10px; text-align: right; color: #075e38; font-size: 14px; font-weight: bold; border-bottom: 2px solid #075e38;'>Precio</th>
                        <th style='padding: 12px 10px; text-align: right; color: #075e38; font-size: 14px; font-weight: bold; border-bottom: 2px solid #075e38;'>Total</th>
                    </tr>
                </thead>
                <tbody>
                    {$itemsHtml}
                </tbody>
            </table>
            
            <!-- Totals -->
            <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom: 30px;'>
                <tr>
                    <td style='padding: 10px 0; text-align: right; color: #6b7280; font-size: 15px;'>Subtotal:</td>
                    <td style='padding: 10px 0 10px 20px; text-align: right; color: #1f2937; font-size: 15px; font-weight: 600; width: 120px;'>\${$order->subtotal}</td>
                </tr>
                <tr>
                    <td style='padding: 10px 0; text-align: right; color: #6b7280; font-size: 15px;'>Descuento:</td>
                    <td style='padding: 10px 0 10px 20px; text-align: right; color: #dc2626; font-size: 15px; font-weight: 600;'>-\${$order->discount_amount}</td>
                </tr>
                <tr style='border-top: 2px solid #075e38;'>
                    <td style='padding: 15px 0 0 0; text-align: right; color: #075e38; font-size: 18px; font-weight: bold;'>Total:</td>
                    <td style='padding: 15px 0 0 20px; text-align: right; color: #075e38; font-size: 24px; font-weight: bold;'>\${$order->total_amount}</td>
                </tr>
            </table>
            
            <!-- Status -->
            <div style='background-color: #fffbeb; border: 1px solid #fbbf24; padding: 15px 20px; border-radius: 6px; margin-bottom: 20px;'>
                <p style='margin: 0; color: #92400e; font-size: 14px;'>
                    <strong>Estado actual:</strong> <span style='color: #b45309; font-weight: bold;'>{$this->getStatusLabel($order->status)}</span>
                </p>
            </div>
            
            <p style='color: #6b7280; font-size: 14px; line-height: 1.6; margin: 0;'>
                Te mantendremos informado sobre cualquier cambio en el estado de tu orden.
            </p>
        ";

        $htmlContent = $this->getEmailTemplate($content, '¡Gracias por tu orden!');

        return $this->sendEmail(
            $user->email,
            $user->name,
            "Confirmación de orden #{$order->order_number}",
            $htmlContent
        );
    }

    public function sendPasswordResetEmail($user, string $resetUrl): bool
    {
        $content = "
            <p style='color: #1f2937; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;'>
                Hola <strong style='color: #075e38;'>{$user->name}</strong>,
            </p>
            <p style='color: #4b5563; font-size: 15px; line-height: 1.6; margin: 0 0 30px 0;'>
                Recibimos una solicitud para restablecer la contraseña de tu cuenta. Si no fuiste tú, puedes ignorar este correo.
            </p>

            <!-- CTA Button -->
            <table width='100%' cellpadding='0' cellspacing='0' style='margin: 30px 0;'>
                <tr>
                    <td align='center'>
                        <a href='{$resetUrl}'
                           style='display: inline-block; background: linear-gradient(135deg, #075e38 0%, #0a7d4a 100%);
                                  color: #ffffff; text-decoration: none; font-size: 16px; font-weight: bold;
                                  padding: 16px 40px; border-radius: 8px; letter-spacing: 0.5px;'>
                            🔑 Restablecer contraseña
                        </a>
                    </td>
                </tr>
            </table>

            <!-- Expiry notice -->
            <div style='background-color: #fffbeb; border: 1px solid #fbbf24; padding: 15px 20px; border-radius: 6px; margin: 30px 0;'>
                <p style='margin: 0; color: #92400e; font-size: 14px; line-height: 1.6;'>
                    ⏱️ <strong>Este enlace expira en 60 minutos.</strong> Si ya expiró, solicita uno nuevo desde la pantalla de inicio de sesión.
                </p>
            </div>

            <!-- Fallback URL -->
            <p style='color: #9ca3af; font-size: 12px; line-height: 1.6; margin: 20px 0 0 0;'>
                Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
                <span style='color: #075e38; word-break: break-all;'>{$resetUrl}</span>
            </p>
        ";

        $htmlContent = $this->getEmailTemplate($content, 'Restablecer contraseña');

        return $this->sendEmail(
            $user->email,
            $user->name,
            'Restablece tu contraseña - Gran Camerino',
            $htmlContent
        );
    }

    public function sendPasswordChangedEmail($user): bool
    {
        $content = "
            <p style='color: #1f2937; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;'>
                Hola <strong style='color: #075e38;'>{$user->name}</strong>,
            </p>
            <p style='color: #4b5563; font-size: 15px; line-height: 1.6; margin: 0 0 30px 0;'>
                Tu contraseña ha sido actualizada exitosamente.
            </p>

            <!-- Success box -->
            <div style='background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-radius: 8px; padding: 30px; margin: 30px 0; text-align: center;'>
                <div style='font-size: 48px; margin-bottom: 15px;'>✅</div>
                <p style='color: #065f46; font-size: 18px; font-weight: bold; margin: 0;'>¡Contraseña actualizada!</p>
                <p style='color: #4b5563; font-size: 14px; margin: 10px 0 0 0;'>Ya puedes iniciar sesión con tu nueva contraseña.</p>
            </div>

            <!-- Security warning -->
            <div style='background-color: #fef2f2; border-left: 4px solid #dc2626; padding: 15px 20px; border-radius: 4px; margin: 30px 0;'>
                <p style='margin: 0; color: #991b1b; font-size: 14px; line-height: 1.6;'>
                    🔒 <strong>¿No fuiste tú?</strong> Si no realizaste este cambio, contacta a nuestro equipo de soporte de inmediato.
                </p>
            </div>

            <p style='color: #6b7280; font-size: 14px; line-height: 1.6; margin: 0;'>
                Por seguridad, todas las sesiones activas han sido invalidadas. Inicia sesión nuevamente.
            </p>
        ";

        $htmlContent = $this->getEmailTemplate($content, 'Contraseña actualizada');

        return $this->sendEmail(
            $user->email,
            $user->name,
            'Tu contraseña fue actualizada - Gran Camerino',
            $htmlContent
        );
    }

    public function sendWelcomeEmail($user)
    {
        $content = "
            <p style='color: #1f2937; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;'>
                Hola <strong style='color: #075e38;'>{$user->name}</strong>,
            </p>
            <p style='color: #4b5563; font-size: 15px; line-height: 1.6; margin: 0 0 30px 0;'>
                ¡Gracias por unirte a Gran Camerino! Estamos emocionados de tenerte con nosotros en nuestra comunidad de apasionados del fútbol.
            </p>
            
            <div style='background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-radius: 8px; padding: 30px; margin: 30px 0;'>
                <h3 style='color: #075e38; font-size: 20px; margin: 0 0 20px 0;'>🎉 ¿Qué puedes hacer ahora?</h3>
                <ul style='color: #1f2937; font-size: 15px; line-height: 2; margin: 0; padding-left: 20px;'>
                    <li><strong style='color: #075e38;'>Explora</strong> nuestro catálogo de camisetas oficiales</li>
                    <li><strong style='color: #075e38;'>Personaliza</strong> tu camiseta con nombre y número</li>
                    <li><strong style='color: #075e38;'>Guarda</strong> tus productos favoritos</li>
                    <li><strong style='color: #075e38;'>Recibe</strong> notificaciones sobre tus pedidos</li>
                </ul>
            </div>
            
            <div style='background-color: #f0fdf4; border-left: 4px solid #075e38; padding: 20px; margin: 30px 0; border-radius: 4px;'>
                <p style='margin: 0; color: #065f46; font-size: 15px; line-height: 1.6;'>
                    <strong>💡 Consejo:</strong> Mantén tu información de contacto actualizada para recibir todas las novedades y promociones especiales.
                </p>
            </div>
            
            <p style='color: #4b5563; font-size: 15px; line-height: 1.6; margin: 30px 0 0 0;'>
                Si tienes alguna pregunta, nuestro equipo está aquí para ayudarte.
            </p>
            <p style='color: #075e38; font-size: 16px; font-weight: bold; margin: 20px 0 0 0;'>
                ¡Felices compras! ⚽
            </p>
        ";

        $htmlContent = $this->getEmailTemplate($content, '¡Bienvenido a Gran Camerino!');

        return $this->sendEmail(
            $user->email,
            $user->name,
            "¡Bienvenido a Gran Camerino!",
            $htmlContent
        );
    }

    public function sendOrderStatusUpdate($order, $oldStatus, $newStatus)
    {
        $user = $order->user;
        
        $statusIcon = $this->getStatusIcon($newStatus);
        $statusColor = $this->getStatusColor($newStatus);

        $content = "
            <p style='color: #1f2937; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;'>
                Hola <strong style='color: #075e38;'>{$user->name}</strong>,
            </p>
            <p style='color: #4b5563; font-size: 15px; line-height: 1.6; margin: 0 0 30px 0;'>
                Tenemos una actualización sobre tu orden <strong style='color: #075e38;'>#{$order->order_number}</strong>
            </p>
            
            <!-- Status Change Box -->
            <div style='background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%); border-radius: 8px; padding: 30px; margin: 30px 0; border: 2px solid #e5e7eb;'>
                <table width='100%' cellpadding='0' cellspacing='0'>
                    <tr>
                        <td style='text-align: center; padding-bottom: 20px;'>
                            <div style='font-size: 48px; margin-bottom: 10px;'>{$statusIcon}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style='text-align: center;'>
                            <p style='margin: 0 0 10px 0; color: #6b7280; font-size: 14px;'>Estado anterior</p>
                            <p style='margin: 0 0 20px 0; color: #9ca3af; font-size: 16px; text-decoration: line-through;'>{$this->getStatusLabel($oldStatus)}</p>
                            <div style='width: 40px; height: 2px; background-color: #075e38; margin: 0 auto 20px auto;'></div>
                            <p style='margin: 0 0 10px 0; color: #6b7280; font-size: 14px;'>Estado actual</p>
                            <p style='margin: 0; color: {$statusColor}; font-size: 24px; font-weight: bold;'>{$this->getStatusLabel($newStatus)}</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            {$this->getStatusMessage($newStatus)}
            
            <!-- Order Summary -->
            <div style='background-color: #f9fafb; border-radius: 6px; padding: 20px; margin: 30px 0;'>
                <table width='100%' cellpadding='0' cellspacing='0'>
                    <tr>
                        <td style='padding: 8px 0; color: #6b7280; font-size: 14px;'>Número de orden:</td>
                        <td style='padding: 8px 0; text-align: right; color: #075e38; font-size: 14px; font-weight: bold;'>#{$order->order_number}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #6b7280; font-size: 14px;'>Total:</td>
                        <td style='padding: 8px 0; text-align: right; color: #075e38; font-size: 18px; font-weight: bold;'>\${$order->total_amount}</td>
                    </tr>
                </table>
            </div>
            
            <p style='color: #6b7280; font-size: 14px; line-height: 1.6; margin: 0;'>
                Seguiremos manteniéndote informado sobre cualquier cambio en tu pedido.
            </p>
        ";

        $htmlContent = $this->getEmailTemplate($content, 'Actualización de tu orden');

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

    protected function getStatusIcon(string $status): string
    {
        return match($status) {
            'paid' => '✅',
            'preparing' => '📦',
            'shipped' => '🚚',
            'in_transit' => '🛫',
            'delivered' => '🎉',
            'cancelled' => '❌',
            'failed' => '⚠️',
            default => '📋'
        };
    }

    protected function getStatusColor(string $status): string
    {
        return match($status) {
            'paid' => '#075e38',
            'preparing' => '#0891b2',
            'shipped' => '#7c3aed',
            'in_transit' => '#2563eb',
            'delivered' => '#16a34a',
            'cancelled' => '#dc2626',
            'failed' => '#ea580c',
            default => '#6b7280'
        };
    }

    protected function getStatusMessage(string $status): string
    {
        $messages = [
            'paid' => [
                'icon' => '💳',
                'title' => '¡Pago confirmado!',
                'message' => 'Tu pago ha sido procesado exitosamente. Comenzaremos a preparar tu orden de inmediato.',
                'color' => '#075e38'
            ],
            'preparing' => [
                'icon' => '📦',
                'title' => 'Preparando tu orden',
                'message' => 'Estamos empacando tu pedido con mucho cuidado para que llegue en perfectas condiciones.',
                'color' => '#0891b2'
            ],
            'shipped' => [
                'icon' => '🚚',
                'title' => '¡Tu orden está en camino!',
                'message' => 'Tu pedido ha sido enviado y pronto estará en tus manos. ¡La emoción está cerca!',
                'color' => '#7c3aed'
            ],
            'in_transit' => [
                'icon' => '🛫',
                'title' => 'En tránsito',
                'message' => 'Tu orden está viajando hacia ti. Muy pronto podrás disfrutar de tu compra.',
                'color' => '#2563eb'
            ],
            'delivered' => [
                'icon' => '🎉',
                'title' => '¡Entregado!',
                'message' => 'Tu orden ha sido entregada. ¡Esperamos que disfrutes tu nueva camiseta! Gracias por confiar en nosotros.',
                'color' => '#16a34a'
            ],
            'cancelled' => [
                'icon' => '❌',
                'title' => 'Orden cancelada',
                'message' => 'Tu orden ha sido cancelada. Si tienes alguna pregunta o necesitas ayuda, no dudes en contactarnos.',
                'color' => '#dc2626'
            ],
            'failed' => [
                'icon' => '⚠️',
                'title' => 'Problema con el pago',
                'message' => 'Hubo un problema al procesar tu pago. Por favor, verifica tu método de pago o contáctanos para ayudarte.',
                'color' => '#ea580c'
            ]
        ];

        $info = $messages[$status] ?? null;
        
        if (!$info) {
            return '';
        }

        return "
            <div style='background-color: #f0fdf4; border-left: 4px solid {$info['color']}; padding: 20px; margin: 30px 0; border-radius: 4px;'>
                <p style='margin: 0 0 10px 0; color: {$info['color']}; font-size: 18px; font-weight: bold;'>
                    {$info['icon']} {$info['title']}
                </p>
                <p style='margin: 0; color: #065f46; font-size: 15px; line-height: 1.6;'>
                    {$info['message']}
                </p>
            </div>
        ";
    }
}
