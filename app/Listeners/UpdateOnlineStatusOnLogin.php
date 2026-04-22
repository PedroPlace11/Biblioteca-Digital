<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class UpdateOnlineStatusOnLogin
{
    /**
     * Cria uma nova instância do listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event - Atualiza o status do utilizador para online ao fazer login.
     */
    public function handle(Login $event): void
    {
        // Atualiza o utilizador para status online e registra o último acesso
        $event->user->update([
            'is_online' => true,
            'last_seen_at' => now(),
        ]);
    }
}
