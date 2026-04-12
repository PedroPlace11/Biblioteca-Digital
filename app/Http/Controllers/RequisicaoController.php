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
        // Le parametros de filtro da query string.
        $autorId = $request->input('autor_id');
        $editoraId = $request->input('editora_id');
        // Define filtro de disponibilidade (todas, disponivel, indisponivel).
        $disponibilidade = $request->input('disponibilidade', 'todas');
        // Define ordenacao inicial e direcao.
        $sortBy = $request->input('sort_by', 'nome');
        $sortOrder = $request->input('sort_order', 'asc');

        // Valida opcao de ordenacao para prevenir SQL injection.
        if (!in_array($sortBy, ['nome', 'editora', 'autor'], true)) {
            $sortBy = 'nome';
        }

        // Valida direcao de ordenacao.
        if (!in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = 'asc';
        }

        // Valida opcao de disponibilidade.
        if (!in_array($disponibilidade, ['todas', 'disponivel', 'indisponivel'], true)) {
            $disponibilidade = 'todas';
        }

        // Query base para listagem de livros, incluindo total de requisicoes por livro.
        $query = Livro::with('autores', 'editora')->withCount('requisicoes');

        // Filtra por autor quando parametro foi informado.
        if (!empty($autorId)) {
            $query->whereHas('autores', function ($autorQuery) use ($autorId) {
                $autorQuery->where('autors.id', $autorId);
            });
        }

        // Filtra por editora quando parametro foi informado.
        if (!empty($editoraId)) {
            $query->where('editora_id', $editoraId);
        }

        // Filtra por disponibilidade (livros com/sem requisicoes ativas).
        if ($disponibilidade === 'disponivel') {
            // Mostra apenas livros sem requisicoes ativas.
            $query->whereDoesntHave('requisicoes');
        } elseif ($disponibilidade === 'indisponivel') {
            // Mostra apenas livros com requisicoes ativas.
            $query->whereHas('requisicoes');
        }
        // Se 'todas', nenhum filtro adicional e aplicado.

        // Aplica ordenacao com joins apropriados conforme criterio escolhido.
        if ($sortBy === 'editora') {
            // Ordena por nome da editora com join e distinct para evitar duplicacoes.
            $query->join('editoras', 'livros.editora_id', '=', 'editoras.id')
                ->select('livros.*')
                ->distinct()
                ->orderBy('editoras.nome', $sortOrder);
        } elseif ($sortBy === 'autor') {
            // Ordena por nome do autor com joins na tabela pivot e autores.
            $query->join('autor_livro', 'livros.id', '=', 'autor_livro.livro_id')
                ->join('autors', 'autor_livro.autor_id', '=', 'autors.id')
                ->select('livros.*')
                ->distinct()
                ->orderBy('autors.nome', $sortOrder);
        } else {
            // Ordena por nome do livro (padrao).
            $query->orderBy('nome', $sortOrder);
        }

        // Pagina resultados para exibicao.
        $livros = $query->paginate(10);

        // Carrega listas de autores e editoras para os filtros da view.
        $autores = Autor::orderBy('nome')->get(['id', 'nome']);
        $editoras = Editora::orderBy('nome')->get(['id', 'nome']);

        // Verifica se usuario autenticado e admin.
        $isAdmin = $request->user()?->role === 'admin';
        // Inicializa indicadores globais (para admin) e pessoais (para cidadao).
        $totalRequisicoesAtivas = 0;
        $totalRequisicoesUltimos30Dias = 0;
        $totalLivrosEntreguesHoje = 0;
        $totalLivrosRequisitadosPorMim = 0;
        $totalRequisicoesUltimos30DiasPorMim = 0;
        $totalLivrosEntreguesPorMim = 0;

        // Indicadores globais para administrador e indicadores pessoais para cidadão.
        if ($isAdmin) {
            // Total de requisicoes ativas (nao deletadas).
            $totalRequisicoesAtivas = Requisicao::count();
            // Total de requisicoes criadas nos ultimos 30 dias (incluindo deletadas).
            $totalRequisicoesUltimos30Dias = Requisicao::withTrashed()
                ->where('created_at', '>=', now()->subDays(30))
                ->count();
            // Total de livros devolvidos (recebidos) hoje.
            $totalLivrosEntreguesHoje = Requisicao::withTrashed()
                ->whereNotNull('data_recepcao_real')
                ->whereDate('data_recepcao_real', now()->toDateString())
                ->count();
        } else {
            // Cidadao: total de livros requisitados por este utilizador (incluindo devolvidos).
            $totalLivrosRequisitadosPorMim = Requisicao::withTrashed()
                ->where('user_id', $request->user()?->id)
                ->count();
            // Cidadao: total de requisicoes criadas nos ultimos 30 dias.
            $totalRequisicoesUltimos30DiasPorMim = Requisicao::withTrashed()
                ->where('user_id', $request->user()?->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->count();
            // Cidadao: total de livros devolvidos com sucesso por este utilizador.
            $totalLivrosEntreguesPorMim = Requisicao::withTrashed()
                ->where('user_id', $request->user()?->id)
                ->whereNotNull('data_recepcao_real')
                ->count();
        }

        // Renderiza pagina com todos os dados compilados e valores de filtros.
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



