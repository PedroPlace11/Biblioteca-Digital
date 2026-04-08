<?php

namespace App\Http\Controllers\Cidadao;

use App\Http\Controllers\Controller;
use App\Models\Morada;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MoradaController extends Controller
{
    public function index(Request $request): View
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        $moradas = $user->moradas()->latest('id')->get();
        $moradaEmEdicao = null;
        $editarId = (int) $request->query('editar', 0);

        if ($editarId > 0) {
            $moradaEmEdicao = $moradas->firstWhere('id', $editarId);
        }

        if (!$moradaEmEdicao && old('form_mode') === 'update') {
            $moradaEmEdicao = $moradas->firstWhere('id', (int) old('morada_id'));
        }

        return view('cidadao.moradas.index', compact('moradas', 'moradaEmEdicao'));
    }

    public function edit(Morada $morada): View
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);
        abort_unless((int) $morada->user_id === (int) $user->id, 404);

        $moradas = $user->moradas()->latest('id')->get();
        $moradaEmEdicao = $moradas->firstWhere('id', (int) $morada->id);

        return view('cidadao.moradas.index', compact('moradas', 'moradaEmEdicao'));
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        $dados = $this->validarDadosMorada($request);

        $user->moradas()->create($dados);

        return redirect()
            ->route('cidadao.moradas.index')
            ->with('popup_success', 'Morada guardada com sucesso.');
    }

    public function update(Request $request, Morada $morada): RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);
        abort_unless((int) $morada->user_id === (int) $user->id, 404);

        $dados = $this->validarDadosMorada($request);

        $morada->update($dados);

        return redirect()
            ->route('cidadao.moradas.index');
    }

    public function destroy(Morada $morada): RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);
        abort_unless((int) $morada->user_id === (int) $user->id, 404);

        $moradaId = (int) $morada->id;
        $morada->delete();

        if ((int) session('checkout.morada_id', 0) === $moradaId) {
            session()->forget(['checkout.morada_id', 'checkout.morada']);
        }

        return redirect()
            ->route('cidadao.moradas.index');
    }

    private function validarDadosMorada(Request $request): array
    {
        $dados = $request->validate([
            'titulo' => ['required', 'string', 'max:80'],
            'nome_destinatario' => ['required', 'string', 'max:255'],
            'telemovel_destinatario' => ['required', 'string', 'max:40'],
            'morada_linha_1' => ['required', 'string', 'max:255'],
            'morada_linha_2' => ['nullable', 'string', 'max:255'],
            'codigo_postal' => ['required', 'string', 'max:20'],
            'cidade' => ['required', 'string', 'max:120'],
            'pais' => ['required', 'string', 'size:2'],
        ]);

        $dados['pais'] = strtoupper($dados['pais']);

        return $dados;
    }
}
