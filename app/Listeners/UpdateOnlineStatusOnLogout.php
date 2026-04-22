<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;

class UpdateOnlineStatusOnLogout
{
    /**
     * Cria uma nova instância do listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event - Atualiza o status do utilizador para offline ao fazer logout.
     */
    public function handle(Logout $event): void
    {
        // Atualiza o utilizador para status offline e registra o último acesso
        $event->user->update([
            'is_online' => false,
            'last_seen_at' => now(),
        ]);
    }
}
