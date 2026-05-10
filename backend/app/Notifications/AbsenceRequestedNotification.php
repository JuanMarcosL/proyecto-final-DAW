<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Absence;

class AbsenceRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(public Absence $absence) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $tecnico = $this->absence->resource->user->name ?? 'Técnico';
        $tipo = [
            'vacation' => 'Vacaciones',
            'medical'  => 'Médica',
            'personal' => 'Personal',
            'other'    => 'Otro',
        ][$this->absence->type] ?? $this->absence->type;

        return (new MailMessage)
            ->subject('Nueva solicitud de ausencia — Field Service')
            ->greeting('Hola ' . $notifiable->name . ',')
            ->line($tecnico . ' ha solicitado una ausencia.')
            ->line('**Tipo:** ' . $tipo)
            ->line('**Desde:** ' . \Carbon\Carbon::parse($this->absence->start_date)->format('d/m/Y'))
            ->line('**Hasta:** ' . \Carbon\Carbon::parse($this->absence->end_date)->format('d/m/Y'))
            ->line($this->absence->reason ? '**Motivo:** ' . $this->absence->reason : '')
            ->line('Accede a la aplicación para aprobar o rechazar la solicitud.')
            ->salutation('Atentamente, El equipo de Field Service');
    }
}
