<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComprobanteStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $clave,
        private string $estado,
        private string $mensaje
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->estado === 'aceptado'
            ? 'Comprobante Aceptado'
            : 'Comprobante Rechazado';

        return (new MailMessage())
            ->subject("FE CR: {$subject} - {$this->clave}")
            ->greeting("Hola {$notifiable->name},")
            ->line("El comprobante con clave {$this->clave} ha sido **{$this->estado}** por Hacienda.")
            ->line("Mensaje: {$this->mensaje}")
            ->action('Ver Comprobante', url("/comprobantes?buscar={$this->clave}"))
            ->line('Gracias por usar Ociann Facturacion Electronica C.R..');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'clave' => $this->clave,
            'estado' => $this->estado,
            'mensaje' => $this->mensaje,
        ];
    }
}
