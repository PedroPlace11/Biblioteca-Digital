<?php
namespace App\Notifications;

use App\Models\Requisicao;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Notificação enviada no dia anterior ao fim previsto da requisição.
class LembreteEntregaRequisicaoNotification extends Notification
{
    use Queueable;

    // Guarda a requisição e os canais de entrega usados nesta instância.
    public function __construct(
        protected Requisicao $requisicao,
        protected array $channels = ['mail', 'database']
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Permite alternar canais (ex.: apenas database em testes).
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Usa dados do livro associado para montar o conteúdo do lembrete.
        $livro = $this->requisicao->livro;

        // Se o livro não existir, direciona para o dashboard como fallback seguro.
        $urlLivro = $livro ? route('livros.show', $livro) : route('dashboard');

        // Estrutura do email com contexto suficiente para identificar a requisição.
        return (new MailMessage)
            ->subject('Lembrete de entrega - ' . ($livro?->nome ?? 'Livro'))
            ->greeting('Olá, ' . ($notifiable->name ?? 'utilizador') . '!')
            ->line('Este é um lembrete: a data de entrega do seu livro é amanhã.')
            ->line('N.º da requisição: ' . ($this->requisicao->numero_requisicao ?: '-'))
            ->line('Livro: ' . ($livro?->nome ?? '-'))
            ->line('ISBN: ' . ($livro?->isbn ?: '-'))
            ->line('Data prevista de fim: ' . ($this->requisicao->data_fim_prevista?->format('d/m/Y H:i') ?? '-'))
            ->action('Ver detalhes da requisição', $urlLivro)
            ->line('Obrigado por usar a Biblioteca Digital.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        // Payload usado no canal database (centro de notificações da aplicação).
        $livro = $this->requisicao->livro;

        return [
            'title' => 'Lembrete de entrega',
            'message' => 'A requisição ' . ($this->requisicao->numero_requisicao ?: '-') . ' do livro "' . ($livro?->nome ?? '-') . '" termina amanhã.',
            'requisicao_numero' => $this->requisicao->numero_requisicao,
            'livro_nome' => $livro?->nome,
            'livro_url' => $livro ? route('livros.show', $livro) : route('dashboard'),
            'data_fim_prevista' => $this->requisicao->data_fim_prevista?->toIso8601String(),
            'created_at' => now()->toIso8601String(),
        ];
    }
}



