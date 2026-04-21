<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $table = 'messages';

    protected $fillable = [
        'room_id',
        'user_id',
        'content',
        'type', // 'text', 'file', 'image'
        'file_path',
        'file_name',
        'mime_type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Sala à qual a mensagem pertence
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Utilizador que enviou a mensagem
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtém a URL do arquivo (se houver)
     */
    public function getFileUrlAttribute()
    {
        if ($this->file_path) {
            return asset('storage/' . $this->file_path);
        }
        return null;
    }

    /**
     * Verifica se é uma mensagem de tipo arquivo
     */
    public function isFileMessage(): bool
    {
        return $this->type === 'file';
    }

    /**
     * Verifica se é uma mensagem de tipo imagem
     */
    public function isImageMessage(): bool
    {
        return $this->type === 'image';
    }
}
