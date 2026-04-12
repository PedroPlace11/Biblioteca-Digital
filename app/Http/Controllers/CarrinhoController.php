<?php

namespace App\Http\Controllers;

use App\Models\Carrinho;
use App\Models\Livro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CarrinhoController extends Controller
{
    // Mostra o carrinho do cidadao autenticado com itens e total atual.
    public function index(): View
    {
        // Recupera utilizador autenticado para validar acesso ao carrinho.
        $user = Auth::user();

        // Apenas cidadaos podem consultar e gerir carrinho de compras.
        abort_unless($user && $user->role === 'cidadao', 403);

        // Obtem carrinho existente ou cria um novo para o utilizador.
        $carrinho = $this->obterOuCriarCarrinhoDoUtilizador($user->id);

        // Carrega itens com dados do livro e autores para a vista.
        $itens = $carrinho->itens()
            ->with('livro.autores')
            ->orderByDesc('id')
            ->get();

        // Soma subtotais de cada item para calcular total do carrinho.
        $total = $itens->sum(fn ($item) => $item->subtotal);

        // Renderiza pagina principal do carrinho.
        return view('carrinho.index', compact('carrinho', 'itens', 'total'));
    }

    // Adiciona um livro ao carrinho e responde em HTML ou JSON.
    public function adicionar(Request $request, Livro $livro): RedirectResponse|JsonResponse
    {
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        // Garante carrinho pronto para receber o item.
        $carrinho = $this->obterOuCriarCarrinhoDoUtilizador($user->id);

        // Reaproveita item existente do mesmo livro ou cria novo item em memoria.
        $item = $carrinho->itens()->firstOrNew([
            'livro_id' => $livro->id,
        ]);

        // Preco convertido para float para evitar inconsistencias de tipo.
        $preco = (float) ($livro->preco ?? 0);

        // Impede adicionar livros sem preco valido para compra.
        if ($preco <= 0) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este livro não tem preço válido para compra.',
                ], 422);
            }

            return redirect()->back()->with('popup_info', 'Este livro não tem preço válido para compra.');
        }

        $item->preco_unitario = $preco;
        $item->quantidade = ((int) $item->quantidade) + 1;
        $item->save();

        // Atualiza atividade para reiniciar ciclo de lembrete de abandono.
        $this->registarAtividadeCarrinho($carrinho);

        // Para chamadas AJAX devolve estado atualizado do carrinho.
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Livro adicionado ao carrinho.',
                'cart' => $this->obterResumoCarrinho($carrinho),
            ]);
        }

        // Fluxo tradicional: redireciona para a pagina do carrinho.
        return redirect()->route('carrinho.index')->with('popup_success', 'Livro adicionado ao carrinho.');
    }

    // Atualiza quantidade de um item existente no carrinho.
    public function atualizarQuantidade(Request $request, int $itemId): RedirectResponse
    {
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        // Valida limites de quantidade aceitos pela regra de negocio.
        $dados = $request->validate([
            'quantidade' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $carrinho = $this->obterOuCriarCarrinhoDoUtilizador($user->id);

        // Garante que o item pertence ao carrinho do utilizador autenticado.
        $item = $carrinho->itens()->whereKey($itemId)->firstOrFail();
        $item->quantidade = (int) $dados['quantidade'];
        $item->save();

        $this->registarAtividadeCarrinho($carrinho);

        // Regressa ao carrinho com confirmacao de atualizacao.
        return redirect()->route('carrinho.index')->with('popup_success', 'Quantidade atualizada.');
    }

    // Remove um item do carrinho e suporta resposta HTML ou JSON.
    public function remover(Request $request, int $itemId): RedirectResponse|JsonResponse
    {
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        $carrinho = $this->obterOuCriarCarrinhoDoUtilizador($user->id);

        // Procura item no carrinho atual sem lancar excecao quando nao existe.
        $item = $carrinho->itens()->whereKey($itemId)->first();

        // Remove item encontrado e atualiza timestamp de atividade do carrinho.
        if ($item) {
            $item->delete();
            $this->registarAtividadeCarrinho($carrinho);
        }

        // Para frontend assíncrono devolve resumo atualizado do carrinho.
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Livro removido do carrinho.',
                'cart' => $this->obterResumoCarrinho($carrinho),
            ]);
        }

        // Para requisicoes web normais redireciona para index do carrinho.
        return redirect()->route('carrinho.index')->with('popup_success', 'Livro removido do carrinho.');
    }

    // Remove todos os itens do carrinho do utilizador autenticado.
    public function limpar(): RedirectResponse
    {
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        $carrinho = $this->obterOuCriarCarrinhoDoUtilizador($user->id);
        // Limpeza total dos itens associados ao carrinho.
        $carrinho->itens()->delete();

        $this->registarAtividadeCarrinho($carrinho);

        // Volta para a pagina do carrinho com mensagem de sucesso.
        return redirect()->route('carrinho.index')->with('popup_success', 'Carrinho limpo com sucesso.');
    }

    // Retorna carrinho do utilizador ou cria novo registo quando inexistente.
    private function obterOuCriarCarrinhoDoUtilizador(int $userId): Carrinho
    {
        return Carrinho::firstOrCreate(['user_id' => $userId]);
    }

    // Regista atividade no carrinho e reativa elegibilidade para novos lembretes.
    private function registarAtividadeCarrinho(Carrinho $carrinho): void
    {
        // Limpa marcador de lembrete enviado para futuras notificacoes.
        $carrinho->lembrete_abandono_enviado_em = null;
        $carrinho->save();
        // Atualiza updated_at para refletir interacao recente no carrinho.
        $carrinho->touch();
    }

    // Monta resumo compacto do carrinho para respostas JSON do frontend.
    private function obterResumoCarrinho(Carrinho $carrinho): array
    {
        // Carrega itens mais recentes para calcular totais e listar pre-visualizacao.
        $itens = $carrinho->itens()
            ->with('livro')
            ->orderByDesc('id')
            ->get();

        // Estrutura padronizada usada pelo mini-carrinho no cliente.
        return [
            'count' => (int) $itens->sum('quantidade'),
            'total' => (float) $itens->sum(fn ($item) => (float) $item->subtotal),
            'items' => $itens->take(5)->map(fn ($item) => [
                'id' => (int) $item->id,
                'nome' => (string) ($item->livro?->nome ?? 'Livro removido'),
                'show_url' => $item->livro ? route('livros.show', $item->livro) : null,
                'quantidade' => (int) $item->quantidade,
                'preco_unitario' => (float) $item->preco_unitario,
                'remove_url' => route('carrinho.remover', $item->id),
            ])->values()->all(),
            'view_url' => route('carrinho.index'),
        ];
    }
}
