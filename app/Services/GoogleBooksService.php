<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GoogleBooksService
{
    protected $apiUrl = 'https://www.googleapis.com/books/v1/volumes';

    /**
     * Pesquisa livros na Google Books API.
     *
     * @param string $query
     * @param int $maxResults
     * @return array
     */
    public function searchBooks(string $query, int $maxResults = 10): array
    {
        $response = Http::get($this->apiUrl, [
            'q' => $query,
            'maxResults' => $maxResults,
        ]);

        if ($response->successful()) {
            return $response->json('items') ?? [];
        }

        return [];
    }

    /**
     * Mapeia os dados da Google Books API para o formato do modelo Livro.
     *
     * @param array $googleBook
     * @return array
     */
    public function mapToLivro(array $googleBook): array
    {
        $volumeInfo = $googleBook['volumeInfo'] ?? [];
        return [
            'titulo' => $volumeInfo['title'] ?? null,
            'subtitulo' => $volumeInfo['subtitle'] ?? null,
            'autores' => isset($volumeInfo['authors']) ? implode(', ', $volumeInfo['authors']) : null,
            'editora' => $volumeInfo['publisher'] ?? null,
            'ano' => isset($volumeInfo['publishedDate']) ? substr($volumeInfo['publishedDate'], 0, 4) : null,
            'isbn' => $this->extractIsbn($volumeInfo['industryIdentifiers'] ?? []),
            'descricao' => $volumeInfo['description'] ?? null,
            'capa_url' => $volumeInfo['imageLinks']['thumbnail'] ?? null,
        ];
    }

    /**
     * Extrai o ISBN dos identificadores.
     *
     * @param array $identifiers
     * @return string|null
     */
    protected function extractIsbn(array $identifiers): ?string
    {
        foreach ($identifiers as $id) {
            if (isset($id['type']) && str_contains($id['type'], 'ISBN')) {
                return $id['identifier'];
            }
        }
        return null;
    }
}
