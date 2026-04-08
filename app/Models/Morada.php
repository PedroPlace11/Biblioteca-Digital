<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Morada extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'titulo',
        'nome_destinatario',
        'telemovel_destinatario',
        'morada_linha_1',
        'morada_linha_2',
        'codigo_postal',
        'cidade',
        'pais',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
