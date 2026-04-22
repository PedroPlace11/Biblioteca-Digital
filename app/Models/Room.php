<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $table = 'rooms';

    protected $fillable = [
        'name',
        'description',
        'avatar',
        'creator_id',
        'is_archived',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Utilizador que criou a sala
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Utilizadores na sala
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'room_users')
            ->withPivot('joined_at', 'role', 'notification_mode')
            ->withTimestamps();
    }

    /**
     * Mensagens na sala
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Convites pendentes para a sala
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(RoomInvitation::class);
    }

    /**
     * Pedidos de entrada para a sala
     */
    public function joinRequests(): HasMany
    {
        return $this->hasMany(RoomJoinRequest::class);
    }

    /**
     * Verifica se um utilizador é membro da sala
     */
    public function hasMember($userId): bool
    {
        return $this->users()->where('user_id', $userId)->exists();
    }

    /**
     * Adiciona um utilizador à sala
     */
    public function addMember($userId, string $role = 'member')
    {
        if (!$this->hasMember($userId)) {
            $this->users()->attach($userId, [
                'joined_at' => now(),
                'role' => $role,
                'notification_mode' => null,
            ]);
        }
    }

    /**
     * Define papel de membro na sala (admin/member)
     */
    public function setMemberRole(int $userId, string $role): void
    {
        $this->users()->updateExistingPivot($userId, ['role' => $role]);
    }

    /**
     * Verifica se utilizador é admin da sala
     */
    public function isRoomAdmin(int $userId): bool
    {
        if ($this->creator_id === $userId) {
            return true;
        }

        return $this->users()
            ->where('users.id', $userId)
            ->wherePivot('role', 'admin')
            ->exists();
    }

    /**
     * Remove um utilizador da sala
     */
    public function removeMember($userId)
    {
        $this->users()->detach($userId);
    }

    /**
     * Obtém a última mensagem da sala
     */
    public function getLastMessageAttribute()
    {
        return $this->messages()->latest('created_at')->first();
    }
}
