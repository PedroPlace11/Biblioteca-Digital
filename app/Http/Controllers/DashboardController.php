<?php
namespace App\Http\Controllers;

use App\Models\Livro;
use App\Models\Autor;
use App\Models\Editora;
use DB;

// Controlador que centraliza os indicadores exibidos no painel.
class DashboardController extends Controller
{
    // Monta as metricas, rankings e listas para a pagina inicial autenticada.
    public function index()
    {
        // Contadores gerais para cards principais do painel.
        $totalLivros = Livro::count();
        $totalAutores = Autor::count();
        $totalEditoras = Editora::count();

        // Soma de precos para estimar valor agregado do catalogo.
        $valorLivros = Livro::sum('preco');

        // Lista dos livros mais recentes com autores para exibicao rapida.
        $ultimosLivros = Livro::with('autores')->latest()->take(5)->get();

        // Quantidade de livros por editora para grafico/resumo por entidade.
        $livrosPorEditora = Editora::withCount('livros')->get();

        // Ranking dos autores com maior numero de livros cadastrados.
        $topAutores = Autor::withCount('livros')
            ->orderBy('livros_count', 'desc')
            ->take(5)
            ->get();

        // Destaques de faixa de preco do catalogo.
        $livroMaisCaro = Livro::orderBy('preco', 'desc')->first();
        $livroMaisBarato = Livro::orderBy('preco', 'asc')->first();

        // Renderiza dashboard com todas as metricas preparadas.
        return view('dashboard', compact(
            'totalLivros',
            'totalAutores',
            'totalEditoras',
            'valorLivros',
            'ultimosLivros',
            'livrosPorEditora',
            'topAutores',
            'livroMaisCaro',
            'livroMaisBarato'
        ));
    }
}



