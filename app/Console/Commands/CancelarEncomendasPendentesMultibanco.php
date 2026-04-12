<?php

namespace App\Console\Commands;

use App\Models\Encomenda;
use Illuminate\Console\Command;

class CancelarEncomendasPendentesMultibanco extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'encomendas:cancelar-pendentes-multibanco';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancela encomendas pendentes de pagamento Multibanco com mais de 7 dias';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limite = now()->subDays(7);

        $query = Encomenda::query()
            ->where('estado', 'pendente_pagamento')
            ->whereNull('pago_em')
            ->whereNotNull('stripe_payment_intent_id')
            ->where('created_at', '<=', $limite);

        $total = $query->count();

        if ($total === 0) {
            $this->info('Sem encomendas pendentes para cancelar.');

            return self::SUCCESS;
        }

        $query->update([
            'estado' => 'cancelada',
        ]);

        $this->info('Encomendas canceladas por expirar prazo de pagamento: ' . $total);

        return self::SUCCESS;
    }
}
