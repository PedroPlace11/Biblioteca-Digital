<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EncomendaItem extends Model
{
    // Habilita factory para testes e seeders.
    use HasFactory;
    // Define nome customizado da tabela no banco de dados.
    protected $table = 'encomenda_itens';

    // Define campos que podem ser atribuidos em massa (mass assignment).
    protected $fillable = [
        'encomenda_id',
        'livro_id',
        // Nome do livro snapshot (evita quebra se livro for deletado).
        'livro_nome',
        // ISBN do livro snapshot.
        'livro_isbn',
        // Quantidade de copias deste item.
        'quantidade',
        // Preco unitario do livro no momento da compra.
        'preco_unitario',
        // Subtotal (quantidade * preco_unitario).
        'subtotal',
    ];

    // Define conversoes de tipos para atributos.
    protected function casts(): array
    {
        return [
            // Conversao de precos para decimal com 2 casas decimais.
            'preco_unitario' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    // Relacao N:1 com a encomenda.
    public function encomenda()
    {
        // Cada item pertence a uma unica encomenda.
        return $this->belongsTo(Encomenda::class);
    }

    // Relacao N:1 com o livro.
    public function livro()
    {
        // Cada item refere-se a um livro no catalogo (pode ser null se livro foi deletado).
        return $this->belongsTo(Livro::class);
    }
}
