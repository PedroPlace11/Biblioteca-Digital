<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomInvitation extends Model
{
    protected $table = 'room_invitations';

    protected $fillable = [
        'room_id',
        'invited_user_id',
        'invited_by_id',
        'status', // 'pending', 'accepted', 'declined'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Sala para a qual foi feito o convite
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Utilizador que foi convidado
     */
    public function invitedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_user_id');
    }

    /**
     * Utilizador que fez o convite
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    }

    /**
     * Marca como aceite
     */
    public function accept()
    {
        $this->update(['status' => 'accepted']);
        $this->room->addMember($this->invited_user_id);
    }

    /**
     * Marca como recusado
     */
    public function decline()
    {
        $this->update(['status' => 'declined']);
    }

    /**
     * Verifica se está pendente
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
