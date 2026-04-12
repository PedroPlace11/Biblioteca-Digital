<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Encomenda;
use App\Notifications\EncomendaPagamentoAtualizadoNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class EncomendaController extends Controller
{
    // Lista encomendas no painel admin com filtros por estado e pesquisa textual.
    public function index(Request $request): View
    {
        // Le parametros de pesquisa e estado vindos da query string.
        $pesquisa = trim((string) $request->query('q', ''));
        $estadoFiltro = trim((string) $request->query('estado', 'todos'));

        // Contadores para os cards/resumo exibidos na interface administrativa.
        $contagemEnviadas = Encomenda::query()
            ->where('estado', 'enviado')
            ->count();

        $contagemPendentes = Encomenda::query()
            ->where('estado', 'pendente_pagamento')
            ->whereNotNull('checkout_finalizado_em')
            ->count();

        $contagemRecusadas = Encomenda::query()
            ->where('estado', 'pagamento_recusado')
            ->count();

        if (!in_array($estadoFiltro, ['todos', 'pendente', 'enviado', 'recusado'], true)) {
            $estadoFiltro = 'todos';
        }

        // Lista apenas encomendas relevantes para gestao administrativa.
        $query = Encomenda::with(['user', 'itens'])
            ->whereIn('estado', ['pendente_pagamento', 'enviado', 'pagamento_recusado'])
            ->where(function ($q) {
                $q->where('estado', '!=', 'pendente_pagamento')
                    ->orWhereNotNull('checkout_finalizado_em');
            })
            ->orderByDesc('created_at');

        if ($estadoFiltro === 'pendente') {
            // Restringe listagem para pendentes de pagamento.
            $query->where('estado', 'pendente_pagamento');
        } elseif ($estadoFiltro === 'enviado') {
            // Restringe listagem para encomendas ja enviadas.
            $query->where('estado', 'enviado');
        } elseif ($estadoFiltro === 'recusado') {
            // Restringe listagem para pagamentos recusados.
            $query->where('estado', 'pagamento_recusado');
        }

        if ($pesquisa !== '') {
            $pesquisaNumeroLeitorSeq = null;

            // Aceita formato "L123" ou "123" para procurar pelo numero sequencial do leitor.
            if (preg_match('/^\s*L?\s*0*(\d+)\s*$/i', $pesquisa, $matchNumeroLeitor)) {
                $pesquisaNumeroLeitorSeq = (int) $matchNumeroLeitor[1];
            }

            $query->where(function ($q) use ($pesquisa, $pesquisaNumeroLeitorSeq) {
                // Pesquisa por nome/email do utilizador e por id da encomenda.
                $q->whereHas('user', function ($u) use ($pesquisa) {
                    $u->where('name', 'like', "%{$pesquisa}%")
                        ->orWhere('email', 'like', "%{$pesquisa}%");
                })
                ->orWhere('id', $pesquisa);

                if (!is_null($pesquisaNumeroLeitorSeq) && $pesquisaNumeroLeitorSeq > 0) {
                    $q->orWhereHas('user', function ($u) use ($pesquisaNumeroLeitorSeq) {
                        $u->where('numero_leitor_seq', $pesquisaNumeroLeitorSeq);
                    });
                }
            });
        }

        $encomendas = $query->paginate(5)->withQueryString();

        // Mantem filtros e pesquisa ativos ao navegar entre paginas.
        return view('admin.encomendas.index', compact(
            'encomendas',
            'pesquisa',
            'estadoFiltro',
            'contagemEnviadas',
            'contagemPendentes',
            'contagemRecusadas'
        ));
    }

    // Mostra detalhes da encomenda e respetivos itens para gestao admin.
    public function show(Encomenda $encomenda): View
    {
        $encomenda->load(['user', 'itens']);

        return view('admin.encomendas.show', compact('encomenda'));
    }

    public function atualizarPagamento(Request $request, Encomenda $encomenda): RedirectResponse
    {
        // Valida as acoes aceitas pela interface administrativa.
        $dados = $request->validate([
            'acao' => ['required', 'in:enviar,pendente,recusado,aprovar,recusar'],
        ]);

        $acao = $dados['acao'];

        // Normaliza acao da interface para os estados persistidos da encomenda.

        if ($acao === 'aprovar') {
            $acao = 'enviar';
        }

        if ($acao === 'recusar') {
            $acao = 'recusado';
        }

        $admin = Auth::user();
        if (!$admin) {
            // Impede alteracoes sem sessao administrativa autenticada.
            abort(403);
        }

        // Utilizador associado a encomenda que recebera notificacoes.
        $cidadao = $encomenda->user()->first();

        if ($acao === 'enviar') {
            // Ao enviar, garante rastreio e data de pagamento.
            $encomenda->update([
                'estado' => 'enviado',
                'pago_em' => $encomenda->pago_em ?? now(),
                'transportadora' => 'CTT',
                'numero_rastreio' => $encomenda->numero_rastreio ?: $this->gerarNumeroRastreioCtt(),
            ]);

            if ($cidadao) {
                // Guarda notificacao interna para consulta no painel do utilizador.
                Notification::send($cidadao, new EncomendaPagamentoAtualizadoNotification($encomenda, $admin, 'enviado', ['database']));

                try {
                    // Tenta envio por email sem bloquear o fluxo principal em caso de falha.
                    Notification::send($cidadao, new EncomendaPagamentoAtualizadoNotification($encomenda, $admin, 'enviado', ['mail']));
                } catch (\Throwable $e) {
                    Log::warning('Falha ao enviar email de estado enviado', [
                        'encomenda_id' => $encomenda->id,
                        'user_id' => $cidadao->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return redirect()
                ->route('admin.encomendas.show', $encomenda)
                ->with('popup_success', 'Encomenda marcada como enviada e rastreio CTT atualizado.');
        }

        if ($acao === 'pendente') {
            // Reverte para pendente e limpa dados de expedicao.
            $encomenda->update([
                'estado' => 'pendente_pagamento',
                'transportadora' => null,
                'numero_rastreio' => null,
            ]);

            if ($cidadao) {
                // Notifica estado pendente na base de dados.
                Notification::send($cidadao, new EncomendaPagamentoAtualizadoNotification($encomenda, $admin, 'pendente', ['database']));

                try {
                    // Notifica estado pendente por email.
                    Notification::send($cidadao, new EncomendaPagamentoAtualizadoNotification($encomenda, $admin, 'pendente', ['mail']));
                } catch (\Throwable $e) {
                    Log::warning('Falha ao enviar email de estado pendente', [
                        'encomenda_id' => $encomenda->id,
                        'user_id' => $cidadao->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return redirect()
                ->route('admin.encomendas.show', $encomenda)
                ->with('popup_info', 'Encomenda marcada como pendente.');
        }

        // Fluxo default: qualquer outra acao valida resulta em pagamento recusado.
        $encomenda->update([
            'estado' => 'pagamento_recusado',
            'transportadora' => null,
            'numero_rastreio' => null,
        ]);

        if ($cidadao) {
            // Regista notificacao interna de pagamento recusado.
            Notification::send($cidadao, new EncomendaPagamentoAtualizadoNotification($encomenda, $admin, 'recusado', ['database']));

            try {
                // Tenta enviar email de recusacao, logando erro quando necessario.
                Notification::send($cidadao, new EncomendaPagamentoAtualizadoNotification($encomenda, $admin, 'recusado', ['mail']));
            } catch (\Throwable $e) {
                Log::warning('Falha ao enviar email de estado recusado', [
                    'encomenda_id' => $encomenda->id,
                    'user_id' => $cidadao->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()
            ->route('admin.encomendas.show', $encomenda)
            ->with('popup_info', 'Pagamento recusado com sucesso.');
    }

    // Gera identificador CTT pseudo-aleatorio para rastreamento de expedicao.
    private function gerarNumeroRastreioCtt(): string
    {
        // Gera codigo unico para evitar colisao de rastreio entre encomendas.
        do {
            $codigo = 'CTT'.strtoupper(Str::random(12));
        } while (Encomenda::where('numero_rastreio', $codigo)->exists());

        return $codigo;
    }
}
