<?php

namespace App\Console\Commands;

use App\Models\Carrinho;
use App\Models\CarrinhoItem;
use App\Models\Livro;
use App\Models\User;
use App\Notifications\CarrinhoAbandonadoNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestCarrinhoAbandonadoNotification extends Command
{
    protected $signature = 'test:carrinho-abandonado-notification {--user_id= : ID do cidadao para receber a notificacao} {--email= : Email do cidadao para receber a notificacao}';

    protected $description = 'Envia notificacao de teste de carrinho abandonado para aparecer no sino e por email';

    public function handle(): int
    {
        $userId = $this->option('user_id');
        $email = $this->option('email');

        $user = User::query()
            ->when($email, fn ($query) => $query->where('email', (string) $email))
            ->when($userId, fn ($query) => $query->where('id', (int) $userId))
            ->where('role', 'cidadao')
            ->first();

        if (!$user) {
            $this->error('Nenhum cidadao encontrado para envio da notificacao de teste.');
            return self::FAILURE;
        }

        $carrinho = Carrinho::query()->firstOrCreate([
            'user_id' => $user->id,
        ]);

        if (!$carrinho->itens()->exists()) {
            $livro = Livro::query()->first();

            if (!$livro) {
                $this->error('Nao foi encontrado nenhum livro para criar item de teste no carrinho.');
                return self::FAILURE;
            }

            CarrinhoItem::query()->create([
                'carrinho_id' => $carrinho->id,
                'livro_id' => $livro->id,
                'quantidade' => 1,
                'preco_unitario' => $livro->preco,
            ]);
        }

        $carrinho->load('itens.livro');

        $user->notify(new CarrinhoAbandonadoNotification($carrinho, ['database']));

        try {
            $user->notify(new CarrinhoAbandonadoNotification($carrinho, ['mail']));
        } catch (\Throwable $e) {
            Log::warning('Falha no envio de email no comando de teste de carrinho abandonado.', [
                'user_id' => $user->id,
                'carrinho_id' => $carrinho->id,
                'error' => $e->getMessage(),
            ]);
        }

        $carrinho->forceFill([
            'lembrete_abandono_enviado_em' => now(),
        ])->save();

        $this->info('Notificacao de teste enviada para o cidadao: ' . $user->email);

        return self::SUCCESS;
    }
}
