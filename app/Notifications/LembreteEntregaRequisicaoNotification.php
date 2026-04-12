<?php
namespace App\Notifications;

use App\Models\Requisicao;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Notificação enviada no dia anterior ao fim previsto da requisição.
// Lembra o utilizador que deve devolver o livro em breve.
class LembreteEntregaRequisicaoNotification extends Notification
{
    // Habilita fila (async) para notificacoes em background.
    use Queueable;

    // Construtor que recebe a requisicao e canais de entrega usados nesta instancia.
    public function __construct(
        // Requisicao associada a este lembrete.
        protected Requisicao $requisicao,
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
        // Permite alternar canais (ex.: apenas database em testes).
        // Padrao: email e database notification.
        return $this->channels;
    }

    // Gera corpo da mensagem de email para envio.
    public function toMail(object $notifiable): MailMessage
    {
        // Usa dados do livro associado para montar o conteudo do lembrete.
        $livro = $this->requisicao->livro;

        // Se o livro nao existir (deletado), direciona para o dashboard como fallback seguro.
        $urlLivro = $livro ? route('livros.show', $livro) : route('dashboard');

        // Estrutura do email com contexto suficiente para identificar a requisicao.
        return (new MailMessage)
            // Assunto menciona o livro para clareza.
            ->subject('Lembrete de entrega - ' . ($livro?->nome ?? 'Livro'))
            // Saudacao personalizada com nome do utilisador.
            ->greeting('Olá, ' . ($notifiable->name ?? 'utilizador') . '!')
            // Contexto claro: devolucao e amanha.
            ->line('Este e um lembrete: a data de entrega do seu livro e amanhã.')
            // Numero da requisicao para referencia.
            ->line('N.º da requisição: ' . ($this->requisicao->numero_requisicao ?: '-'))
            // Nome do livro (ou fallback se nao existe).
            ->line('Livro: ' . ($livro?->nome ?? '-'))
            // ISBN para identificacao clara.
            ->line('ISBN: ' . ($livro?->isbn ?: '-'))
            // Data prevista de devolucao.
            ->line('Data prevista de fim: ' . ($this->requisicao->data_fim_prevista?->format('d/m/Y H:i') ?? '-'))
            // Botao chamada a acao - link para ver detalhes.
            ->action('Ver detalhes da requisição', $urlLivro)
            // Assinatura do email.
            ->line('Obrigado por usar a Biblioteca Digital.');
    }

    /**
     * Gera conteudo para notificacao armazenada no banco de dados.
     * Esta notificacao aparece como sino/bell no painel do utilizador.
     * @return array<string, mixed> Array com dados da notificacao.
     */
    public function toArray(object $notifiable): array
    {
        // Payload usado no canal database (centro de notificacoes da aplicacao).
        $livro = $this->requisicao->livro;

        return [
            // Titulo breve da notificacao.
            'title' => 'Lembrete de entrega',
            // Mensagem descritiva com numero requisicao e livro.
            'message' => 'A requisição ' . ($this->requisicao->numero_requisicao ?: '-') . ' do livro "' . ($livro?->nome ?? '-') . '" termina amanhã.',
            // Numero formatado da requisicao.
            'requisicao_numero' => $this->requisicao->numero_requisicao,
            // Nome do livro (ou null se deletado).
            'livro_nome' => $livro?->nome,
            // URL direto para visualizar livro (com fallback para dashboard).
            'livro_url' => $livro ? route('livros.show', $livro) : route('dashboard'),
            // Data prevista em formato ISO 8601.
            'data_fim_prevista' => $this->requisicao->data_fim_prevista?->toIso8601String(),
            // Timestamp ISO quando notificacao foi criada.
            'created_at' => now()->toIso8601String(),
        ];



