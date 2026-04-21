<?php

namespace App\Policies;

use App\Models\DirectMessage;
use App\Models\User;

class DirectMessagePolicy
{
    /**
     * Editar mensagem - apenas o autor
     */
    public function update(User $user, DirectMessage $message): bool
    {
        return $user->id === $message->sender_id;
    }

    /**
     * Eliminar mensagem - apenas o autor
     */
    public function delete(User $user, DirectMessage $message): bool
    {
        return $user->id === $message->sender_id;
    }
}
