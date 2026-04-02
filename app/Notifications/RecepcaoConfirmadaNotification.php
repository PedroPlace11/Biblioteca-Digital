<?php
namespace App\Notifications;

use App\Models\Livro;
use App\Models\Requisicao;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Notificação disparada quando a receção do livro devolvido é confirmada por um admin.
class RecepcaoConfirmadaNotification extends Notification
{
    use Queueable;

    // Recebe contexto completo da requisição para compor email e notificação interna.
    public function __construct(
        protected Requisicao $requisicao,
        protected User $adminConfirmador,
        protected Livro $livro,
        protected array $channels = ['mail', 'database']
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Mantém os canais configuráveis por instância (útil para testes e fallbacks).
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Link para o detalhe do livro associado à requisição encerrada.
        $urlLivro = route('livros.show', $this->livro);

        // Email com resumo do encerramento e métricas de duração da requisição.
        return (new MailMessage)
            ->subject('Receção confirmada - ' . $this->livro->nome)
            ->greeting('Olá, ' . ($notifiable->name ?? 'utilizador') . '!')
            ->line('A receção da devolução do seu livro foi confirmada por um administrador.')
            ->line('Livro: ' . $this->livro->nome)
            ->line('ISBN: ' . ($this->livro->isbn ?: '-'))
            ->line('Admin que confirmou: ' . $this->adminConfirmador->name)
            ->line('Data de encerramento: ' . ($this->requisicao->data_recepcao_real?->format('d/m/Y H:i') ?? '-'))
            ->line('Dias decorridos: ' . ((int) ($this->requisicao->dias_decorridos ?? 0)) . ' ' . (((int) ($this->requisicao->dias_decorridos ?? 0)) === 1 ? 'dia' : 'dias'))
            ->action('Ver detalhes da requisição', $urlLivro)
            ->line('Obrigado por usar a Biblioteca Digital.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        // Payload persistido no canal database para o centro de notificações da app.
        return [
            'title' => 'Receção confirmada',
            'message' => 'A devolução de "' . $this->livro->nome . '" foi confirmada por ' . $this->adminConfirmador->name . '.',
            'livro_nome' => $this->livro->nome,
            'livro_url' => route('livros.show', $this->livro),
            'cidadao_numero_leitor' => $this->requisicao->cidadao_numero_leitor,
            'admin_confirmador_nome' => $this->adminConfirmador->name,
            'created_at' => now()->toIso8601String(),
        ];
    }
}



