<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertaDisponibilidadeLivro extends Model
{
    // Define nome customizado da tabela no banco de dados.
    protected $table = 'alertas_disponibilidade_livros';

    // Define campos que podem ser atribuidos em massa (mass assignment).
    protected $fillable = [
        'user_id',
        'livro_id',
    ];

    // Relacao N:1 com o utilizador que pediu o alerta.
    public function user()
    {
        // Cada alerta pertence a um usuario cidadao.
        return $this->belongsTo(User::class);
    }

    // Relacao N:1 com o livro monitorizado.
    public function livro()
    {
        // Cada alerta esta associado a um livro para monitorar disponibilidade.
        return $this->belongsTo(Livro::class);
    }
}
