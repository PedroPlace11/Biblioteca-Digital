<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Livro;
use App\Models\Review;
use App\Notifications\ReviewSubmetidoNotification;
use App\Notifications\ReviewEstadoAtualizadoNotification;

class TestReviewNotifications extends Command
{
    protected $signature = 'test:review-notifications';
    protected $description = 'Testa o envio de notificações de review para admin e cidadão';

    public function handle()
    {
        $admin = User::where('role', 'admin')->first();
        $cidadao = User::where('role', 'cidadao')->first();
        $livro = Livro::first();

        if (!$admin || !$cidadao || !$livro) {
            $this->error('Admin, cidadão ou livro não encontrados.');
            return 1;
        }

        $review = Review::create([
            'user_id' => $cidadao->id,
            'livro_id' => $livro->id,
            'conteudo' => 'Teste de review via comando',
            'estado' => 'suspenso',
            'rating' => 4,
        ]);

        $admin->notify(new ReviewSubmetidoNotification($review));
        $this->info('Notificação enviada para admin.');

        $review->estado = 'recusado';
        $review->justificacao = 'Não cumpre os requisitos';
        $review->save();
        $cidadao->notify(new ReviewEstadoAtualizadoNotification($review));
        $this->info('Notificação enviada para cidadão.');

        return 0;
    }
}
