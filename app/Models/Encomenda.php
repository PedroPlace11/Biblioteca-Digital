<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Encomenda extends Model
{
    // Habilita factory para testes e seeders.
    use HasFactory;

    // Estados possiveis da encomenda (constantes para evitar strings magicas).
    // Estado quando ordem esta aguardando pagamento.
    public const ESTADO_PENDENTE = 'pendente_pagamento';
    // Estado quando pagamento foi confirmado com sucesso.
    public const ESTADO_PAGA = 'paga';

    // Define campos que podem ser atribuidos em massa (mass assignment).
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
        'codigo_promocional',
        'desconto_percentual',
        'valor_desconto',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'transportadora',
        'numero_rastreio',
        'pago_em',
        'checkout_finalizado_em',
        'fatura_com_nif',
        'fatura_nif',
        'fatura_nome',
    ];

    // Define conversoes de tipos para atributos.
    protected function casts(): array
    {
        return [
            // Valores monetarios com 2 casas decimais.
            'total' => 'decimal:2',
            // Percentual de desconto aplicado.
            'desconto_percentual' => 'integer',
            // Valor em euros do desconto.
            'valor_desconto' => 'decimal:2',
            // Timestamps de eventos importantes.
            'pago_em' => 'datetime',
            'checkout_finalizado_em' => 'datetime',
            // Flag booleano se fatura foi solicitada com NIF.
            'fatura_com_nif' => 'boolean',
        ];
    }

    // Accessor: descriptografa NIF quando lido, com fallback para valor nao criptografado.
    public function getFaturaNifAttribute($value): ?string
    {
        // Se NIF nao foi informado, retorna null.
        if (is_null($value) || $value === '') {
            return null;
        }

        // Tenta descriptografar valor armazenado (pode estar criptografado ou nao).
        try {
            return Crypt::decryptString((string) $value);
        } catch (\Throwable $e) {
            // Se falhar descriptografia, retorna como esta (legacy data).
            return (string) $value;
        }
    }

    // Mutator: criptografa NIF quando atribuido para armazenamento seguro.
    public function setFaturaNifAttribute($value): void
    {
        // Se NIF nao foi informado, armazena null.
        if (is_null($value) || $value === '') {
            $this->attributes['fatura_nif'] = null;

            return;
        }

        // Remove caracteres nao numericos do NIF (apenas digitos).
        $valor = preg_replace('/\D+/', '', (string) $value);

        // Tenta detectar se ja e estava criptografado.
        try {
            Crypt::decryptString($valor);
            // Se consegue descriptografar, ja estava criptografado - armazena como esta.
            $this->attributes['fatura_nif'] = $valor;
        } catch (\Throwable $e) {
            // Se nao consegue descriptografar, criptografa antes de armazenar.
            $this->attributes['fatura_nif'] = Crypt::encryptString($valor);
        }
    }

    // Relacao N:1 com o utilizador que criou a encomenda.
    public function user()
    {
        // Cada encomenda pertence a um unico utilizador.
        return $this->belongsTo(User::class);
    }

    // Relacao 1:N: uma encomenda contem muitos itens (livros).
    public function itens()
    {
        // Cada encomenda pode ter multiplos itens de compra.
        return $this->hasMany(EncomendaItem::class);
    }

    // Accessor: calcula total de unidades (soma quantidades de todos itens).
    public function getTotalItensAttribute(): int
    {
        // Soma as quantidades de cada item para obter total de livros.
        return (int) $this->itens->sum('quantidade');
    }
}
