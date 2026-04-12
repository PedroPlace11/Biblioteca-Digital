<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// Modelo que representa um autor e os seus livros publicados na biblioteca.
class Autor extends Model
{
    // Define campos que podem ser atribuidos em massa (mass assignment).
    protected $fillable = [
        'nome',
        'slug',
        'foto',
        'bibliografia'
    ];

    // Define 'slug' como chave de rota para implicit model binding.
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // Gera slug automaticamente quando modelo e salvo (se nome foi alterado ou slug vazio).
    protected static function booted(): void
    {
        // Hook executado antes de salvar o modelo.
        static::saving(function (self $autor): void {
            // Sai cedo se nome nao foi alterado e slug ja existe.
            if (!$autor->isDirty('nome') && !empty($autor->slug)) {
                return;
            }

            // Gera novo slug unico baseado no nome.
            $autor->slug = static::generateUniqueSlug($autor->nome, $autor->id);
        });
    }

    // Resolve model binding por multiplos criterios (slug, nome ou id).
    public function resolveRouteBinding($value, $field = null)
    {
        // Busca autor pelo campo especificado, posteriormente pelo nome, ou pelo id se numerico.
        $autor = $this->newQuery()
            // Primeiro tenta o campo de rota (slug por padrao).
            ->where($field ?? $this->getRouteKeyName(), $value)
            // Fallback: busca por nome exato.
            ->orWhere('nome', $value)
            // Fallback: se valor e numerico, busca por id.
            ->when(is_numeric($value), fn ($q) => $q->orWhere('id', (int) $value))
            ->first();

        // Se nenhum resultado encontrado, aborta com 404.
        if (!$autor) {
            abort(404);
        }

        // Retorna autor encontrado.
        return $autor;
    }

    // Gera slug unico para um autor, incrementando sufixo se necessario para evitar colisoes.
    protected static function generateUniqueSlug(string $nome, ?int $ignoreId = null): string
    {
        // Converte nome em slug (minusculas, hifens em espacos, caracteres especiais removidos).
        $base = Str::slug($nome);
        // Fallback para 'autor' se nome resultar em string vazia.
        $base = $base !== '' ? $base : 'autor';

        // Inicia com slug base.
        $slug = $base;
        // Sufixo para garantir unicidade em colisoes.
        $suffix = 2;

        // Loop enquanto slug ja existe no banco de dados (excluindo o id fornecido).
        while (static::query()
            // Se ignoreId fornecido, exclui esse id da verificacao (util para updates).
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            // Incrementa slug adicionando sufixo (ex: autor-2, autor-3).
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        // Retorna slug final garantidamente unico.
        return $slug;
    }

    // Relacao N:N entre autores e livros.
    public function livros()
    {
        // Cada autor pode ter muitos livros e cada livro pode ter muitos autores.
        return $this->belongsToMany(Livro::class);
    }
}




