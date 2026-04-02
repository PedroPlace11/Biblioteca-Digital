<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

class CorrigirLinksNotificacoesReview extends Command
{
    protected $signature = 'corrigir:links-notificacoes-review';
    protected $description = 'Corrige o link das notificações de review para admin para apontar para admin.reviews.show';

    public function handle()
    {
        $count = 0;
        DatabaseNotification::where('type', 'App\\Notifications\\ReviewSubmetidoNotification')->get()->each(function($n) use (&$count) {
            $data = is_array($n->data) ? $n->data : json_decode($n->data, true);
            if (isset($data['review_id'])) {
                $review = \App\Models\Review::find($data['review_id']);

                if ($review) {
                    $data['review_url'] = route('admin.reviews.show', $review, false);
                    $data['livro_url'] = route('admin.reviews.show', $review, false);
                    $n->data = $data;
                    $n->save();
                    $count++;
                }
            }
        });
        $this->info("Corrigidas {$count} notificações de review para admin.");
        return 0;
    }
}
