<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'livro_id',
        'slug',
        'conteudo',
        'estado',
        'justificacao',
        'rating',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::saving(function (self $review): void {
            if (!empty($review->slug) && !$review->isDirty('livro_id')) {
                return;
            }

            $nomeLivro = Livro::query()->whereKey($review->livro_id)->value('nome') ?? 'review';
            $review->slug = static::generateUniqueSlug($nomeLivro, $review->id);
        });
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $review = $this->newQuery()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->when(is_numeric($value), fn ($q) => $q->orWhere('id', (int) $value))
            ->first();

        if (!$review) {
            abort(404);
        }

        return $review;
    }

    protected static function generateUniqueSlug(string $baseTexto, ?int $ignoreId = null): string
    {
        $base = Str::slug($baseTexto);
        $base = $base !== '' ? $base : 'review';

        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function livro(): BelongsTo
    {
        return $this->belongsTo(Livro::class);
    }
}
