<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Livro extends Model
{
    protected $fillable = [
        'isbn',
        'nome',
        'slug',
        'editora_id',
        'bibliografia',
        'imagem_capa',
        'preco'
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::saving(function (self $livro): void {
            if (!$livro->isDirty('nome') && !empty($livro->slug)) {
                return;
            }

            $livro->slug = static::generateUniqueSlug($livro->nome, $livro->id);
        });
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $query = $this->newQuery();

        $livro = $query
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->orWhere('nome', $value)
            ->when(is_numeric($value), fn ($q) => $q->orWhere('id', (int) $value))
            ->first();

        if (!$livro) {
            abort(404);
        }

        return $livro;
    }

    protected static function generateUniqueSlug(string $nome, ?int $ignoreId = null): string
    {
        $base = Str::slug($nome);
        $base = $base !== '' ? $base : 'livro';

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

    // Relacao N:1 com a editora do livro.
    public function editora()
    {
        return $this->belongsTo(Editora::class);
    }

    // Relacao N:N com os autores do livro.
    public function autores()
    {
        return $this->belongsToMany(Autor::class);
    }

    // Relacao 1:N com requisicoes feitas para este livro.
    public function requisicoes()
    {
        return $this->hasMany(Requisicao::class);
    }

    // Relacao 1:N com alertas de disponibilidade criados para este livro.
    public function alertasDisponibilidade()
    {
        return $this->hasMany(AlertaDisponibilidadeLivro::class);
    }

    /**
     * Retorna livros relacionados com base em palavras-chave da descrição (bibliografia).
     * Exclui o próprio livro da lista.
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function relacionados($limit = 4)
    {
        // Extrai palavras-chave da descrição do livro atual
        $palavras = collect(preg_split('/\W+/', strtolower($this->bibliografia)))->filter(function($p) {
            return strlen($p) > 3; // ignora palavras muito curtas
        })->unique();

        if ($palavras->isEmpty()) {
            return collect();
        }

        // Monta a query para encontrar livros com descrições semelhantes
        $query = Livro::where('id', '!=', $this->id);
        $query->where(function($q) use ($palavras) {
            foreach ($palavras as $palavra) {
                $q->orWhere('bibliografia', 'LIKE', "%$palavra%");
            }
        });

        return $query->limit($limit)->get();
    }
}



