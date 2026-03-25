<?php
namespace App\Http\Controllers;

use App\Models\Autor;
use App\Models\Editora;
use App\Models\Livro;
use App\Models\Requisicao;
use Illuminate\Http\Request;

// Controlador da página de requisições com filtros e indicadores por perfil.
class RequisicaoController extends Controller
{
    // Lista livros e disponibilidade para requisicao na pagina dedicada.
    public function index(Request $request)
    {
        $autorId = $request->input('autor_id');
        $editoraId = $request->input('editora_id');
        $disponibilidade = $request->input('disponibilidade', 'todas');
        $sortBy = $request->input('sort_by', 'nome');
        $sortOrder = $request->input('sort_order', 'asc');

        if (!in_array($sortBy, ['nome', 'editora', 'autor'], true)) {
            $sortBy = 'nome';
        }

        if (!in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = 'asc';
        }

        if (!in_array($disponibilidade, ['todas', 'disponivel', 'indisponivel'], true)) {
            $disponibilidade = 'todas';
        }

        // Query base para listagem de livros, incluindo total de requisições por livro.
        $query = Livro::with('autores', 'editora')->withCount('requisicoes');

        if (!empty($autorId)) {
            $query->whereHas('autores', function ($autorQuery) use ($autorId) {
                $autorQuery->where('autors.id', $autorId);
            });
        }

        if (!empty($editoraId)) {
            $query->where('editora_id', $editoraId);
        }

        if ($disponibilidade === 'disponivel') {
            $query->whereDoesntHave('requisicoes');
        } elseif ($disponibilidade === 'indisponivel') {
            $query->whereHas('requisicoes');
        }

        if ($sortBy === 'editora') {
            $query->join('editoras', 'livros.editora_id', '=', 'editoras.id')
                ->select('livros.*')
                ->distinct()
                ->orderBy('editoras.nome', $sortOrder);
        } elseif ($sortBy === 'autor') {
            $query->join('autor_livro', 'livros.id', '=', 'autor_livro.livro_id')
                ->join('autors', 'autor_livro.autor_id', '=', 'autors.id')
                ->select('livros.*')
                ->distinct()
                ->orderBy('autors.nome', $sortOrder);
        } else {
            $query->orderBy('nome', $sortOrder);
        }

        $livros = $query->paginate(10);

        $autores = Autor::orderBy('nome')->get(['id', 'nome']);
        $editoras = Editora::orderBy('nome')->get(['id', 'nome']);

        $isAdmin = $request->user()?->role === 'admin';
        $totalRequisicoesAtivas = 0;
        $totalRequisicoesUltimos30Dias = 0;
        $totalLivrosEntreguesHoje = 0;
        $totalLivrosRequisitadosPorMim = 0;
        $totalRequisicoesUltimos30DiasPorMim = 0;
        $totalLivrosEntreguesPorMim = 0;

        // Indicadores globais para administrador e indicadores pessoais para cidadão.
        if ($isAdmin) {
            $totalRequisicoesAtivas = Requisicao::count();
            $totalRequisicoesUltimos30Dias = Requisicao::withTrashed()
                ->where('created_at', '>=', now()->subDays(30))
                ->count();
            $totalLivrosEntreguesHoje = Requisicao::withTrashed()
                ->whereNotNull('data_recepcao_real')
                ->whereDate('data_recepcao_real', now()->toDateString())
                ->count();
        } else {
            $totalLivrosRequisitadosPorMim = Requisicao::withTrashed()
                ->where('user_id', $request->user()?->id)
                ->count();
            $totalRequisicoesUltimos30DiasPorMim = Requisicao::withTrashed()
                ->where('user_id', $request->user()?->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->count();
            $totalLivrosEntreguesPorMim = Requisicao::withTrashed()
                ->where('user_id', $request->user()?->id)
                ->whereNotNull('data_recepcao_real')
                ->count();
        }

        return view('requisicoes.index', compact(
            'livros',
            'autores',
            'editoras',
            'autorId',
            'editoraId',
            'disponibilidade',
            'sortBy',
            'sortOrder',
            'isAdmin',
            'totalRequisicoesAtivas',
            'totalRequisicoesUltimos30Dias',
            'totalLivrosEntreguesHoje',
            'totalLivrosRequisitadosPorMim',
            'totalRequisicoesUltimos30DiasPorMim',
            'totalLivrosEntreguesPorMim'
        ));
    }
}



