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
    // Lista moradas do cidadao e define se alguma deve abrir em modo edicao.
    public function index(Request $request): View
    {
        /** @var \App\Models\User|null $user */
        // Recupera utilizador autenticado para validacao de permissao.
        $user = Auth::user();

        // Permite acesso apenas a utilizadores com role cidadao.
        abort_unless($user && $user->role === 'cidadao', 403);

        // Carrega moradas por ordem decrescente de criacao.
        $moradas = $user->moradas()->latest('id')->get();
        $moradaEmEdicao = null;
        // Parametro opcional para abrir uma morada especifica em edicao.
        $editarId = (int) $request->query('editar', 0);

        if ($editarId > 0) {
            $moradaEmEdicao = $moradas->firstWhere('id', $editarId);
        }

        // Quando ha erro de validacao, restaura a morada que estava a ser editada.
        if (!$moradaEmEdicao && old('form_mode') === 'update') {
            $moradaEmEdicao = $moradas->firstWhere('id', (int) old('morada_id'));
        }

        return view('cidadao.moradas.index', compact('moradas', 'moradaEmEdicao'));
    }

    // Abre a listagem ja com a morada selecionada para edicao.
    public function edit(Morada $morada): View
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);
        // Garante que a morada pertence ao utilizador autenticado.
        abort_unless((int) $morada->user_id === (int) $user->id, 404);

        $moradas = $user->moradas()->latest('id')->get();
        $moradaEmEdicao = $moradas->firstWhere('id', (int) $morada->id);

        return view('cidadao.moradas.index', compact('moradas', 'moradaEmEdicao'));
    }

    // Cria uma nova morada para o cidadao autenticado.
    public function store(Request $request): RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        // Valida e normaliza os campos antes de persistir.
        $dados = $this->validarDadosMorada($request);

        $user->moradas()->create($dados);

        return redirect()
            ->route('cidadao.moradas.index');
    }

    // Atualiza uma morada existente do utilizador autenticado.
    public function update(Request $request, Morada $morada): RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);
        abort_unless((int) $morada->user_id === (int) $user->id, 404);

        // Reaproveita as mesmas regras de validacao da criacao.
        $dados = $this->validarDadosMorada($request);

        $morada->update($dados);

        return redirect()
            ->route('cidadao.moradas.index');
    }

    // Remove uma morada do utilizador e limpa dados de checkout associados.
    public function destroy(Morada $morada): RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);
        abort_unless((int) $morada->user_id === (int) $user->id, 404);

        $moradaId = (int) $morada->id;
        $morada->delete();

        // Se a morada removida estava selecionada no checkout, limpa a sessao.
        if ((int) session('checkout.morada_id', 0) === $moradaId) {
            session()->forget(['checkout.morada_id', 'checkout.morada']);
        }

        return redirect()
            ->route('cidadao.moradas.index');
    }

    // Valida dados do formulario e aplica normalizacoes basicas.
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

        // Normaliza codigo do pais para o formato ISO em maiusculas.
        $dados['pais'] = strtoupper($dados['pais']);

        return $dados;
    }
}
