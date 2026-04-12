<?php



use App\Models\Requisicao;
use App\Notifications\LembreteEntregaRequisicaoNotification;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

// Comando simples para validar o agendador com uma citação inspiradora.
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Mostra uma citação inspiradora.');

// Processa lembretes de entrega para requisições cujo prazo termina amanhã.
Artisan::command('requisicoes:enviar-lembretes-entrega', function () {
    $amanhaInicio = now()->addDay()->startOfDay();
    $amanhaFim = now()->addDay()->endOfDay();

    $requisicoes = Requisicao::with(['user:id,name,email,role', 'livro:id,nome,isbn'])
        ->whereNull('deleted_at')
        ->whereNull('lembrete_devolucao_enviado_em')
        ->whereBetween('data_fim_prevista', [$amanhaInicio, $amanhaFim])
        ->whereHas('user', function ($query) {
            $query->where('role', 'cidadao');
        })
        ->get();

    $enviados = 0;

    foreach ($requisicoes as $requisicao) {
        $user = $requisicao->user;

        if (!$user) {
            continue;
        }

        $databaseNotification = new LembreteEntregaRequisicaoNotification($requisicao, ['database']);
        $mailNotification = new LembreteEntregaRequisicaoNotification($requisicao, ['mail']);

        $user->notify($databaseNotification);

        try {
            $user->notify($mailNotification);
        } catch (\Throwable $e) {
            Log::warning('Falha no envio de email de lembrete de entrega.', [
                'requisicao_id' => $requisicao->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        $requisicao->update([
            'lembrete_devolucao_enviado_em' => now(),
        ]);

        $enviados++;
    }

    $this->info("Lembretes processados: {$enviados}");
})->purpose('Envia lembrete de entrega (dia anterior) por email e notificação interna para cidadãos.');

// Executa o comando diariamente às 09:00 para automatizar o envio.
Schedule::command('requisicoes:enviar-lembretes-entrega')->dailyAt('09:00');



