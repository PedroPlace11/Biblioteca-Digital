<?php

namespace App\Notifications;

use App\Models\Livro;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LivroDisponivelNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Livro $livro,
        protected array $channels = ['mail', 'database']
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Livro disponível - ' . $this->livro->nome)
            ->greeting('Olá, ' . ($notifiable->name ?? 'utilizador') . '!')
            ->line('O livro que pediu para acompanhar voltou a estar disponível para requisição.')
            ->line('Livro: ' . $this->livro->nome)
            ->line('ISBN: ' . ($this->livro->isbn ?: '-'))
            ->action('Requisitar livro', route('livros.show', $this->livro))
            ->line('Pode requisitar agora, sujeito à disponibilidade no momento da ação.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Livro disponível',
            'message' => 'O livro "' . $this->livro->nome . '" já está disponível para nova requisição.',
            'livro_nome' => $this->livro->nome,
            'livro_url' => route('livros.show', $this->livro),
            'created_at' => now()->toIso8601String(),
        ];
    }
}
