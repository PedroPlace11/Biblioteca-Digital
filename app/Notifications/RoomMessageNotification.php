<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class RoomMessageNotification extends Notification
{
    use Queueable;

    public function __construct(protected Message $message)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $room = $this->message->room;
        $sender = $this->message->user;

        $fallback = $this->message->type === 'image'
            ? '[Imagem]'
            : ($this->message->type === 'file' ? '[Arquivo]' : 'Nova mensagem');

        $previewBase = trim((string) ($this->message->content ?: $fallback));
        $preview = Str::limit($previewBase, 90);

        return [
            'type' => 'room_message',
            'title' => 'Nova mensagem em ' . ($room?->name ?? 'Sala'),
            'message' => ($sender?->name ?? 'Utilizador') . ': ' . $preview,
            'room_id' => $this->message->room_id,
            'room_name' => $room?->name,
            'sender_id' => $this->message->user_id,
            'sender_name' => $sender?->name,
            'room_url' => route('chat.rooms.index', [
                'tab' => 'rooms',
                'room' => $this->message->room_id,
            ]),
            'created_at' => now()->toIso8601String(),
        ];
    }
}
