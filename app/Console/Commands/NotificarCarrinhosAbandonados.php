<?php

namespace App\Console\Commands;

use App\Models\Carrinho;
use App\Notifications\CarrinhoAbandonadoNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NotificarCarrinhosAbandonados extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'carrinho:notificar-abandonados';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia notificações para cidadãos com carrinho abandonado há mais de 30 minutos';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limite = now()->subMinutes(30);

        $carrinhos = Carrinho::with(['user', 'itens.livro'])
            ->whereHas('itens')
            ->where('updated_at', '<=', $limite)
            ->where(function ($query) {
                $query->whereNull('lembrete_abandono_enviado_em')
                    ->orWhereColumn('lembrete_abandono_enviado_em', '<', 'updated_at');
            })
            ->get();

        if ($carrinhos->isEmpty()) {
            $this->info('Sem carrinhos abandonados para notificar.');
            return self::SUCCESS;
        }

        foreach ($carrinhos as $carrinho) {
            if (!$carrinho instanceof Carrinho) {
                continue;
            }

            $user = $carrinho->user;

            if (!$user || $user->role !== 'cidadao') {
                continue;
            }

            $user->notify(new CarrinhoAbandonadoNotification($carrinho, ['database']));

            try {
                $user->notify(new CarrinhoAbandonadoNotification($carrinho, ['mail']));
            } catch (\Throwable $e) {
                Log::warning('Falha no envio de email de carrinho abandonado.', [
                    'user_id' => $user->id,
                    'carrinho_id' => $carrinho->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $carrinho->lembrete_abandono_enviado_em = now();
            $carrinho->save();
        }

        $this->info('Notificações de carrinho abandonado processadas: ' . $carrinhos->count());

        return self::SUCCESS;
    }
}
