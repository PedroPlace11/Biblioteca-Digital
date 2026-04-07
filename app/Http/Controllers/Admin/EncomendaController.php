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
    public function index(Request $request): View
    {
        $estado = (string) $request->query('estado', 'todas');
        $pesquisa = trim((string) $request->query('q', ''));

        if (!in_array($estado, ['todas', 'pendente_pagamento', 'paga', 'enviado', 'pagamento_recusado'], true)) {
            $estado = 'todas';
        }

        $query = Encomenda::with(['user', 'itens'])
            ->orderByDesc('created_at');

        if ($estado !== 'todas') {
            $query->where('estado', $estado);
        }

        if ($pesquisa !== '') {
            $pesquisaNumeroLeitorSeq = null;

            if (preg_match('/^\s*L?\s*0*(\d+)\s*$/i', $pesquisa, $matchNumeroLeitor)) {
                $pesquisaNumeroLeitorSeq = (int) $matchNumeroLeitor[1];
            }

            $query->where(function ($q) use ($pesquisa, $pesquisaNumeroLeitorSeq) {
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

        return view('admin.encomendas.index', compact('encomendas', 'estado', 'pesquisa'));
    }

    public function show(Encomenda $encomenda): View
    {
        $encomenda->load(['user', 'itens']);

        return view('admin.encomendas.show', compact('encomenda'));
    }

    public function atualizarPagamento(Request $request, Encomenda $encomenda): RedirectResponse
    {
        $dados = $request->validate([
            'acao' => ['required', 'in:enviar,pendente,recusado,aprovar,recusar'],
        ]);

        $acao = $dados['acao'];

        if ($acao === 'aprovar') {
            $acao = 'enviar';
        }

        if ($acao === 'recusar') {
            $acao = 'recusado';
        }

        $admin = Auth::user();
        if (!$admin) {
            abort(403);
        }

        $cidadao = $encomenda->user()->first();

        if ($acao === 'enviar') {
            $encomenda->update([
                'estado' => 'enviado',
                'pago_em' => $encomenda->pago_em ?? now(),
                'transportadora' => 'CTT',
                'numero_rastreio' => $encomenda->numero_rastreio ?: $this->gerarNumeroRastreioCtt(),
            ]);

            if ($cidadao) {
                Notification::send($cidadao, new EncomendaPagamentoAtualizadoNotification($encomenda, $admin, 'enviado', ['database']));

                try {
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
            $encomenda->update([
                'estado' => 'pendente_pagamento',
                'transportadora' => null,
                'numero_rastreio' => null,
            ]);

            if ($cidadao) {
                Notification::send($cidadao, new EncomendaPagamentoAtualizadoNotification($encomenda, $admin, 'pendente', ['database']));

                try {
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

        $encomenda->update([
            'estado' => 'pagamento_recusado',
            'transportadora' => null,
            'numero_rastreio' => null,
        ]);

        if ($cidadao) {
            Notification::send($cidadao, new EncomendaPagamentoAtualizadoNotification($encomenda, $admin, 'recusado', ['database']));

            try {
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

    private function gerarNumeroRastreioCtt(): string
    {
        do {
            $codigo = 'CTT'.strtoupper(Str::random(12));
        } while (Encomenda::where('numero_rastreio', $codigo)->exists());

        return $codigo;
    }
}
