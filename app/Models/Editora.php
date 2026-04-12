<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// Modelo que representa uma editora com os seus livros publicados.
class Editora extends Model
{
    // Define campos que podem ser atribuidos em massa (mass assignment).
    protected $fillable = [
        'nome',
        'slug',
        'logotipo'
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
        static::saving(function (self $editora): void {
            // Sai cedo se nome nao foi alterado e slug ja existe.
            if (!$editora->isDirty('nome') && !empty($editora->slug)) {
                return;
            }

            // Gera novo slug unico baseado no nome.
            $editora->slug = static::generateUniqueSlug($editora->nome, $editora->id);
        });
    }

    // Resolve model binding por multiplos criterios (slug, nome ou id).
    public function resolveRouteBinding($value, $field = null)
    {
        // Busca editora pelo campo especificado, posteriormente pelo nome, ou pelo id se numerico.
        $editora = $this->newQuery()
            // Primeiro tenta o campo de rota (slug por padrao).
            ->where($field ?? $this->getRouteKeyName(), $value)
            // Fallback: busca por nome exato.
            ->orWhere('nome', $value)
            // Fallback: se valor e numerico, busca por id.
            ->when(is_numeric($value), fn ($q) => $q->orWhere('id', (int) $value))
            ->first();

        // Se nenhum resultado encontrado, aborta com 404.
        if (!$editora) {
            abort(404);
        }

        // Retorna editora encontrada.
        return $editora;
    }

    // Gera slug unico para uma editora, incrementando sufixo se necessario para evitar colisoes.
    protected static function generateUniqueSlug(string $nome, ?int $ignoreId = null): string
    {
        // Converte nome em slug (minusculas, hifens em espacos, caracteres especiais removidos).
        $base = Str::slug($nome);
        // Fallback para 'editora' se nome resultar em string vazia.
        $base = $base !== '' ? $base : 'editora';

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
            // Incrementa slug adicionando sufixo (ex: editora-2, editora-3).
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        // Retorna slug final garantidamente unico.
        return $slug;
    }

    // Relacao 1:N entre editora e livros publicados.
    public function livros()
    {
        // Uma editora pode ter muitos livros publicados.
        return $this->hasMany(Livro::class);
    }
}




