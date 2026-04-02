<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class ReviewEstadoAtualizadoNotification extends Notification
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
        $msg = (new MailMessage)
            ->greeting('Olá!')
            ->subject('Estado do seu review atualizado')
            ->line('O estado do seu review ao livro "' . $this->review->livro->nome . '" foi atualizado para: ' . strtoupper($this->review->estado));
        if ($this->review->estado === 'recusado') {
            $msg->line('Justificação da recusa:')->line($this->review->justificacao);
        }
        return $msg->action('Ver review', url(route('livros.show', $this->review->livro)));
    }

    public function toArray($notifiable)
    {
        $statusLabel = [
            'ativo' => 'aprovado',
            'recusado' => 'recusado',
            'suspenso' => 'suspenso',
        ];
        $status = $statusLabel[$this->review->estado] ?? $this->review->estado;
        $title = 'Estado do seu review atualizado';
        $message = 'O estado do seu review ao livro "' . $this->review->livro->nome . '" foi alterado para ' . $status . '.';
        if ($this->review->estado === 'recusado' && $this->review->justificacao) {
            $message .= ' Justificação: ' . $this->review->justificacao;
        }
        return [
            'title' => $title,
            'message' => $message,
            'review_id' => $this->review->id,
            'livro_nome' => $this->review->livro->nome,
            'estado' => $this->review->estado,
            'justificacao' => $this->review->justificacao,
            'livro_url' => route('livros.show', $this->review->livro),
            // Adiciona o link relativo para o detalhe do review do cidadão
            'review_url' => route('cidadao.reviews.show', $this->review, false),
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
