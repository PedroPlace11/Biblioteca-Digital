<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\CorrigirReviewUrlNotificacoesAntigas::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Processa lembretes de carrinho abandonado em intervalos curtos.
        $schedule->command('carrinho:notificar-abandonados')->everyFiveMinutes();

        // Cancela encomendas pendentes de pagamento apos 7 dias sem liquidacao.
        $schedule->command('encomendas:cancelar-pendentes-multibanco')->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        // Carrega automaticamente os comandos Artisan personalizados da aplicacao.
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
