<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use App\Notifications\ReviewEstadoAtualizadoNotification;

class AdminReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with(['user', 'livro']);

        // Filtro por estado
        $estado = $request->input('estado', 'todas');
        if (in_array($estado, ['ativo', 'recusado', 'suspenso'])) {
            $query->where('estado', $estado);
        }

        // Filtro por pesquisa
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

        // Ordenação
        $ordenar = $request->input('ordenar', 'recentes');
        switch ($ordenar) {
            case 'antigos':
                $query->orderBy('created_at', 'asc');
                break;
            case 'nome':
                $query->join('users', 'reviews.user_id', '=', 'users.id')
                      ->orderBy('users.name', 'asc')
                      ->select('reviews.*');
                break;
            case 'livro':
                $query->join('livros', 'reviews.livro_id', '=', 'livros.id')
                      ->orderBy('livros.nome', 'asc')
                      ->select('reviews.*');
                break;
            case 'recentes':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }
        $reviews = $query->paginate(10)->appends($request->all());
        return view('admin.reviews.index', compact('reviews'));
    }

    public function show(Request $request, Review $review)
    {
        $review->load(['user', 'livro']);

        $parametroRota = (string) $request->segment(3);

        if ($parametroRota !== (string) $review->getRouteKey()) {
            return redirect()->route('admin.reviews.show', $review, 301);
        }

        return view('admin.reviews.show', compact('review'));
    }

    public function update(Request $request, Review $review)
    {
        $request->validate([
            'estado' => 'required|in:ativo,recusado',
            'justificacao' => 'nullable|string|max:2000',
        ]);
        $review->estado = $request->estado;
        $review->justificacao = $request->estado === 'recusado' ? $request->justificacao : null;
        $review->save();
        // Notificar cidadão por email e sininho
        $review->user->notify(new ReviewEstadoAtualizadoNotification($review));
        return redirect()->route('admin.reviews.index')->with('success', 'Estado do review atualizado.');
    }
}
