<?php

namespace App\Notifications;

use App\Models\Encomenda;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Notificacao enviada quando uma nova encomenda e criada.
// Alerta admin e sistema sobre nova ordem para processamento.
class EncomendaCriadaNotification extends Notification
{
    // Habilita fila (async) para notificacoes em background.
    use Queueable;

    // Construtor que recebe a encomenda criada, o cidadao que a fez e canais de entrega.
    public function __construct(
        // Encomenda recentemente criada com todos os itens.
        protected Encomenda $encomenda,
        // Utilizador que criou a encomenda.
        protected User $cidadao,
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
        // Constroi email com contexto completo da encomenda criada.
        return (new MailMessage)
            // Assunto identifica claramente a nova encomenda.
            ->subject('Nova encomenda recebida #' . $this->encomenda->id)
            // Saudacao personalizada com nome do utilisador.
            ->greeting('Olá, ' . ($notifiable->name ?? 'utilizador') . '!')
            // Contexto: informacao sobre nova encomenda registada.
            ->line('Foi registada uma nova encomenda na biblioteca.')
            // ID da encomenda para referencia.
            ->line('N.º da encomenda: #' . $this->encomenda->id)
            // Nome do cidadao que criou a encomenda.
            ->line('Cidadão: ' . $this->cidadao->name)
            // Numero de leitor para identificacao.
            ->line('N.º leitor: ' . ($this->cidadao->numero_leitor ?: '-'))
            // Valor total da encomenda formatado com moeda.
            ->line('Total: ' . number_format((float) $this->encomenda->total, 2, ',', '.') . ' €')
            // Estado atual da encomenda.
            ->line('Estado: ' . $this->encomenda->estado)
            // Botao chamada a acao - link para ver encomenda no admin.
            ->action('Ver encomenda', route('admin.encomendas.show', $this->encomenda))
            // Informacao sobre notificacao no painel.
            ->line('A encomenda também ficou disponível no sininho da aplicação.');
    }

    // Gera conteudo para notificacao armazenada no banco de dados.
    public function toArray(object $notifiable): array
    {
        // Payload salvo no canal database e usado no centro de notificacoes da interface.
        return [
            // Titulo breve e identificador da encomenda.
            'title' => 'Nova encomenda recebida #' . $this->encomenda->id,
            // Mensagem descritiva com quem criou.
            'message' => 'Foi criada uma nova encomenda por ' . $this->cidadao->name . ' (' . ($this->cidadao->numero_leitor ?: '-') . ').',
            // ID da encomenda para reference rapida.
            'encomenda_id' => $this->encomenda->id,
            // URL direto para visualizar encomenda (admin painel).
            'encomenda_url' => route('admin.encomendas.show', $this->encomenda),
            // Nome do cidadao.
            'cidadao_nome' => $this->cidadao->name,
            // Email do cidadao.
            'cidadao_email' => $this->cidadao->email,
            // Numero de leitor do cidadao.
            'cidadao_numero_leitor' => $this->cidadao->numero_leitor,
            // Valor total da encomenda como float.
            'total' => (float) $this->encomenda->total,
            // Estado atual da encomenda (ex: pendente_pagamento).
            'estado' => $this->encomenda->estado,
            // Timestamp ISO quando notificacao foi criada.
            'created_at' => now()->toIso8601String(),
        ];
    }
}
