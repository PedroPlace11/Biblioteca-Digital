<?php

namespace App\Notifications;

use App\Models\Livro;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Notificacao enviada quando um livro que estava em alerta de disponibilidade volta a estar disponivel.
// Avisa utilizadores que criaram alertas que agora podem requisitar o livro.
class LivroDisponivelNotification extends Notification
{
    // Habilita fila (async) para notificacoes em background.
    use Queueable;

    // Construtor que recebe o livro agora disponivel e canais de entrega.
    public function __construct(
        // Livro cuja disponibilidade foi restaurada.
        protected Livro $livro,
        // Canais para enviar notificacao (mail=email, database=sino na app).
        protected array $channels = ['mail', 'database']
    ) {
    }

    /**
     * Define quais canais de notificacao serao usados.
     * @return array<int, string> Lista de canais configurados.
     */
    public function via(object $notifiable): array
    {
        // Retorna canais definidos no construtor (padrao: email e database).
        return $this->channels;
    }

    // Gera corpo da mensagem de email para envio.
    public function toMail(object $notifiable): MailMessage
    {
        // Constroi email notificando sobre livro agora disponivel.
        return (new MailMessage)
            // Assunto menciona o livro disponivel.
            ->subject('Livro disponível - ' . $this->livro->nome)
            // Saudacao personalizada com nome do utilizador.
            ->greeting('Olá, ' . ($notifiable->name ?? 'utilizador') . '!')
            // Contexto: livro voltou a estar disponivel (era pedido em alerta).
            ->line('O livro que pediu para acompanhar voltou a estar disponível para requisição.')
            // Nome do livro.
            ->line('Livro: ' . $this->livro->nome)
            // ISBN para identificacao clara.
            ->line('ISBN: ' . ($this->livro->isbn ?: '-'))
            // Botao chamada a acao - link para requisitar livro.
            ->action('Requisitar livro', route('livros.show', $this->livro))
            // Nota: sujeito a disponibilidade no momento (pode ter requisicoes concorrentes).
            ->line('Pode requisitar agora, sujeito à disponibilidade no momento da ação.');
    }

    /**
     * Gera conteudo para notificacao armazenada no banco de dados.
     * Esta notificacao aparece como sino/bell no painel do utilizador.
     * @return array<string, mixed> Array com dados da notificacao.
     */
    public function toArray(object $notifiable): array
    {
        // Payload salvo no canal database e usado no centro de notificacoes da interface.
        return [
            // Titulo breve da notificacao.
            'title' => 'Livro disponível',
            // Mensagem descritiva mencionando nome do livro.
            'message' => 'O livro "' . $this->livro->nome . '" já está disponível para nova requisição.',
            // Nome do livro para referencia.
            'livro_nome' => $this->livro->nome,
            // URL direto para visualizar livro (onde pode requisitar).
            'livro_url' => route('livros.show', $this->livro),
            // Timestamp ISO quando notificacao foi criada.
            'created_at' => now()->toIso8601String(),
        ];
