<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carrinho extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lembrete_abandono_enviado_em',
    ];

    protected function casts(): array
    {
        return [
            'lembrete_abandono_enviado_em' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function itens()
    {
        return $this->hasMany(CarrinhoItem::class);
    }
}
