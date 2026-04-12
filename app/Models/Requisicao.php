<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// Modelo que representa uma requisicao de livro feita por um utilizador.
// O soft delete e usado para manter o historico de requisicoes encerradas.
class Requisicao extends Model
{
    // Usa soft deletes para manter escopo historico (deleted_at logico).
    use SoftDeletes;

    // Define nome customizado da tabela no banco de dados.
    protected $table = 'requisicoes';

    // Define campos que podem ser atribuidos em massa (mass assignment).
    protected $fillable = [
        // Referencia do utilizador que fez a requisicao.
        'user_id',
        // Referencia do livro requisitado.
        'livro_id',
        // Numero sequencial unico da requisicao (auto-gerado).
        'numero_requisicao_seq',
        // Nome do cidadao snapshot (para historico em caso de deletar user).
        'cidadao_nome',
        // Email do cidadao snapshot.
        'cidadao_email',
        // Numero de leitor no sistema de biblioteca.
        'cidadao_numero_leitor',
        // Path de armazenamento da foto do cidadao.
        'cidadao_foto_path',
        // Data prevista para devolucao do livro.
        'data_fim_prevista',
        // Timestamp quando lembrete de devolucao foi enviado.
        'lembrete_devolucao_enviado_em',
        // Timestamp quando devolucao foi solicitada.
        'devolucao_solicitada_em',
        // Timestamp real quando livro foi recebido/devolvido.
        'data_recepcao_real',
        // Numero de dias decorridos entre requisicao e devolucao.
        'dias_decorridos',
        // ID do admin que confirmou a recepcao do livro.
        'confirmado_por_admin_id',
    ];

    // Define atributos calculados inclusos na serializacao (não existem na tabela).
    protected $appends = [
        // Foto URL extraida via accessor getCidadaoFotoUrlAttribute().
        'cidadao_foto_url',
        // Numero formatado via accessor getNumeroRequisicaoAttribute().
        'numero_requisicao',
    ];

    // Define conversoes de tipos para atributos.
    protected $casts = [
        // Numero sequencial como inteiro.
        'numero_requisicao_seq' => 'integer',
        // Timestamps nas datas como objetos Carbon.
        'data_fim_prevista' => 'datetime',
        'lembrete_devolucao_enviado_em' => 'datetime',
        'devolucao_solicitada_em' => 'datetime',
        'data_recepcao_real' => 'datetime',
    ];

    // Hook executado antes de criar novo registro na tabela.
    protected static function booted(): void
    {
        static::creating(function (Requisicao $requisicao) {
            // Se numero sequencial nao foi definido, gera o proximo automaticamente.
            if (empty($requisicao->numero_requisicao_seq)) {
                $requisicao->numero_requisicao_seq = static::proximoNumeroRequisicaoSequencial();
            }
        });
    }

    // Gera proximo numero sequencial de requisicao com garantia de uniqueness via lock.
    protected static function proximoNumeroRequisicaoSequencial(): int
    {
        // Usa transacao e lock para evitar race conditions entre multiplas requisicoes simultaneas.
        return DB::transaction(function () {
            // lockForUpdate() bloqueia linhas ate fim da transacao - garante numeros sequenciais correctos.
            return ((int) DB::table('requisicoes')->lockForUpdate()->max('numero_requisicao_seq')) + 1;
        }, 3);
    }

    // Accessor: formata numero sequencial com padding de zeros (ex: R000001, R000002).
    public function getNumeroRequisicaoAttribute(): ?string
    {
        // Se numero sequencial nao existe, retorna null.
        if (is_null($this->numero_requisicao_seq)) {
            return null;
        }

        // Formata como 'R' + 6 digitos com padding de zeros.
        return sprintf('R%06d', (int) $this->numero_requisicao_seq);
    }

    // Relacao N:1 com o utilizador que fez a requisicao.
    public function user()
    {
        // Cada requisicao pertence a um unico utilizador.
        return $this->belongsTo(User::class);
    }

    // Relacao N:1 com o livro requisitado.
    public function livro()
    {
        // Cada requisicao refere-se a um unico livro no catalogo.
        return $this->belongsTo(Livro::class);
    }

    // Accessor: obtem URL da foto do cidadao com fallbacks em cascata.
    public function getCidadaoFotoUrlAttribute(): string
    {
        // Prioridade 1: Foto de perfil do utilizador atual (se ainda existe).
        if ($this->user?->profile_photo_url) {
            return $this->user->profile_photo_url;
        }

        // Prioridade 2: Foto armazenada no storage (snapshot do momento da requisicao).
        if (!empty($this->cidadao_foto_path)) {
            return Storage::url($this->cidadao_foto_path);
        }

        // Prioridade 3: Avatar gerado SVG com iniciais do nome.
        // Extrai ate 2 primeiras letras do nome do cidadao.
        $iniciais = trim((string) collect(explode(' ', (string) ($this->cidadao_nome ?: 'Cidadao')))
            ->filter()
            ->take(2)
            ->map(fn ($parte) => mb_substr($parte, 0, 1))
            ->implode(''));

        // Gera SVG simples com iniciais em fundo cinzento.
        $svg = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 80 80'><rect width='80' height='80' fill='#E5E7EB'/><text x='50%' y='50%' dominant-baseline='middle' text-anchor='middle' font-family='Arial, sans-serif' font-size='28' fill='#111111'>" . e($iniciais ?: 'C') . "</text></svg>";

        // Retorna SVG como data URI (nao precisa de request ao servidor).
        return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
    }
}



