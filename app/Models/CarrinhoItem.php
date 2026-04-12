<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarrinhoItem extends Model
{
    // Habilita factory para testes e seeders.
    use HasFactory;

    // Define nome customizado da tabela no banco de dados (plural e underscore).
    protected $table = 'carrinho_itens';

    // Define campos que podem ser atribuidos em massa (mass assignment).
    protected $fillable = [
        'carrinho_id',
        'livro_id',
        // Quantidade de copias do livro neste item do carrinho.
        'quantidade',
        // Preco unitario do livro no momento da adicao ao carrinho.
        'preco_unitario',
    ];

    // Define conversoes de tipos para atributos.
    protected function casts(): array
    {
        return [
            // Converte preco para decimal com 2 casas decimais.
            'preco_unitario' => 'decimal:2',
        ];
    }

    // Relacao N:1 com o carrinho.
    public function carrinho()
    {
        // Cada item pertence a um unico carrinho.
        return $this->belongsTo(Carrinho::class);
    }

    // Relacao N:1 com o livro.
    public function livro()
    {
        // Cada item refere-se a um unico livro no catalogo.
        return $this->belongsTo(Livro::class);
    }

    // Calcula e retorna o subtotal para este item (quantidade * preco unitario).
    public function getSubtotalAttribute(): float
    {
        // Multiplica quantidade pelo preco unitario para obter valor total da linha.
        return (float) $this->preco_unitario * (int) $this->quantidade;
    }
}
