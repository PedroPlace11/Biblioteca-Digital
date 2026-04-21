<?php

namespace App\Notifications;

use App\Models\RoomJoinRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RoomJoinRequestNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly RoomJoinRequest $joinRequest)
    {
    }

    public function via(object $notifiable): array
    {
        // Em ambiente local/testing, evita timeout por limite do Mailtrap.
        if (app()->environment(['local', 'testing'])) {
            return ['database'];
        }

        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'Novo pedido para entrar na sala',
            'message' => $this->joinRequest->user->name . ' pediu permissão para entrar na sala "' . $this->joinRequest->room->name . '".',
            'room_id' => $this->joinRequest->room_id,
            'room_name' => $this->joinRequest->room->name,
            'join_request_id' => $this->joinRequest->id,
            'requester_id' => $this->joinRequest->user_id,
            'requester_name' => $this->joinRequest->user->name,
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Novo pedido de entrada em sala de chat')
            ->greeting('Olá ' . $notifiable->name . ',')
            ->line($this->joinRequest->user->name . ' pediu permissão para entrar na sala "' . $this->joinRequest->room->name . '".')
            ->line('Aceda ao chat para aprovar ou recusar o pedido.')
            ->action('Abrir Chat', route('chat.rooms.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Novo pedido para entrar na sala',
            'message' => $this->joinRequest->user->name . ' pediu permissão para entrar na sala "' . $this->joinRequest->room->name . '".',
            'room_id' => $this->joinRequest->room_id,
            'room_name' => $this->joinRequest->room->name,
            'join_request_id' => $this->joinRequest->id,
            'requester_id' => $this->joinRequest->user_id,
            'requester_name' => $this->joinRequest->user->name,
        ];
    }
}
