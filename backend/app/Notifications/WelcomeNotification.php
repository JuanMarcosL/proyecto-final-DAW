<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class WelcomeNotification extends Notification
{
    use Queueable;

    public string $resetUrl;

    public function __construct(string $resetUrl)
    {
        $this->resetUrl = $resetUrl;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bienvenido a Field Service — Crea tu contraseña')
            ->greeting('Hola ' . $notifiable->name . ',')
            ->line('Se ha creado una cuenta para ti en Field Service.')
            ->action('Crear contraseña', $this->resetUrl)
            ->line('Este enlace expira en 60 minutos.')
            ->line('Si no esperabas este email, ignóralo.')
            ->salutation("Atentamente,\nEl equipo de Field Service");

    }
}
