<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CorrigirReviewUrlNotificacoesAntigas extends Command
{
    protected $signature = 'notificacoes:corrigir-review-url-antigas';
    protected $description = 'Corrige o campo review_url das notificações antigas de ReviewSubmetidoNotification para caminho relativo';

    public function handle()
    {
        $notificacoes = DB::table('notifications')
            ->where('type', 'App\\Notifications\\ReviewSubmetidoNotification')
            ->get();

        $corrigidas = 0;
        foreach ($notificacoes as $n) {
            $data = json_decode($n->data, true);
            if (isset($data['review_url']) && str_starts_with($data['review_url'], 'http')) {
                $parsed = parse_url($data['review_url']);
                $data['review_url'] = $parsed['path'] ?? $data['review_url'];
                DB::table('notifications')->where('id', $n->id)->update(['data' => json_encode($data)]);
                $corrigidas++;
            }
        }
        $this->info("Notificações corrigidas: {$corrigidas}");
        return 0;
    }
}
