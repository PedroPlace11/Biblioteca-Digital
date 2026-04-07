<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EncomendaItem extends Model
{
    use HasFactory;
    protected $table = 'encomenda_itens';

    protected $fillable = [
        'encomenda_id',
        'livro_id',
        'livro_nome',
        'livro_isbn',
        'quantidade',
        'preco_unitario',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'preco_unitario' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function encomenda()
    {
        return $this->belongsTo(Encomenda::class);
    }

    public function livro()
    {
        return $this->belongsTo(Livro::class);
    }
}
