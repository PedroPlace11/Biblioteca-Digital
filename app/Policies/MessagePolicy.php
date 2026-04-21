<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    /**
     * Editar mensagem - apenas o autor
     */
    public function update(User $user, Message $message): bool
    {
        return $user->id === $message->user_id;
    }

    /**
     * Eliminar mensagem - autor ou admin
     */
    public function delete(User $user, Message $message): bool
    {
        return $user->id === $message->user_id || $user->isAdmin();
    }
}
