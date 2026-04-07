<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Encomenda extends Model
{
    use HasFactory;

    public const ESTADO_PENDENTE = 'pendente_pagamento';
    public const ESTADO_PAGA = 'paga';

    protected $fillable = [
        'user_id',
        'estado',
        'nome_destinatario',
        'telemovel_destinatario',
        'morada_linha_1',
        'morada_linha_2',
        'codigo_postal',
        'cidade',
        'pais',
        'total',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'transportadora',
        'numero_rastreio',
        'pago_em',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'pago_em' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function itens()
    {
        return $this->hasMany(EncomendaItem::class);
    }

    public function getTotalItensAttribute(): int
    {
        return (int) $this->itens->sum('quantidade');
    }
}
