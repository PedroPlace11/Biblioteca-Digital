<?php

namespace App\Notifications;

use App\Models\DirectMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class DirectMessageNotification extends Notification
{
    public function __construct(protected DirectMessage $message)
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
        $sender = $this->message->sender;

        $fallback = $this->message->type === 'image'
            ? '[Imagem]'
            : ($this->message->type === 'file' ? '[Arquivo]' : 'Nova mensagem');

        $previewBase = trim((string) ($this->message->content ?: $fallback));
        $preview = Str::limit($previewBase, 90);

        return [
            'type' => 'direct_message',
            'title' => 'Mensagem privada de ' . ($sender?->name ?? 'Utilizador'),
            'message' => $preview,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $sender?->name,
            'direct_message_url' => route('chat.rooms.index', [
                'tab' => 'messages',
                'dm' => $this->message->sender_id,
            ]),
            'created_at' => now()->toIso8601String(),
        ];
    }
}
