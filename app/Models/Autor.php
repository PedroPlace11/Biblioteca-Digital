<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// Modelo que representa um autor e os seus livros publicados na biblioteca.
class Autor extends Model
{
    protected $fillable = [
        'nome',
        'slug',
        'foto',
        'bibliografia'
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::saving(function (self $autor): void {
            if (!$autor->isDirty('nome') && !empty($autor->slug)) {
                return;
            }

            $autor->slug = static::generateUniqueSlug($autor->nome, $autor->id);
        });
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $autor = $this->newQuery()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->orWhere('nome', $value)
            ->when(is_numeric($value), fn ($q) => $q->orWhere('id', (int) $value))
            ->first();

        if (!$autor) {
            abort(404);
        }

        return $autor;
    }

    protected static function generateUniqueSlug(string $nome, ?int $ignoreId = null): string
    {
        $base = Str::slug($nome);
        $base = $base !== '' ? $base : 'autor';

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

    // Relacao N:N entre autores e livros.
    public function livros()
    {
        return $this->belongsToMany(Livro::class);
    }
}




