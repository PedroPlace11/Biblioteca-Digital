<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Livro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Notifications\ReviewSubmetidoNotification;

class ReviewController extends Controller
{
    // Exibe formulário para criar review
    public function create($livro_id)
    {
        // Carrega livro ou falha se nao existir.
        $livro = Livro::findOrFail($livro_id);
        // Renderiza formulario de criacao de review com dados do livro.
        return view('reviews.create', compact('livro'));
    }

    // Salva review submetido pelo cidadão
    public function store(Request $request, $livro_id)
    {
        // Valida conteudo (obrigatorio e max 2000 chars) e rating (1-5 estrelas).
        $request->validate([
            'conteudo' => 'required|string|max:2000',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        // Cria review com estado inicial 'suspenso' (aguarda moderacao admin).
        $review = Review::create([
            'user_id' => Auth::id(),
            'livro_id' => $livro_id,
            'conteudo' => $request->conteudo,
            // Estado inicial suspenso ate aprovacao de admin.
            'estado' => 'suspenso',
            'rating' => $request->rating,
        ]);

        // Recupera todos os admins do sistema.
        $admins = User::where('role', 'admin')->get();
        // Notifica cada admin da criacao do novo review (requer moderacao).
        foreach ($admins as $admin) {
            $admin->notify(new ReviewSubmetidoNotification($review));
        }

        // Redireciona para o detalhe do review do cidadão (rota correta com prefixo 'conta')
        // Nao mantem dados do formulario preenchidos.
        return redirect('/conta/reviews/' . $review->id);
    }
}
