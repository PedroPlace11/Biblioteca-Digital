<?php

namespace App\Http\Controllers;

use App\Models\Carrinho;
use App\Models\Livro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CarrinhoController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        $carrinho = $this->obterOuCriarCarrinhoDoUtilizador($user->id);

        $itens = $carrinho->itens()
            ->with('livro.autores')
            ->orderByDesc('id')
            ->get();

        $total = $itens->sum(fn ($item) => $item->subtotal);

        return view('carrinho.index', compact('carrinho', 'itens', 'total'));
    }

    public function adicionar(Livro $livro): RedirectResponse
    {
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        $carrinho = $this->obterOuCriarCarrinhoDoUtilizador($user->id);

        $item = $carrinho->itens()->firstOrNew([
            'livro_id' => $livro->id,
        ]);

        $preco = (float) ($livro->preco ?? 0);

        if ($preco <= 0) {
            return redirect()->back()->with('popup_info', 'Este livro não tem preço válido para compra.');
        }

        $item->preco_unitario = $preco;
        $item->quantidade = ((int) $item->quantidade) + 1;
        $item->save();

        $this->registarAtividadeCarrinho($carrinho);

        return redirect()->route('carrinho.index')->with('popup_success', 'Livro adicionado ao carrinho.');
    }

    public function atualizarQuantidade(Request $request, int $itemId): RedirectResponse
    {
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        $dados = $request->validate([
            'quantidade' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $carrinho = $this->obterOuCriarCarrinhoDoUtilizador($user->id);

        $item = $carrinho->itens()->whereKey($itemId)->firstOrFail();
        $item->quantidade = (int) $dados['quantidade'];
        $item->save();

        $this->registarAtividadeCarrinho($carrinho);

        return redirect()->route('carrinho.index')->with('popup_success', 'Quantidade atualizada.');
    }

    public function remover(int $itemId): RedirectResponse
    {
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        $carrinho = $this->obterOuCriarCarrinhoDoUtilizador($user->id);

        $item = $carrinho->itens()->whereKey($itemId)->first();

        if ($item) {
            $item->delete();
            $this->registarAtividadeCarrinho($carrinho);
        }

        return redirect()->route('carrinho.index')->with('popup_success', 'Livro removido do carrinho.');
    }

    public function limpar(): RedirectResponse
    {
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        $carrinho = $this->obterOuCriarCarrinhoDoUtilizador($user->id);
        $carrinho->itens()->delete();

        $this->registarAtividadeCarrinho($carrinho);

        return redirect()->route('carrinho.index')->with('popup_success', 'Carrinho limpo com sucesso.');
    }

    private function obterOuCriarCarrinhoDoUtilizador(int $userId): Carrinho
    {
        return Carrinho::firstOrCreate(['user_id' => $userId]);
    }

    private function registarAtividadeCarrinho(Carrinho $carrinho): void
    {
        $carrinho->lembrete_abandono_enviado_em = null;
        $carrinho->save();
        $carrinho->touch();
    }
}
