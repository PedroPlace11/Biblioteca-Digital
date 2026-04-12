<?php

namespace App\Http\Controllers\Cidadao;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Review;

class ReviewController extends Controller
{
    // Lista reviews do cidadao autenticado com filtros e ordenacao.
    public function index(Request $request)
    {
        // Query base limitada ao utilizador logado e com relacao do livro.
        $query = Auth::user()
            ->reviews()
            ->with('livro');

        // Aplica filtro por estado quando um valor valido e selecionado.
        $estado = $request->input('estado', 'todas');
        if (in_array($estado, ['ativo', 'recusado', 'suspenso'])) {
            $query->where('estado', $estado);
        } else {
            // Estado "todas" nao adiciona restricoes extras.
        }

        // Ordena a listagem conforme criterio escolhido na interface.
        $ordenar = $request->input('ordenar', 'recentes');
        switch ($ordenar) {
            case 'antigos':
                // Mais antigos primeiro.
                $query->orderBy('created_at', 'asc');
                break;
            case 'livro':
                // Ordena alfabeticamente pelo nome do livro relacionado.
                $query->join('livros', 'reviews.livro_id', '=', 'livros.id')
                    ->orderBy('livros.nome', 'asc')
                    ->select('reviews.*');
                break;
            case 'recentes':
            default:
                // Padrao: reviews mais recentes primeiro.
                $query->orderBy('created_at', 'desc');
                break;
        }

        // Pagina resultados e preserva query string atual dos filtros.
        $reviews = $query->paginate(10)->appends($request->all());

        // Devolve vista de listagem de reviews do cidadao.
        return view('cidadao.reviews.index', compact('reviews'));
    }

    // Mostra detalhe da review garantindo propriedade e URL canonica.
    public function show(Request $request, Review $review)
    {
        // Impede acesso a reviews que nao pertencem ao utilizador autenticado.
        if ((int) $review->user_id !== (int) Auth::id()) {
            abort(404);
        }

        // Carrega relacao do livro para exibicao no detalhe.
        $review->load('livro');

        // Captura o parametro da rota para verificar consistencia da URL.
        $parametroRota = (string) $request->segment(3);

        // Redireciona para a URL canonica quando a chave da rota diverge.
        if ($parametroRota !== (string) $review->getRouteKey()) {
            return redirect()->route('cidadao.reviews.show', $review, 301);
        }

        // Devolve vista com os dados da review selecionada.
        return view('cidadao.reviews.show', compact('review'));
    }
}
