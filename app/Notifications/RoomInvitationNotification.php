<?php

namespace App\Notifications;

use App\Models\RoomInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class RoomInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Criar uma nova notificação.
     */
    public function __construct(protected RoomInvitation $invitation)
    {
    }

    /**
     * Obter os canais de notificação.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Obter a representação em base de dados da notificação.
     */
    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage()
            ->line(
                'Foste convidado para a sala: **' . $this->invitation->room->name . '**'
            )
            ->line(
                'Por: ' . $this->invitation->invitedBy->name
            )
            ->action(
                'Ver Convite',
                route('chat.invitations.index')
            );
    }

    /**
     * Obter a representação em mail da notificação.
     */
    public function toMail(object $notifiable)
    {
        return (new \Illuminate\Notifications\Messages\MailMessage())
            ->subject('Foste convidado para uma sala de chat')
            ->greeting('Olá ' . $notifiable->name)
            ->line(
                'Foste convidado para entrar na sala **' . $this->invitation->room->name . '** por ' . $this->invitation->invitedBy->name . '.'
            )
            ->lineIf(
                $this->invitation->room->description,
                'Descrição: ' . $this->invitation->room->description
            )
            ->action(
                'Aceitar Convite',
                route('chat.invitations.accept', $this->invitation)
            )
            ->action(
                'Recusar',
                route('chat.invitations.decline', $this->invitation)
            );
    }

    /**
     * Obter o array de dados da notificação.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'room_id' => $this->invitation->room_id,
            'room_name' => $this->invitation->room->name,
            'invited_by' => $this->invitation->invitedBy->name,
            'invitation_id' => $this->invitation->id,
        ];
    }
}
