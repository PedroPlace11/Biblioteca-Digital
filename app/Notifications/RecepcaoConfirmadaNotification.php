<?php
namespace App\Notifications;

use App\Models\Livro;
use App\Models\Requisicao;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Notificacao disparada quando a recepcao do livro devolvido e confirmada por um admin.
// Encerra a requisicao e notifica cidadao sobre conclusao com metricas de duracacao.
class RecepcaoConfirmadaNotification extends Notification
{
    // Habilita fila (async) para notificacoes em background.
    use Queueable;

    // Construtor que recebe contexto completo da requisicao para compor email e notificacao interna.
    public function __construct(
        // Requisicao associada a esta confirmacao de recepcao.
        protected Requisicao $requisicao,
        // Admin que confirmou a recepcao do livro.
        protected User $adminConfirmador,
        // Livro que foi devolvido e confirmado.
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
        // Mantem os canais configuravel por instancia (util para testes e fallbacks).
        // Padrao: email e database notification.
        return $this->channels;
    }

    // Gera corpo da mensagem de email para envio.
    public function toMail(object $notifiable): MailMessage
    {
        // Link para o detalhe do livro associado a requisicao encerrada.
        $urlLivro = route('livros.show', $this->livro);

        // Email com resumo do encerramento e metricas de duracao da requisicao.
        return (new MailMessage)
            // Assunto menciona o livro e confirmacao de recepcao.
            ->subject('Receção confirmada - ' . $this->livro->nome)
            // Saudacao personalizada com nome do utilizador.
            ->greeting('Olá, ' . ($notifiable->name ?? 'utilizador') . '!')
            // Contexto: livro foi devolvido e confirmado.
            ->line('A receção da devolução do seu livro foi confirmada por um administrador.')
            // Nome do livro.
            ->line('Livro: ' . $this->livro->nome)
            // ISBN para identificacao clara.
            ->line('ISBN: ' . ($this->livro->isbn ?: '-'))
            // Admin responsavel pela confirmacao.
            ->line('Admin que confirmou: ' . $this->adminConfirmador->name)
            // Data de encerramento da requisicao.
            ->line('Data de encerramento: ' . ($this->requisicao->data_recepcao_real?->format('d/m/Y H:i') ?? '-'))
            // Metricas: quantos dias ficou com o livro. Pluralizacao: 'dia' vs 'dias'.
            ->line('Dias decorridos: ' . ((int) ($this->requisicao->dias_decorridos ?? 0)) . ' ' . (((int) ($this->requisicao->dias_decorridos ?? 0)) === 1 ? 'dia' : 'dias'))
            // Botao chamada a acao - link para ver detalhes da requisicao encerrada.
            ->action('Ver detalhes da requisição', $urlLivro)
            // Assinatura final do email.
            ->line('Obrigado por usar a Biblioteca Digital.');
    }

    /**
     * Gera conteudo para notificacao armazenada no banco de dados.
     * Esta notificacao aparece como sino/bell no painel do utilizador.
     * @return array<string, mixed> Array com dados da notificacao.
     */
    public function toArray(object $notifiable): array
    {
        // Payload persistido no canal database para o centro de notificacoes da app.
        return [
            // Titulo breve da notificacao.
            'title' => 'Receção confirmada',
            // Mensagem descritiva mencionando livro e admin confirmador.
            'message' => 'A devolução de "' . $this->livro->nome . '" foi confirmada por ' . $this->adminConfirmador->name . '.',
            // Nome do livro para referencia.
            'livro_nome' => $this->livro->nome,
            // URL direto para visualizar livro (detalhe da requisicao encerrada).
            'livro_url' => route('livros.show', $this->livro),
            // Numero de leitor do cidadao.
            'cidadao_numero_leitor' => $this->requisicao->cidadao_numero_leitor,
            // Nome do admin que confirmou a recepcao.
            'admin_confirmador_nome' => $this->adminConfirmador->name,
            // Timestamp ISO quando notificacao foi criada.
            'created_at' => now()->toIso8601String(),
        ];



