<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $resetUrl = config('app.frontend_url', 'http://localhost:5173')
            . '/reset-password?token=' . $this->token
            . '&email=' . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Restablecer contraseña — Field Service')
            ->greeting('Hola ' . $notifiable->name . ',')
            ->line('Has solicitado restablecer tu contraseña.')
            ->action('Restablecer contraseña', $resetUrl)
            ->line('Este enlace expira en 60 minutos.')
            ->line('Si no solicitaste esto, ignora este email.')
            ->salutation("Atentamente, <br> El equipo de Field Service");
    }
}
