<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarrinhoItem extends Model
{
    use HasFactory;

    protected $table = 'carrinho_itens';

    protected $fillable = [
        'carrinho_id',
        'livro_id',
        'quantidade',
        'preco_unitario',
    ];

    protected function casts(): array
    {
        return [
            'preco_unitario' => 'decimal:2',
        ];
    }

    public function carrinho()
    {
        return $this->belongsTo(Carrinho::class);
    }

    public function livro()
    {
        return $this->belongsTo(Livro::class);
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->preco_unitario * (int) $this->quantidade;
    }
}
