<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

// Notificacao enviada quando um cidadao submete um novo review a um livro.
// Alerta admins para moderacao e decisao (aprovacao, rejeicao, suspenso).
class ReviewSubmetidoNotification extends Notification
{
    // Modelo review associado a esta notificacao.
    public $review;

    // Construtor que recebe a review recém-submetida.
    public function __construct(Review $review)
    {
        // Armazena a review para uso nos metodos de geracao de notificacoes.
        $this->review = $review;
    }

    public function via($notifiable)
    {
        // Define quais canais de notificacao serao usados para esta notificacao.
        // database: sino/bell no painel do admin
        // broadcast: transmissao em tempo real via WebSocket
        return ['database', 'broadcast'];
    }

    public function toMail($notifiable)
    {
        // Email notificando admin sobre novo review em moderacao.
        return (new MailMessage)
            // Assunto breve indicando novo review.
            ->subject('Novo Review Submetido')
            // Saudacao direcionada para admin.
            ->greeting('Olá Admin!')
            // Contexto breve da notificacao.
            ->line('Um cidadão submeteu um review a um livro.')
            // Informacoes do autor da review (nome e email).
            ->line('Cidadão: ' . $this->review->user->name . ' (' . $this->review->user->email . ')')
            // Nome do livro que foi revisitado.
            ->line('Livro: ' . $this->review->livro->nome)
            // Botao chamada a acao - link para visualizar e moderar a review.
            ->action('Ver Review', url(route('admin.reviews.show', $this->review)))
            // Separacao de secoes no email.
            ->line('Conteúdo:')
            // Conteudo completo da review submetida pelo cidadao.
            ->line($this->review->conteudo);
    }

    public function toArray($notifiable)
    {
        // Para admin, direciona para o detalhe do review (sempre caminho relativo).
        // false = parametro que force URL relativa em vez de absoluta.
        $url = route('admin.reviews.show', $this->review, false);

        // Payload usado no sino de notificacoes e em listagens internas do painel admin.
        return [
            // Titulo breve da notificacao.
            'title' => 'Novo review submetido',
            // Mensagem descritiva mencionando cidadao e livro revisitado.
            'message' => 'O cidadão ' . $this->review->user->name . ' submeteu um review ao livro "' . $this->review->livro->nome . '".',
            // ID da review para referencia no banco de dados.
            'review_id' => $this->review->id,
            // Nome do utilizador que criou a review.
            'user_nome' => $this->review->user->name,
            // Email do utilizador para contacto rapido.
            'user_email' => $this->review->user->email,
            // Nome do livro que foi revisitado.
            'livro_nome' => $this->review->livro->nome,
            // Conteudo da review (texto completo do comentario).
            'conteudo' => $this->review->conteudo,
            // URL relativo para aceder ao detalhe da review (painel admin).
            'review_url' => $url,
        ];
    }

    public function toBroadcast($notifiable)
    {
        // Gera mensagem para canal broadcast (transmissao em tempo real via WebSocket).
        // Permite notificacoes em tempo real para admins sem necessidade de refresh da pagina.
        // Encapsula dados do array em BroadcastMessage para transmissao.
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
