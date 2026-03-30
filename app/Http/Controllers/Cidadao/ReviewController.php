<?php

namespace App\Http\Controllers\Cidadao;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Review;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Auth::user()
            ->reviews()
            ->with('livro');

        // Filtro por estado
        $estado = $request->input('estado', 'todas');
        if (in_array($estado, ['ativo', 'recusado', 'suspenso'])) {
            $query->where('estado', $estado);
        } else {
            // 'todas' mostra todos os estados
        }

        // Ordenação
        $ordenar = $request->input('ordenar', 'recentes');
        switch ($ordenar) {
            case 'antigos':
                $query->orderBy('created_at', 'asc');
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
        return view('cidadao.reviews.index', compact('reviews'));
    }

    public function show($id)
    {
        $review = Auth::user()
            ->reviews()
            ->with('livro')
            ->findOrFail($id);
        return view('cidadao.reviews.show', compact('review'));
    }
}
