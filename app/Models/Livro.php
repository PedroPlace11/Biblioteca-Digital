<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Livro extends Model
{
    protected $fillable = [
        'isbn',
        'nome',
        'editora_id',
        'bibliografia',
        'imagem_capa',
        'preco'
    ];

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



