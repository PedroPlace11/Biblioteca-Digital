<?php

namespace App\Notifications;

use App\Models\Carrinho;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Notificacao enviada quando um utilizador deixa livros no carrinho ha mais de 1 hora.
// Envia email e cria entrada no banco de dados para lembrar o utilizador.
class CarrinhoAbandonadoNotification extends Notification
{
    // Habilita fila (async) para notificacoes em background.
    use Queueable;

    // Construtor que recebe o carrinho abandonado e canais de entrega.
    public function __construct(
        // Carrinho com items pendentes.
        protected Carrinho $carrinho,
        // Canais para enviar notificacao (mail=email, database=sino na app).
        protected array $channels = ['mail', 'database']
    ) {
    }

    /**
     * Define quais canais de notificacao sera usado.
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
        // Calcula quantidade total de livros no carrinho.
        $quantidadeLivros = $this->carrinho->itens->sum('quantidade');

        // Constroi email com saudacao, conteudo e call-to-action.
        return (new MailMessage)
            // Assunto do email.
            ->subject('Precisa de ajuda com a sua encomenda?')
            // Saudacao personalizada com nome do utilisador.
            ->greeting('Olá, ' . ($notifiable->name ?? 'utilizador') . '!')
            // Contexto: carrinho nao finalizado.
            ->line('Detetámos livros no seu carrinho há mais de 1 hora.')
            // Detalhes do carrinho.
            ->line('Total de livros no carrinho: ' . $quantidadeLivros)
            // Incentivo para completar compra.
            ->line('Se precisar de apoio para concluir a compra, estamos disponíveis para ajudar.')
            // Botao chamada a acao - link para carrinho.
            ->action('Retomar compra', route('carrinho.index'))
            // Assinatura do email.
            ->line('Biblioteca Digital');
    }

    /**
     * Gera conteudo para notificacao armazenada no banco de dados.
     * Esta notificacao aparece como sino/bell no painel do utilizador.
     * @return array<string, mixed> Array com dados da notificacao.
     */
    public function toArray(object $notifiable): array
    {
        // Calcula quantidade total de livros no carrinho.
        $quantidadeLivros = $this->carrinho->itens->sum('quantidade');

        // Retorna dados estruturados para armazenamento no banco.
        return [
            // Titulo exibido na notificacao.
            'title' => 'Carrinho com compra pendente',
            // Mensagem descritiva do alerta.
            'message' => 'Tem ' . $quantidadeLivros . ' livro(s) no carrinho há mais de 1 hora. Precisa de ajuda para concluir a encomenda?',
            // URL relativa para retomar compra (false = sem scheme e host).
            'carrinho_url' => route('carrinho.index', [], false),
            // Timestamp ISO quando notificacao foi criada.
            'created_at' => now()->toIso8601String(),
        ];
    }
}
