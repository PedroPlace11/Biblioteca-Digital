<?php

namespace App\Notifications;

use App\Models\Encomenda;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Notificacao enviada quando o estado de pagamento/envio de uma encomenda e atualizado.
// Notifica o cidadao sobre mudancas no processamento: aprovado (enviado), pendente, ou recusado.
class EncomendaPagamentoAtualizadoNotification extends Notification
{
    // Habilita fila (async) para notificacoes em background.
    use Queueable;

    // Construtor que recebe a encomenda, admin que fez atualizacao, novo estado e canais.
    public function __construct(
        // Encomenda cuja status foi atualizado.
        protected Encomenda $encomenda,
        // Admin que fez a atualizacao.
        protected User $admin,
        // Novo estado de pagamento (aprovado, pendente, recusado).
        protected string $estadoPagamento,
        // Canais para enviar notificacao (mail=email, database=sino na app).
        protected array $channels = ['mail', 'database']
    ) {
    }

    // Define quais canais de notificacao serao usados.
    public function via(object $notifiable): array
    {
        // Retorna canais definidos no construtor (padrao: email e database).
        return $this->channels;
    }

    // Gera corpo da mensagem de email para envio.
    public function toMail(object $notifiable): MailMessage
    {
        // Normaliza estado (converte 'aprovado' em 'enviado' para apresentacao).
        $estado = $this->normalizarEstado($this->estadoPagamento);

        // Determine assunto dinamicamente baseado no estado.
        $assunto = match ($estado) {
            // Se enviado: assunto sobre envio.
            'enviado' => 'Encomenda enviada',
            // Se pendente: assunto sobre aguardo.
            'pendente' => 'Encomenda pendente',
            // Default: recusado/outros.
            default => 'Pagamento recusado',
        };

        // Determine corpo do email dinamicamente baseado no estado.
        $linhaEstado = match ($estado) {
            'enviado' => 'A sua encomenda foi atualizada para enviada pelo administrador.',
            'pendente' => 'A sua encomenda foi marcada como pendente pelo administrador.',
            default => 'O pagamento da sua encomenda foi recusado pelo administrador.',
        };

        // Constroi email base com informacoes comuns.
        $mail = (new MailMessage)
            // Assunto customizado com ID da encomenda.
            ->subject($assunto . ' #' . $this->encomenda->id)
            // Saudacao personalizada.
            ->greeting('Olá, ' . ($notifiable->name ?? 'utilizador') . '!')
            // Mensagem do estado customizada.
            ->line($linhaEstado)
            // Numero da encomenda para referencia.
            ->line('N.º da encomenda: #' . $this->encomenda->id)
            // Estado atual completo.
            ->line('Estado atual: ' . $this->encomenda->estado)
            // Valor total formatado com moeda.
            ->line('Total: ' . number_format((float) $this->encomenda->total, 2, ',', '.') . ' €')
            // Link para ver encomenda no painel cidadao.
            ->action('Ver encomenda', route('cidadao.encomendas.show', $this->encomenda));

        // Se enviado e tem rastreio, adiciona informacoes de transportadora.
        if ($estado === 'enviado' && !empty($this->encomenda->numero_rastreio)) {
            // Transportadora (padrao CTT se nao informada).
            $mail->line('Transportadora: ' . ($this->encomenda->transportadora ?? 'CTT'))
                // Numero CTT para rastreio online.
                ->line('Número de rastreio: ' . $this->encomenda->numero_rastreio);
        }

        // Assinatura final do email.
        return $mail->line('Obrigado por usar a Biblioteca Digital.');
    }

    // Gera conteudo para notificacao armazenada no banco de dados.
    public function toArray(object $notifiable): array
    {
        // Normaliza estado para apresentacao (aprovado -> enviado).
        $estado = $this->normalizarEstado($this->estadoPagamento);

        // Payload salvo no canal database e usado no centro de notificacoes da interface.
        return [
            // Titulo dinamico baseado no estado.
            'title' => match ($estado) {
                'enviado' => 'Encomenda enviada',
                'pendente' => 'Encomenda pendente',
                default => 'Pagamento recusado',
            },
            // Mensagem descritiva com detalhes do estado.
            'message' => match ($estado) {
                // Se enviado: menciona rastreio se disponivel.
                'enviado' => 'A encomenda #' . $this->encomenda->id . ' foi enviada. '
                    . (!empty($this->encomenda->numero_rastreio) ? 'Rastreio CTT: ' . $this->encomenda->numero_rastreio . '.' : ''),
                // Se pendente: aguardando a acao.
                'pendente' => 'A encomenda #' . $this->encomenda->id . ' foi marcada como pendente.',
                // Default: recusado.
                default => 'O pagamento da encomenda #' . $this->encomenda->id . ' foi recusado.',
            },
            // ID da encomenda para referencia rapida.
            'encomenda_id' => $this->encomenda->id,
            // URL direto para visualizar encomenda.
            'encomenda_url' => route('cidadao.encomendas.show', $this->encomenda),
            // Estado normalizado (para filtragem/identificacao).
            'estado_pagamento' => $estado,
            // Transportadora (para rastreio).
            'transportadora' => $this->encomenda->transportadora,
            // Numero de rastreio CTT.
            'numero_rastreio' => $this->encomenda->numero_rastreio,
            // Valor total como float.
            'total' => (float) $this->encomenda->total,
            // Timestamp ISO quando notificacao foi criada.
            'created_at' => now()->toIso8601String(),
        ];
    }

    // Converte estado interno para representacao amigavel.
    private function normalizarEstado(string $estado): string
    {
        // Mapeia estado 'aprovado' para 'enviado' (mais legivel).
        // Outros estados ficam iguais.
        return match ($estado) {
            // Se aprovado internamente, exibe como enviado ao utilizador.
            'aprovado' => 'enviado',
            // Qualquer outro estado e retornado como-esta.
            default => $estado,
        };
}
