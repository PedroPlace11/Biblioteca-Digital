<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertaDisponibilidadeLivro extends Model
{
    protected $table = 'alertas_disponibilidade_livros';

    protected $fillable = [
        'user_id',
        'livro_id',
    ];

    // Relacao N:1 com o utilizador que pediu o alerta.
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relacao N:1 com o livro monitorizado.
    public function livro()
    {
        return $this->belongsTo(Livro::class);
    }
}
