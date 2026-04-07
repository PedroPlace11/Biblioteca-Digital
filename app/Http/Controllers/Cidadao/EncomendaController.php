<?php

namespace App\Http\Controllers\Cidadao;

use App\Http\Controllers\Controller;
use App\Models\Encomenda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EncomendaController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        $estado = (string) $request->query('estado', 'todas');

        if (!in_array($estado, ['todas', 'pendente_pagamento', 'paga', 'enviado', 'pagamento_recusado'], true)) {
            $estado = 'todas';
        }

        $query = Encomenda::with('itens')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at');

        if ($estado !== 'todas') {
            $query->where('estado', $estado);
        }

        $encomendas = $query->paginate(5)->withQueryString();

        return view('cidadao.encomendas.index', compact('encomendas', 'estado'));
    }

    public function show(Encomenda $encomenda): View
    {
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        if ((int) $encomenda->user_id !== (int) $user->id) {
            abort(403);
        }

        $encomenda->load('itens', 'user');

        return view('cidadao.encomendas.show', compact('encomenda'));
    }
}
