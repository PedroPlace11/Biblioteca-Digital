<?php

namespace App\Notifications;

use App\Models\Encomenda;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EncomendaPagamentoAtualizadoNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Encomenda $encomenda,
        protected User $admin,
        protected string $estadoPagamento,
        protected array $channels = ['mail', 'database']
    ) {
    }

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $estado = $this->normalizarEstado($this->estadoPagamento);

        $assunto = match ($estado) {
            'enviado' => 'Encomenda enviada',
            'pendente' => 'Encomenda pendente',
            default => 'Pagamento recusado',
        };

        $linhaEstado = match ($estado) {
            'enviado' => 'A sua encomenda foi atualizada para enviada pelo administrador.',
            'pendente' => 'A sua encomenda foi marcada como pendente pelo administrador.',
            default => 'O pagamento da sua encomenda foi recusado pelo administrador.',
        };

        $mail = (new MailMessage)
            ->subject($assunto . ' #' . $this->encomenda->id)
            ->greeting('Olá, ' . ($notifiable->name ?? 'utilizador') . '!')
            ->line($linhaEstado)
            ->line('N.º da encomenda: #' . $this->encomenda->id)
            ->line('Estado atual: ' . $this->encomenda->estado)
            ->line('Total: ' . number_format((float) $this->encomenda->total, 2, ',', '.') . ' €')
            ->action('Ver encomenda', route('cidadao.encomendas.show', $this->encomenda));

        if ($estado === 'enviado' && !empty($this->encomenda->numero_rastreio)) {
            $mail->line('Transportadora: ' . ($this->encomenda->transportadora ?? 'CTT'))
                ->line('Número de rastreio: ' . $this->encomenda->numero_rastreio);
        }

        return $mail->line('Obrigado por usar a Biblioteca Digital.');
    }

    public function toArray(object $notifiable): array
    {
        $estado = $this->normalizarEstado($this->estadoPagamento);

        return [
            'title' => match ($estado) {
                'enviado' => 'Encomenda enviada',
                'pendente' => 'Encomenda pendente',
                default => 'Pagamento recusado',
            },
            'message' => match ($estado) {
                'enviado' => 'A encomenda #' . $this->encomenda->id . ' foi enviada. '
                    . (!empty($this->encomenda->numero_rastreio) ? 'Rastreio CTT: ' . $this->encomenda->numero_rastreio . '.' : ''),
                'pendente' => 'A encomenda #' . $this->encomenda->id . ' foi marcada como pendente.',
                default => 'O pagamento da encomenda #' . $this->encomenda->id . ' foi recusado.',
            },
            'encomenda_id' => $this->encomenda->id,
            'encomenda_url' => route('cidadao.encomendas.show', $this->encomenda),
            'estado_pagamento' => $estado,
            'transportadora' => $this->encomenda->transportadora,
            'numero_rastreio' => $this->encomenda->numero_rastreio,
            'total' => (float) $this->encomenda->total,
            'created_at' => now()->toIso8601String(),
        ];
    }

    private function normalizarEstado(string $estado): string
    {
        return match ($estado) {
            'aprovado' => 'enviado',
            default => $estado,
        };
    }
}
