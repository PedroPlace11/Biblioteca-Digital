<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use App\Notifications\ReviewEstadoAtualizadoNotification;

class AdminReviewController extends Controller
{
    // Lista reviews no painel admin com filtros, pesquisa e ordenacao.
    public function index(Request $request)
    {
        // Query base com relacoes para evitar consultas adicionais na vista.
        $query = Review::with(['user', 'livro']);

        // Aplica filtro por estado quando um valor valido e escolhido.
        $estado = $request->input('estado', 'todas');
        if (in_array($estado, ['ativo', 'recusado', 'suspenso'])) {
            $query->where('estado', $estado);
        }

        // Filtra por dados do utilizador associado ao review.
        $q = trim($request->input('q', ''));
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->whereHas('user', function ($user) use ($q) {
                    $user->where('name', 'like', "%$q%")
                        ->orWhere('email', 'like', "%$q%")
                        ->orWhere('numero_leitor', 'like', "%$q%")
                        ->orWhere('numero_leitor_seq', 'like', "%$q%") ;
                });
            });
        }

        // Ordena resultados conforme preferencia recebida da interface.
        $ordenar = $request->input('ordenar', 'recentes');
        switch ($ordenar) {
            case 'antigos':
                // Ordena do mais antigo para o mais recente.
                $query->orderBy('created_at', 'asc');
                break;
            case 'nome':
                // Ordena alfabeticamente pelo nome do utilizador.
                $query->join('users', 'reviews.user_id', '=', 'users.id')
                      ->orderBy('users.name', 'asc')
                      ->select('reviews.*');
                break;
            case 'livro':
                // Ordena alfabeticamente pelo nome do livro.
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

        // Pagina resultados preservando filtros ativos na query string.
        $reviews = $query->paginate(10)->appends($request->all());

        // Devolve a vista de listagem de reviews do admin.
        return view('admin.reviews.index', compact('reviews'));
    }

    // Mostra detalhe do review e garante URL canonica da rota.
    public function show(Request $request, Review $review)
    {
        // Carrega relacoes necessarias para apresentar o detalhe completo.
        $review->load(['user', 'livro']);

        // Le parametro atual da rota para validar consistencia com route key.
        $parametroRota = (string) $request->segment(3);

        // Redireciona para URL canonica quando necessario.
        if ($parametroRota !== (string) $review->getRouteKey()) {
            return redirect()->route('admin.reviews.show', $review, 301);
        }

        // Renderiza a vista de detalhe do review.
        return view('admin.reviews.show', compact('review'));
    }

    // Atualiza estado do review e notifica o cidadao associado.
    public function update(Request $request, Review $review)
    {
        // Valida estado permitido e justificacao opcional.
        $request->validate([
            'estado' => 'required|in:ativo,recusado',
            'justificacao' => 'nullable|string|max:2000',
        ]);

        // Persiste novo estado e justificacao apenas quando houver recusa.
        $review->estado = $request->estado;
        $review->justificacao = $request->estado === 'recusado' ? $request->justificacao : null;
        $review->save();

        // Notifica o cidadao por email e notificacao interna.
        $review->user->notify(new ReviewEstadoAtualizadoNotification($review));

        // Regressa a listagem com mensagem de sucesso.
        return redirect()->route('admin.reviews.index')->with('success', 'Estado do review atualizado.');
    }
}
