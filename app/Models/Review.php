<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Review extends Model
{
    // Habilita factory para testes e seeders.
    use HasFactory;

    // Define campos que podem ser atribuidos em massa (mass assignment).
    protected $fillable = [
        // Referencia do utilizador autor da review.
        'user_id',
        // Referencia do livro sendo revisado.
        'livro_id',
        // Slug unico para URL amigavel da review.
        'slug',
        // Texto da review (parecer do utilizador sobre o livro).
        'conteudo',
        // Estado da review: 'suspenso' (aguarda moderacao), 'publicado' (aprovado), 'rejeitado'.
        'estado',
        // Motivo de rejeicao (preenchido quando estado='rejeitado').
        'justificacao',
        // Classificacao numerica (ex: 1-5 estrelas).
        'rating',
    ];

    // Define o campo tipo de chave para model binding em rotas (slug ao invés de id).
    public function getRouteKeyName(): string
    {
        // Usa slug para URLs amigaveis (ex: /reviews/review-do-livro-x).
        return 'slug';
    }

    // Gera slug automaticamente quando modelo e salvo.
    protected static function booted(): void
    {
        static::saving(function (self $review): void {
            // Sai cedo se slug ja existe e livro nao foi alterado.
            if (!empty($review->slug) && !$review->isDirty('livro_id')) {
                return;
            }

            // Extrai nome do livro do banco de dados para gerar slug baseado no livro.
            $nomeLivro = Livro::query()->whereKey($review->livro_id)->value('nome') ?? 'review';
            // Gera novo slug unico baseado no nome do livro.
            $review->slug = static::generateUniqueSlug($nomeLivro, $review->id);
        });
    }

    // Resolve model binding por multiplos criterios (slug ou ID numerico).
    public function resolveRouteBinding($value, $field = null)
    {
        // Tenta encontrar review por: 1) campo especificado (slug), 2) ID numerico.
        $review = $this->newQuery()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->when(is_numeric($value), fn ($q) => $q->orWhere('id', (int) $value))
            ->first();

        // Se nao encontrar review, retorna erro 404.
        if (!$review) {
            abort(404);
        }

        return $review;
    }

    // Gera slug unico com sufixo numerico em caso de colisao.
    protected static function generateUniqueSlug(string $baseTexto, ?int $ignoreId = null): string
    {
        // Converte texto em slug (minusculas, hifens em lugar de espacos).
        $base = Str::slug($baseTexto);
        // Fallback se slug ficar vazio apos conversao.
        $base = $base !== '' ? $base : 'review';

        // Comeca com slug base sem sufixo.
        $slug = $base;
        // Contador para sufixo em caso de colisao.
        $suffix = 2;

        // Loop: enquanto slug existir no banco, incrementa sufixo e tenta novamente.
        while (static::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            // Adiciona sufixo ao slug (review-2, review-3, etc).
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    // Relacao N:1 com o utilizador autor da review.
    public function user(): BelongsTo
    {
        // Cada review pertence a um unico utilizador.
        return $this->belongsTo(User::class);
    }

    // Relacao N:1 com o livro sendo revisado.
    public function livro(): BelongsTo
    {
        // Cada review refere-se a um unico livro no catalogo.
        return $this->belongsTo(Livro::class);
    }
}
