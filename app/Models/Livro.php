<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Livro extends Model
{
    // Define campos que podem ser atribuidos em massa (mass assignment).
    protected $fillable = [
        'isbn',
        'nome',
        'slug',
        'editora_id',
        'bibliografia',
        'imagem_capa',
        'preco',
        'stock'
    ];

    // Garante que o stock seja tratado como inteiro nas leituras e escritas.
    protected $casts = [
        'stock' => 'integer',
    ];

    // Define o campo tipo de chave para model binding em rotas (slug ao invés de id).
    public function getRouteKeyName(): string
    {
        // Usa slug para URLs amigaveis (ex: /livros/o-senhor-dos-aneis).
        return 'slug';
    }

    // Gera slug automaticamente quando modelo e salvo.
    protected static function booted(): void
    {
        static::saving(function (self $livro): void {
            // Sai cedo se nome nao foi alterado e slug ja existe.
            if (!$livro->isDirty('nome') && !empty($livro->slug)) {
                return;
            }

            // Gera novo slug unico baseado no nome.
            $livro->slug = static::generateUniqueSlug($livro->nome, $livro->id);
        });
    }

    // Resolve model binding por multiplos criterios (slug, nome, ou ID numerico).
    public function resolveRouteBinding($value, $field = null)
    {
        // Inicia query no banco de dados.
        $query = $this->newQuery();

        // Tenta encontrar livro por: 1) campo especificado (slug), 2) nome, 3) ID numerico.
        $livro = $query
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->orWhere('nome', $value)
            ->when(is_numeric($value), fn ($q) => $q->orWhere('id', (int) $value))
            ->first();

        // Se nao encontrar livro, retorna erro 404.
        if (!$livro) {
            abort(404);
        }

        return $livro;
    }

    // Gera slug unico com sufixo numerico em caso de colisao.
    protected static function generateUniqueSlug(string $nome, ?int $ignoreId = null): string
    {
        // Converte nome em slug (minusculas, hifens em lugar de espacos).
        $base = Str::slug($nome);
        // Fallback se slug ficar vazio apos conversao.
        $base = $base !== '' ? $base : 'livro';

        // Comeca com slug base sem sufixo.
        $slug = $base;
        // Contador para sufixo em caso de colisao.
        $suffix = 2;

        // Loop: enquanto slug existir no banco, incrementa sufixo e tenta novamente.
        while (static::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            // Adiciona sufixo ao slug (livro-2, livro-3, etc).
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    // Relacao N:1 com a editora do livro.
    public function editora()
    {
        // Cada livro pertence a uma unica editora.
        return $this->belongsTo(Editora::class);
    }

    // Relacao N:N com os autores do livro.
    public function autores()
    {
        // Um livro pode ter multiplos autores, e um autor pode ter multiplos livros.
        return $this->belongsToMany(Autor::class);
    }

    // Relacao 1:N com requisicoes feitas para este livro.
    public function requisicoes()
    {
        // Um livro pode ter multiplas requisicoes de usuarios.
        return $this->hasMany(Requisicao::class);
    }

    // Relacao 1:N com alertas de disponibilidade criados para este livro.
    public function alertasDisponibilidade()
    {
        // Usuarios podem criar alertas quando livro nao esta disponivel.
        return $this->hasMany(AlertaDisponibilidadeLivro::class);
    }

    // Relacao 1:N com itens de encomenda que referenciam este livro.
    public function encomendaItens()
    {
        // Um livro pode aparecer em multiplas encomendas vezes.
        return $this->hasMany(EncomendaItem::class);
    }

    /**
     * Encontra livros relacionados baseado em palavras-chave da descricao (bibliografia).
     * Exclui o proprio livro da lista de resultados.
     * @param int $limit Numero maximo de livros relacionados a retornar.
     * @return \Illuminate\Database\Eloquent\Collection Colecao de livros relacionados.
     */
    public function relacionados($limit = 4)
    {
        // Extrai palavras-chave da descricao - divide por espacos e caracteres especiais.
        $palavras = collect(preg_split('/\W+/', strtolower($this->bibliografia)))->filter(function($p) {
            // Ignora palavras muito curtas (menos de 3 caracteres - preposicoes, artigos, etc).
            return strlen($p) > 3;
        })->unique();

        // Se nao houver palavras-chave, retorna colecao vazia.
        if ($palavras->isEmpty()) {
            return collect();
        }

        // Construir query para encontrar livros com descricoes similares.
        $query = Livro::where('id', '!=', $this->id);
        // Adiciona clausulas OR para cada palavra-chave (livros que contenham qualquer palavra).
        $query->where(function($q) use ($palavras) {
            foreach ($palavras as $palavra) {
                // Busca case-insensitive com LIKE.
                $q->orWhere('bibliografia', 'LIKE', "%$palavra%");
            }
        });

        // Retorna resultado limitado e colecao de modelos.
        return $query->limit($limit)->get();
    }
}



