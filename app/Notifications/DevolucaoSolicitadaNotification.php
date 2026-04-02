<?php
namespace App\Notifications;

use App\Models\Livro;
use App\Models\Requisicao;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Notificacao enviada quando um utilizador solicita a devolucao de um livro.
class DevolucaoSolicitadaNotification extends Notification
{
    use Queueable;

    // Recebe o contexto necessario para montar a mensagem de email e payload de base de dados.
    public function __construct(
        protected Requisicao $requisicao,
        protected User $solicitante,
        protected Livro $livro,
        protected array $channels = ['mail', 'database']
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Permite controlar os canais por instancia (ex.: apenas database em testes).
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Ajusta o papel para apresentacao amigavel no corpo do email.
        $papel = $this->solicitante->role === 'admin' ? 'Admin' : 'Cidadão';

        // Link direto para a pagina do livro, onde o admin pode confirmar a rececao.
        $urlLivro = route('livros.show', $this->livro);

        // Monta email com contexto completo da requisicao e do pedido de devolucao.
        return (new MailMessage)
            ->subject('Pedido de devolução - ' . $this->livro->nome)
            ->greeting('Olá, ' . ($notifiable->name ?? 'utilizador') . '!')
            ->line('Foi solicitado um pedido de devolução que aguarda confirmação de receção.')
            ->line('Livro: ' . $this->livro->nome)
            ->line('ISBN: ' . ($this->livro->isbn ?: '-'))
            ->line('Solicitante: ' . $this->solicitante->name . ' (' . $papel . ')')
            ->line('Data da requisição: ' . ($this->requisicao->created_at?->format('d/m/Y H:i') ?? '-'))
            ->line('Data do pedido de devolução: ' . ($this->requisicao->devolucao_solicitada_em?->format('d/m/Y H:i') ?? '-'))
            ->line('N.º de leitor: ' . ($this->requisicao->cidadao_numero_leitor ?: '-'))
            ->action('Confirmar receção no livro', $urlLivro)
            ->line('Este pedido deve ser confirmado por um administrador autorizado.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        // Versao curta do papel para exibicao em notificacoes persistidas no sistema.
        $papel = $this->solicitante->role === 'admin' ? 'admin' : 'cidadão';

        // Payload salvo no canal database e usado no centro de notificacoes da interface.
        return [
            'title' => 'Pedido de devolução',
            'message' => 'Pedido de devolução de "' . $this->livro->nome . '" solicitado por ' . $this->solicitante->name . ' (' . $papel . ').',
            'livro_nome' => $this->livro->nome,
            'livro_url' => route('livros.show', $this->livro),
            'cidadao_numero_leitor' => $this->requisicao->cidadao_numero_leitor,
            'solicitante_nome' => $this->solicitante->name,
            'solicitante_role' => $this->solicitante->role,
            'created_at' => now()->toIso8601String(),
        ];
    }
}



