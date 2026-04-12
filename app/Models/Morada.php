<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Morada extends Model
{
    // Habilita factory para testes e seeders.
    use HasFactory;

    // Define campos que podem ser atribuidos em massa (mass assignment).
    protected $fillable = [
        // Referencia do utilizador proprietario desta morada.
        'user_id',
        // Titulo descritivo da morada (ex: 'Casa', 'Trabalho', 'Outro').
        'titulo',
        // Nome completo da pessoa destinataria da encomenda.
        'nome_destinatario',
        // Numero de telefone/telemovel para contacto na entrega.
        'telemovel_destinatario',
        // Primeira linha da morada (rua, numero).
        'morada_linha_1',
        // Segunda linha da morada (complemento, andar, etc).
        'morada_linha_2',
        // Codigo postal (formato PT: XXXX-XXX).
        'codigo_postal',
        // Cidade / localidade.
        'cidade',
        // Pais (para encomendas internacionais).
        'pais',
    ];

    // Relacao N:1 com o utilizador proprietario desta morada.
    public function user(): BelongsTo
    {
        // Cada morada pertence a um unico utilizador.
        return $this->belongsTo(User::class);
    }
}
