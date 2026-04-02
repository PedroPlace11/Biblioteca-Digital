<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// Modelo que representa uma editora com os seus livros publicados.
class Editora extends Model
{
    protected $fillable = [
        'nome',
        'slug',
        'logotipo'
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::saving(function (self $editora): void {
            if (!$editora->isDirty('nome') && !empty($editora->slug)) {
                return;
            }

            $editora->slug = static::generateUniqueSlug($editora->nome, $editora->id);
        });
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $editora = $this->newQuery()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->orWhere('nome', $value)
            ->when(is_numeric($value), fn ($q) => $q->orWhere('id', (int) $value))
            ->first();

        if (!$editora) {
            abort(404);
        }

        return $editora;
    }

    protected static function generateUniqueSlug(string $nome, ?int $ignoreId = null): string
    {
        $base = Str::slug($nome);
        $base = $base !== '' ? $base : 'editora';

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

    // Relacao 1:N entre editora e livros publicados.
    public function livros()
    {
        return $this->hasMany(Livro::class);
    }
}




