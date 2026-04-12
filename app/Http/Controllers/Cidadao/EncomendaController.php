<?php

namespace App\Http\Controllers\Cidadao;

use App\Http\Controllers\Controller;
use App\Models\Encomenda;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EncomendaController extends Controller
{
    // Lista encomendas do cidadao autenticado com filtros por estado e ordenacao.
    public function index(Request $request): View
    {
        // Recupera utilizador logado para validar acesso ao painel de cidadao.
        $user = Auth::user();

        // Garante que apenas utilizadores com role cidadao acedem ao recurso.
        abort_unless($user && $user->role === 'cidadao', 403);

        // Le parametros de filtro/ordenacao da query string.
        $ordem = (string) $request->query('ordem', 'mais_recente');
        $estadoFiltro = (string) $request->query('estado', 'todos');
        // Estados visiveis no historico do cidadao.
        $estadosPermitidosNaListagem = ['pendente_pagamento', 'paga', 'enviado', 'pagamento_recusado'];

        if (!in_array($ordem, ['mais_recente', 'mais_antigo'], true)) {
            $ordem = 'mais_recente';
        }

        if (!in_array($estadoFiltro, ['todos', 'pendente', 'enviado', 'recusado'], true)) {
            $estadoFiltro = 'todos';
        }

        // Monta query base apenas com encomendas do proprio utilizador.
        $query = Encomenda::with('itens')
            ->where('user_id', $user->id)
            ->whereIn('estado', $estadosPermitidosNaListagem)
            ->where(function ($q) {
                // Pendentes sem checkout finalizado nao aparecem na listagem.
                $q->where('estado', '!=', 'pendente_pagamento')
                    ->orWhereNotNull('checkout_finalizado_em');
            });

        if ($estadoFiltro === 'pendente') {
            // Mostra apenas encomendas em pendente de pagamento.
            $query->where('estado', 'pendente_pagamento');
        } elseif ($estadoFiltro === 'enviado') {
            // Mostra apenas encomendas enviadas.
            $query->where('estado', 'enviado');
        } elseif ($estadoFiltro === 'recusado') {
            // Mostra apenas encomendas com pagamento recusado.
            $query->where('estado', 'pagamento_recusado');
        }

        if ($ordem === 'mais_antigo') {
            // Ordena da mais antiga para a mais recente.
            $query->orderBy('created_at');
        } else {
            // Ordenacao padrao: encomendas mais recentes primeiro.
            $query->orderByDesc('created_at');
        }

        $encomendas = $query->paginate(5)->withQueryString();

        // Mantem os filtros atuais ao navegar pelas paginas.
        return view('cidadao.encomendas.index', compact('encomendas', 'ordem', 'estadoFiltro'));
    }

    // Mostra detalhes de uma encomenda, validando propriedade do registo.
    public function show(Encomenda $encomenda): View
    {
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        // Impede acesso a encomendas de outros utilizadores.
        if ((int) $encomenda->user_id !== (int) $user->id) {
            abort(403);
        }

        // Carrega relacoes necessarias para a vista de detalhe.
        $encomenda->load('itens', 'user');

        return view('cidadao.encomendas.show', compact('encomenda'));
    }

    // Exporta a fatura da encomenda em PDF quando o estado permite emissao.
    public function exportarFaturaPdf(Encomenda $encomenda)
    {
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        // Confirma que o dono da encomenda e o utilizador autenticado.
        if ((int) $encomenda->user_id !== (int) $user->id) {
            abort(403);
        }

        // Apenas encomendas pagas ou enviadas podem gerar fatura.
        abort_unless(in_array($encomenda->estado, ['paga', 'enviado'], true), 403);

        // Garante dados completos para compor a fatura.
        $encomenda->load('itens', 'user');

        // Calcula os valores financeiros apresentados no documento.
        $subtotalProdutos = (float) $encomenda->itens->sum('subtotal');
        $valorSemIva = $subtotalProdutos / 1.06;
        $valorIva = $subtotalProdutos - $valorSemIva;
        $portes = $subtotalProdutos < 50 ? 1.99 : 0.0;
        $descontoValor = (float) ($encomenda->valor_desconto ?? 0);
        $total = (float) $encomenda->total;

        // Renderiza o template HTML da fatura para PDF A4.
        $pdf = Pdf::loadView('cidadao.encomendas.fatura-pdf', [
            'encomenda' => $encomenda,
            'subtotalProdutos' => $subtotalProdutos,
            'valorSemIva' => $valorSemIva,
            'valorIva' => $valorIva,
            'portes' => $portes,
            'descontoValor' => $descontoValor,
            'total' => $total,
        ])->setPaper('a4');

        // Devolve download com nome de ficheiro identificando a encomenda.
        return $pdf->download('fatura-encomenda-' . $encomenda->id . '.pdf');
    }
}
