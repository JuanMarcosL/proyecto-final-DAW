<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Appointment;

class AppointmentAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(public Appointment $appointment) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $wo = $this->appointment->workOrder;
        $start = $this->appointment->scheduled_start
            ? \Carbon\Carbon::parse($this->appointment->scheduled_start)->format('d/m/Y H:i')
            : 'Por definir';
        $end = $this->appointment->scheduled_end
            ? \Carbon\Carbon::parse($this->appointment->scheduled_end)->format('d/m/Y H:i')
            : 'Por definir';

        return (new MailMessage)
            ->subject('Nueva cita asignada — Field Service')
            ->greeting('Hola ' . $notifiable->name . ',')
            ->line('Se te ha asignado una nueva cita de servicio.')
            ->line('**Orden de Trabajo:** ' . ($wo->title ?? 'N/A'))
            ->line('**Dirección:** ' . ($this->appointment->address ?? $wo->address ?? 'Por definir'))
            ->line('**Inicio:** ' . $start)
            ->line('**Fin:** ' . $end)
            ->line('Accede a la aplicación para ver más detalles.')
            ->salutation('Atentamente, El equipo de Field Service');
    }
}
