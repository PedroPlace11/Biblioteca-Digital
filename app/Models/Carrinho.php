<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carrinho extends Model
{
    // Habilita factory para testes e seeders.
    use HasFactory;

    // Define campos que podem ser atribuidos em massa (mass assignment).
    protected $fillable = [
        'user_id',
        // Timestamp de quando ultimo alerta de carrinho abandonado foi enviado.
        'lembrete_abandono_enviado_em',
    ];

    // Define conversoes de tipos para atributos.
    protected function casts(): array
    {
        return [
            // Converte timestamp para instancia Carbon para operacoes de data.
            'lembrete_abandono_enviado_em' => 'datetime',
        ];
    }

    // Relacao N:1 com o utilizador proprietario do carrinho.
    public function user()
    {
        // Cada carrinho pertence a um unico utilizador.
        return $this->belongsTo(User::class);
    }

    // Relacao 1:N com os itens do carrinho.
    public function itens()
    {
        // Um carrinho pode ter muitos itens de compra.
        return $this->hasMany(CarrinhoItem::class);
    }
}
