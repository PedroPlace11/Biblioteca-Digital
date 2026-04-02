<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class ReviewSubmetidoNotification extends Notification
{

    public $review;

    public function __construct(Review $review)
    {
        $this->review = $review;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Novo Review Submetido')
            ->greeting('Olá Admin!')
            ->line('Um cidadão submeteu um review a um livro.')
            ->line('Cidadão: ' . $this->review->user->name . ' (' . $this->review->user->email . ')')
            ->line('Livro: ' . $this->review->livro->nome)
            ->action('Ver Review', url(route('admin.reviews.show', $this->review)))
            ->line('Conteúdo:')
            ->line($this->review->conteudo);
    }

    public function toArray($notifiable)
    {
        // Para admin, direciona para o detalhe do review (sempre caminho relativo)
        $url = route('admin.reviews.show', $this->review, false); // false = caminho relativo
        return [
            'title' => 'Novo review submetido',
            'message' => 'O cidadão ' . $this->review->user->name . ' submeteu um review ao livro "' . $this->review->livro->nome . '".',
            'review_id' => $this->review->id,
            'user_nome' => $this->review->user->name,
            'user_email' => $this->review->user->email,
            'livro_nome' => $this->review->livro->nome,
            'conteudo' => $this->review->conteudo,
            'review_url' => $url,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
