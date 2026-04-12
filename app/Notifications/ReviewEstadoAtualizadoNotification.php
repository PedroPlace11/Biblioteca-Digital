<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

// Notificacao enviada quando o estado de um review (ativo, recusado, suspenso) e alterado.
// Informa o autor da review sobre a decisao de moderacao.
class ReviewEstadoAtualizadoNotification extends Notification
{
    // Modelo review associado a esta notificacao.
    public $review;

    // Construtor que recebe a review cuja estado foi atualizado.
    public function __construct(Review $review)
    {
        // Armazena a review para uso nos metodos de geracao de notificacoes.
        $this->review = $review;
    }

    // Define quais canais de notificacao serao usados para esta notificacao.
    // database: sino/bell no painel do utilizador
    // broadcast: transmissao em tempo real via WebSocket
    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    // Gera corpo da mensagem de email para envio (opcional neste caso).
    public function toMail($notifiable)
    {
        // Inicia construtor de email com saudacao.
        $msg = (new MailMessage)
            ->greeting('Olá!')
            // Assunto menciona atualizacao de estado.
            ->subject('Estado do seu review atualizado')
            // Corpo principal: informa novo estado do review.
            // Estado exibido em maiusculas (PUBLICADO, RECUSADO, etc).
            ->line('O estado do seu review ao livro "' . $this->review->livro->nome . '" foi atualizado para: ' . strtoupper($this->review->estado));

        // Se review foi recusada, exibe justificacao do moderador.
        if ($this->review->estado === 'recusado') {
            // Adiciona titulo e conteudo da justificacao (motivo da rejeicao).
            $msg->line('Justificação da recusa:')->line($this->review->justificacao);
        }

        // Botao chamada a acao - link para ver review.
        return $msg->action('Ver review', url(route('livros.show', $this->review->livro)));
    }

    // Gera conteudo para notificacao armazenada no banco de dados e transmissao em broadcast.
    public function toArray($notifiable)
    {
        // Mapa de traducao de estados tecnicos para rotulos legiaveis ao utilizador.
        // ativo -> 'aprovado' (mais intuitivo que 'ativo')
        // recusado -> 'recusado' (mantido como esta)
        // suspenso -> 'suspenso' (estado temporario durante revisao)
        $statusLabel = [
            'ativo' => 'aprovado',
            'recusado' => 'recusado',
            'suspenso' => 'suspenso',
        ];

        // Obtém rotulo legivel ou fallback para estado tecnico se nao estiver no mapa.
        $status = $statusLabel[$this->review->estado] ?? $this->review->estado;

        // Titulo da notificacao.
        $title = 'Estado do seu review atualizado';

        // Mensagem descritiva com nome do livro e novo estado.
        $message = 'O estado do seu review ao livro "' . $this->review->livro->nome . '" foi alterado para ' . $status . '.';

        // Se review foi recusada e existe justificacao, apenda motivo a mensagem.
        if ($this->review->estado === 'recusado' && $this->review->justificacao) {
            // Justificacao do moderador (motivo da rejeicao).
            $message .= ' Justificação: ' . $this->review->justificacao;
        }

        // Payload usado no sino de notificacoes e em listagens internas da interface.
        return [
            // Titulo breve da notificacao.
            'title' => $title,
            // Mensagem descritiva completa.
            'message' => $message,
            // ID da review para referencia no banco de dados.
            'review_id' => $this->review->id,
            // Nome do livro que foi revisitado.
            'livro_nome' => $this->review->livro->nome,
            // Estado tecnico da review (ativo, recusado, suspenso).
            'estado' => $this->review->estado,
            // Justificacao textual se review foi recusada (null caso contrario).
            'justificacao' => $this->review->justificacao,
            // URL direto para visualizar livro e seus detalhes/reviews.
            'livro_url' => route('livros.show', $this->review->livro),
            // Link relativo para detalhe da review do cidadao (cidadao.reviews.show rota).
            'review_url' => route('cidadao.reviews.show', $this->review, false),
        ];
    }

    // Gera mensagem para canal broadcast (transmissao em tempo real via WebSocket).
    // Permite notificacoes em tempo real sem necessidade de refresh da pagina.
    public function toBroadcast($notifiable)
    {
        // Encapsula dados do array em BroadcastMessage para transmissao.
        return new BroadcastMessage($this->toArray($notifiable));
