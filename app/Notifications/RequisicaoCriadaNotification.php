<?php
namespace App\Notifications;

use App\Models\Livro;
use App\Models\Requisicao;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

// Notificação enviada quando uma nova requisição de livro é criada no sistema.
class RequisicaoCriadaNotification extends Notification
{
    use Queueable;

    // Recebe os dados principais da requisição para montar email e notificação interna.
    public function __construct(
        protected Requisicao $requisicao,
        protected User $requisitante,
        protected Livro $livro,
        protected array $channels = ['mail', 'database']
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Canais configuráveis por instância (ex.: apenas database em cenários específicos).
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Ajusta o rótulo do perfil para exibir no corpo do email.
        $papel = $this->requisitante->role === 'admin' ? 'Admin' : 'Cidadão';
        $urlLivro = route('livros.show', $this->livro);
        $capaUrl = $this->livro->imagem_capa ? asset($this->livro->imagem_capa) : null;

        // Mensagem principal com dados da requisição recém-criada.
        $mail = (new MailMessage)
            ->subject('Confirmação de requisição - ' . $this->livro->nome)
            ->greeting('Olá, ' . ($notifiable->name ?? 'utilizador') . '!')
            ->line('Foi registada uma nova requisição de livro.')
            ->line('N.º da requisição: ' . ($this->requisicao->numero_requisicao ?: '-'))
            ->line('Livro: ' . $this->livro->nome)
            ->line('ISBN: ' . ($this->livro->isbn ?: '-'))
            ->line('Requisitante: ' . $this->requisitante->name . ' (' . $papel . ')')
            ->line('Data da requisição: ' . ($this->requisicao->created_at?->format('d/m/Y H:i') ?? '-'))
            ->line('Data prevista de fim: ' . ($this->requisicao->data_fim_prevista?->format('d/m/Y H:i') ?? '-'))
            ->line('N.º de leitor: ' . ($this->requisicao->cidadao_numero_leitor ?: '-'))
            ->action('Ver detalhes da requisição', $urlLivro);

        if ($capaUrl) {
            // Inclui o link público da capa e tenta anexar o ficheiro local quando existir.
            $mail->line('Capa do livro: ' . $capaUrl);

            $relative = ltrim((string) Str::after((string) $this->livro->imagem_capa, 'storage/'), '/');
            $localPath = storage_path('app/public/' . $relative);

            if (is_file($localPath)) {
                // Define nome de anexo legível e preserva a extensão original da imagem.
                $extension = pathinfo($localPath, PATHINFO_EXTENSION) ?: 'jpg';
                $mail->attach($localPath, [
                    'as' => 'capa-' . Str::slug($this->livro->nome) . '.' . $extension,
                ]);
            }
        }

        return $mail->line('Obrigado por usar a Biblioteca Digital.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        // Normaliza o papel para texto simples no payload da notificação em base de dados.
        $papel = $this->requisitante->role === 'admin' ? 'admin' : 'cidadão';

        // Dados usados no sino de notificações e em listagens internas da aplicação.
        return [
            'title' => 'Confirmação de requisição',
            'message' => 'Nova requisição de "' . $this->livro->nome . '" feita por ' . $this->requisitante->name . ' (' . $papel . ').',
            'numero_requisicao' => $this->requisicao->numero_requisicao,
            'livro_nome' => $this->livro->nome,
            'livro_url' => route('livros.show', $this->livro),
            'cidadao_numero_leitor' => $this->requisicao->cidadao_numero_leitor,
            'requisitante_nome' => $this->requisitante->name,
            'requisitante_role' => $this->requisitante->role,
            'created_at' => now()->toIso8601String(),
        ];
    }
}



