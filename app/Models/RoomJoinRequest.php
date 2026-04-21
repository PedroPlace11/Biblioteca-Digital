<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomJoinRequest extends Model
{
    protected $fillable = [
        'room_id',
        'user_id',
        'handled_by_id',
        'status',
        'handled_at',
    ];

    protected $casts = [
        'handled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function accept(int $adminId): void
    {
        $this->update([
            'status' => 'accepted',
            'handled_by_id' => $adminId,
            'handled_at' => now(),
        ]);

        $this->room->addMember($this->user_id);
    }

    public function decline(int $adminId): void
    {
        $this->update([
            'status' => 'declined',
            'handled_by_id' => $adminId,
            'handled_at' => now(),
        ]);
    }
}
