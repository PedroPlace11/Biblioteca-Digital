<?php

namespace App\Console\Commands;

use App\Models\Encomenda;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SincronizarPendentesCheckoutFinalizado extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'encomendas:sincronizar-pendentes-checkout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marca checkout_finalizado_em para encomendas pendentes que foram realmente confirmadas no Stripe';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $chaveStripe = trim((string) config('services.stripe.secret'));

        if ($chaveStripe === '') {
            $this->error('Stripe não configurado. Defina STRIPE_SECRET.');

            return self::FAILURE;
        }

        $encomendas = Encomenda::query()
            ->where('estado', 'pendente_pagamento')
            ->whereNull('checkout_finalizado_em')
            ->whereNotNull('stripe_payment_intent_id')
            ->get();

        if ($encomendas->isEmpty()) {
            $this->info('Sem encomendas pendentes para sincronizar.');

            return self::SUCCESS;
        }

        $sincronizadas = 0;

        foreach ($encomendas as $encomenda) {
            if (! $encomenda instanceof Encomenda) {
                continue;
            }

            $paymentIntentId = (string) ($encomenda->stripe_payment_intent_id ?? '');

            if ($paymentIntentId === '') {
                continue;
            }

            $response = Http::withBasicAuth($chaveStripe, '')
                ->timeout(15)
                ->retry(2, 500)
                ->get("https://api.stripe.com/v1/payment_intents/{$paymentIntentId}");

            if (! $response->successful()) {
                Log::warning('Falha ao sincronizar checkout finalizado para encomenda pendente', [
                    'encomenda_id' => $encomenda->id,
                    'payment_intent_id' => $paymentIntentId,
                    'status' => $response->status(),
                ]);

                continue;
            }

            $status = (string) data_get($response->json(), 'status', '');

            // Estes estados indicam que o utilizador confirmou o pagamento no checkout.
            if (in_array($status, ['requires_action', 'processing', 'succeeded', 'requires_capture'], true)) {
                $encomenda->update([
                    'checkout_finalizado_em' => now(),
                ]);

                $sincronizadas++;
            }
        }

        $this->info('Encomendas sincronizadas com checkout finalizado: ' . $sincronizadas);

        return self::SUCCESS;
    }
}
