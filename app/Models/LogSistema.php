<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogSistema extends Model
{
    use HasFactory;

    protected $table = 'logs';

    protected $fillable = [
        'ocorrido_em',
        'user_id',
        'user_nome',
        'modulo',
        'objeto_id',
        'alteracao',
        'ip',
        'browser',
        'metodo',
        'url',
        'route_name',
    ];

    protected function casts(): array
    {
        return [
            'ocorrido_em' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
