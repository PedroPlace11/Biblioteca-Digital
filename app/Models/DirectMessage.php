<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectMessage extends Model
{
    protected $table = 'direct_messages';

    protected $fillable = [
        'sender_id',
        'recipient_id',
        'content',
        'type', // 'text', 'file', 'image'
        'file_path',
        'file_name',
        'mime_type',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Utilizador que enviou a mensagem
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Utilizador que recebeu a mensagem
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Marca como lida
     */
    public function markAsRead()
    {
        if (is_null($this->read_at)) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Verifica se foi lida
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
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
