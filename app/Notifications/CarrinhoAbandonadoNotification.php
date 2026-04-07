<?php

namespace App\Notifications;

use App\Models\Carrinho;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CarrinhoAbandonadoNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Carrinho $carrinho,
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
        $quantidadeLivros = $this->carrinho->itens->sum('quantidade');

        return (new MailMessage)
            ->subject('Precisa de ajuda com a sua encomenda?')
            ->greeting('Olá, ' . ($notifiable->name ?? 'utilizador') . '!')
            ->line('Detetámos livros no seu carrinho há mais de 1 hora.')
            ->line('Total de livros no carrinho: ' . $quantidadeLivros)
            ->line('Se precisar de apoio para concluir a compra, estamos disponíveis para ajudar.')
            ->action('Retomar compra', route('carrinho.index'))
            ->line('Biblioteca Digital');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $quantidadeLivros = $this->carrinho->itens->sum('quantidade');

        return [
            'title' => 'Carrinho com compra pendente',
            'message' => 'Tem ' . $quantidadeLivros . ' livro(s) no carrinho há mais de 1 hora. Precisa de ajuda para concluir a encomenda?',
            'carrinho_url' => route('carrinho.index'),
            'created_at' => now()->toIso8601String(),
        ];
    }
}
