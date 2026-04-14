<?php
namespace App\Notifications;

use App\Models\Livro;
use App\Models\Requisicao;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

// Notificacao enviada quando uma nova requisicao de livro e criada no sistema.
// Confirma ao requisitante e notifica admins sobre a nova requisicao.
class RequisicaoCriadaNotification extends Notification
{
    // Habilita fila (async) para notificacoes em background.
    use Queueable;

    // Construtor que recebe os dados principais da requisicao para montar email e notificacao interna.
    public function __construct(
        // Requisicao recém-criada.
        protected Requisicao $requisicao,
        // Utilizador que criou a requisicao (cidadao ou admin).
        protected User $requisitante,
        // Livro que foi requisitado.
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
        // Canais configuravel por instancia (ex.: apenas database em cenarios especificos).
        // Padrao: email e database notification.
        return $this->channels;
    }

    // Gera corpo da mensagem de email para envio.
    public function toMail(object $notifiable): MailMessage
    {
        // Ajusta o rotulo do perfil para exibir no corpo do email.
        // Admin exibe 'Admin', caso contrario exibe 'Cidadao'.
        $papel = $this->requisitante->role === 'admin' ? 'Admin' : 'Cidadão';
        // URL para acessar detalhe do livro.
        $urlLivro = route('livros.show', $this->livro);
        // URL publica da capa se existir, null caso contrario.
        $capaUrl = $this->livro->imagem_capa ? asset($this->livro->imagem_capa) : null;

        // Mensagem principal com dados da requisicao recem-criada.
        $mail = (new MailMessage)
            // Assunto menciona o livro e confirmacao de requisicao.
            ->subject('Confirmação de requisição - ' . $this->livro->nome)
            // Saudacao personalizada com nome do utilizador.
            ->greeting('Olá, ' . ($notifiable->name ?? 'utilizador') . '!')
            // Contexto: nova requisicao criada.
            ->line('Foi registada uma nova requisição de livro.')
            // Numero sequencial da requisicao (ex: R000001).
            ->line('N.º da requisição: ' . ($this->requisicao->numero_requisicao ?: '-'))
            // Nome do livro.
            ->line('Livro: ' . $this->livro->nome)
            // ISBN para identificacao clara.
            ->line('ISBN: ' . ($this->livro->isbn ?: '-'))
            // Quem criou a requisicao (nome e papel: Admin ou Cidadao).
            ->line('Requisitante: ' . $this->requisitante->name . ' (' . $papel . ')')
            // Data e hora da criacao da requisicao.
            ->line('Data da requisição: ' . ($this->requisicao->created_at?->format('d/m/Y H:i') ?? '-'))
            // Data prevista de devolucao (padrao: 5 dias).
            ->line('Data prevista de fim: ' . ($this->requisicao->data_fim_prevista?->format('d/m/Y H:i') ?? '-'))
            // Numero de leitor do cidadao (decodificado no snapshot da requisicao).
            ->line('N.º de leitor: ' . ($this->requisicao->cidadao_numero_leitor ?: '-'))
            // Botao chamada a acao - link para ver detalhe da requisicao.
            ->action('Ver detalhes da requisição', $urlLivro);

        if ($capaUrl) {
            // Inclui o link publico da capa e tenta anexar o ficheiro local quando existir.
            $mail->line('Capa do livro: ' . $capaUrl);

            // Extrai caminho relativo ao storage/app/public/
            $relative = ltrim((string) Str::after((string) $this->livro->imagem_capa, 'storage/'), '/');
            // Monta caminho absoluto no sistema de ficheiros.
            $localPath = storage_path('app/public/' . $relative);

            if (is_file($localPath)) {
                // Define nome de anexo legivel e preserva a extensao original da imagem.
                // Fallback para 'jpg' se extensao nao puder ser extraida.
                $extension = pathinfo($localPath, PATHINFO_EXTENSION) ?: 'jpg';
                // Anexa ficheiro ao email com nome formatado (capa-nome-do-livro.ext).
                $mail->attach($localPath, [
                    'as' => 'capa-' . Str::slug($this->livro->nome) . '.' . $extension,
                ]);
            }
        }

        // Assinatura final do email.
        return $mail->line('Obrigado por usar a Biblioteca Digital.');
    }

    /**
     * Gera conteudo para notificacao armazenada no banco de dados.
     * Esta notificacao aparece como sino/bell no painel do utilizador.
     * @return array<string, mixed> Array com dados da notificacao.
     */
    public function toArray(object $notifiable): array
    {
        // Normaliza o papel para texto simples no payload da notificacao em base de dados.
        // Admin exibe 'admin', caso contrario exibe 'cidadão'.
        $papel = $this->requisitante->role === 'admin' ? 'admin' : 'cidadão';

        // Dados usados no sino de notificacoes e em listagens internas da aplicacao.
        return [
            // Titulo breve da notificacao.
            'title' => 'Confirmação de requisição',
            // Mensagem descritiva mencionando livro, requisitante e seu papel.
            'message' => 'Nova requisição de "' . $this->livro->nome . '" feita por ' . $this->requisitante->name . ' (' . $papel . ').',
            // Numero sequencial da requisicao para referencia rapida.
            'numero_requisicao' => $this->requisicao->numero_requisicao,
            // Nome do livro requisitado.
            'livro_nome' => $this->livro->nome,
            // URL direto para visualizar livro e seus detalhes.
            'livro_url' => route('livros.show', $this->livro),
            // Numero de leitor do cidadao (snapshot da requisicao).
            'cidadao_numero_leitor' => $this->requisicao->cidadao_numero_leitor,
            // Nome do utilizador que criou a requisicao.
            'requisitante_nome' => $this->requisitante->name,
            // Role do requisitante (admin ou utilizador regular).
            'requisitante_role' => $this->requisitante->role,
            // Timestamp ISO quando notificacao foi criada.
            'created_at' => now()->toIso8601String(),
        ];
    }
}



