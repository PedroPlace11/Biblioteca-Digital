<?php
namespace App\Notifications;

use App\Models\Livro;
use App\Models\Requisicao;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Notificacao enviada quando um utilizador solicita a devolucao de um livro.
// Alertas admin sobre pedidos de devolucao que requerem confirmacao de recepcao.
class DevolucaoSolicitadaNotification extends Notification
{
    // Habilita fila (async) para notificacoes em background.
    use Queueable;

    // Construtor que recebe o contexto necessario para montar a mensagem de email e payload de base de dados.
    public function __construct(
        // Requisicao associada a este pedido de devolucao.
        protected Requisicao $requisicao,
        // Utilizador que solicitou a devolucao.
        protected User $solicitante,
        // Livro que sera devolvido.
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
        // Permite controlar os canais por instancia (ex.: apenas database em testes).
        // Padrao: email e database notification.
        return $this->channels;
    }

    // Gera corpo da mensagem de email para envio.
    public function toMail(object $notifiable): MailMessage
    {
        // Ajusta o papel para apresentacao amigavel no corpo do email.
        $papel = $this->solicitante->role === 'admin' ? 'Admin' : 'Cidadão';

        // Link direto para a pagina do livro, onde o admin pode confirmar a recepcao.
        $urlLivro = route('livros.show', $this->livro);

        // Monta email com contexto completo da requisicao e do pedido de devolucao.
        return (new MailMessage)
            // Assunto claramente identifica o livro e motivo (devolucao).
            ->subject('Pedido de devolução - ' . $this->livro->nome)
            // Saudacao personalizada com nome do utilisador.
            ->greeting('Olá, ' . ($notifiable->name ?? 'utilizador') . '!')
            // Contexto: notifica sobre pedido de devolucao em estado de confirmacao.
            ->line('Foi solicitado um pedido de devolução que aguarda confirmação de receção.')
            // Informacoes do livro.
            ->line('Livro: ' . $this->livro->nome)
            // ISBN para identificacao clara do volume.
            ->line('ISBN: ' . ($this->livro->isbn ?: '-'))
            // Quem solicitou a devolucao (admin ou cidadao).
            ->line('Solicitante: ' . $this->solicitante->name . ' (' . $papel . ')')
            // Datas importantes da requisicao.
            ->line('Data da requisição: ' . ($this->requisicao->created_at?->format('d/m/Y H:i') ?? '-'))
            // Timestamp do pedido de devolucao.
            ->line('Data do pedido de devolução: ' . ($this->requisicao->devolucao_solicitada_em?->format('d/m/Y H:i') ?? '-'))
            // Numero de leitor para identificacao no sistema.
            ->line('N.º de leitor: ' . ($this->requisicao->cidadao_numero_leitor ?: '-'))
            // Botao chamada a acao - link para confirmar recepcao.
            ->action('Confirmar receção no livro', $urlLivro)
            // Aviso: acao deve ser confirmada por admin autorizado.
            ->line('Este pedido deve ser confirmado por um administrador autorizado.');
    }

    /**
     * Gera conteudo para notificacao armazenada no banco de dados.
     * Esta notificacao aparece como sino/bell no painel do utilizador.
     * @return array<string, mixed> Array com dados da notificacao.
     */
    public function toArray(object $notifiable): array
    {
        // Versao curta do papel para exibicao em notificacoes persistidas no sistema.
        $papel = $this->solicitante->role === 'admin' ? 'admin' : 'cidadão';

        // Payload salvo no canal database e usado no centro de notificacoes da interface.
        return [
            // Titulo breve da notificacao.
            'title' => 'Pedido de devolução',
            // Mensagem descritiva com contexto completo do pedido.
            'message' => 'Pedido de devolução de "' . $this->livro->nome . '" solicitado por ' . $this->solicitante->name . ' (' . $papel . ').',
            // Nome do livro para referencias rapidas.
            'livro_nome' => $this->livro->nome,
            // URL direto para visualizar o livro (onde confirmacao de recepcao e feita).
            'livro_url' => route('livros.show', $this->livro),
            // Numero de leitor do cidadao que requisitou o livro.
            'cidadao_numero_leitor' => $this->requisicao->cidadao_numero_leitor,
            // Nome do solicitante (admin ou cidadao).
            'solicitante_nome' => $this->solicitante->name,
            // Tipo de utilizador (admin ou cidadao) - usado para determinar autoridade na confirmacao.
            'solicitante_role' => $this->solicitante->role,
            // Timestamp ISO quando notificacao foi criada.
            'created_at' => now()->toIso8601String(),
        ];
    }
}



