<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    /**
     * Ver uma sala - apenas membros podem ver
     */
    public function view(User $user, Room $room): bool
    {
        return $room->hasMember($user->id);
    }

    /**
     * Criar salas - apenas admins
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Editar sala - apenas criador ou admin
     */
    public function update(User $user, Room $room): bool
    {
        return $user->isAdmin() || $user->id === $room->creator_id;
    }

    /**
     * Eliminar sala - apenas criador ou admin
     */
    public function delete(User $user, Room $room): bool
    {
        return $user->isAdmin() || $user->id === $room->creator_id;
    }

    /**
     * Convidar utilizadores - apenas criador ou admin
     */
    public function invite(User $user, Room $room): bool
    {
        return $user->isAdmin() || $user->id === $room->creator_id;
    }
}
