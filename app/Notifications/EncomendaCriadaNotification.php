<?php

namespace App\Notifications;

use App\Models\Encomenda;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EncomendaCriadaNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Encomenda $encomenda,
        protected User $cidadao,
        protected array $channels = ['mail', 'database']
    ) {
    }

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nova encomenda recebida #' . $this->encomenda->id)
            ->greeting('Olá, ' . ($notifiable->name ?? 'utilizador') . '!')
            ->line('Foi registada uma nova encomenda na biblioteca.')
            ->line('N.º da encomenda: #' . $this->encomenda->id)
            ->line('Cidadão: ' . $this->cidadao->name)
            ->line('N.º leitor: ' . ($this->cidadao->numero_leitor ?: '-'))
            ->line('Total: ' . number_format((float) $this->encomenda->total, 2, ',', '.') . ' €')
            ->line('Estado: ' . $this->encomenda->estado)
            ->action('Ver encomenda', route('admin.encomendas.show', $this->encomenda))
            ->line('A encomenda também ficou disponível no sininho da aplicação.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Nova encomenda recebida #' . $this->encomenda->id,
            'message' => 'Foi criada uma nova encomenda por ' . $this->cidadao->name . ' (' . ($this->cidadao->numero_leitor ?: '-') . ').',
            'encomenda_id' => $this->encomenda->id,
            'encomenda_url' => route('admin.encomendas.show', $this->encomenda),
            'cidadao_nome' => $this->cidadao->name,
            'cidadao_email' => $this->cidadao->email,
            'cidadao_numero_leitor' => $this->cidadao->numero_leitor,
            'total' => (float) $this->encomenda->total,
            'estado' => $this->encomenda->estado,
            'created_at' => now()->toIso8601String(),
        ];
    }
}
