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
        $livro = Livro::findOrFail($livro_id);
        return view('reviews.create', compact('livro'));
    }

    // Salva review submetido pelo cidadão
    public function store(Request $request, $livro_id)
    {

        $request->validate([
            'conteudo' => 'required|string|max:2000',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $review = Review::create([
            'user_id' => Auth::id(),
            'livro_id' => $livro_id,
            'conteudo' => $request->conteudo,
            'estado' => 'suspenso',
            'rating' => $request->rating,
        ]);

        // Notificar todos admins
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new ReviewSubmetidoNotification($review));
        }

        // Redireciona para o detalhe do review do cidadão (rota correta com prefixo 'conta')
        // Não mantém dados do formulário preenchidos
        return redirect('/conta/reviews/' . $review->id);
    }
}
