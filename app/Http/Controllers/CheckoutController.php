<?php

namespace App\Http\Controllers;

use App\Models\Carrinho;
use App\Models\Encomenda;
use App\Models\Morada;
use App\Notifications\EncomendaCriadaNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function morada(Request $request): View|RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        $carrinho = Carrinho::firstOrCreate(['user_id' => $user->id]);
        $itens = $carrinho->itens()->with('livro')->get();

        if ($itens->isEmpty()) {
            return redirect()->route('carrinho.index')->with('popup_info', 'Adicione livros ao carrinho antes de avançar.');
        }

        $total = $itens->sum(fn ($item) => $item->subtotal);
        $moradas = Morada::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->get();
        $dadosMorada = session('checkout.morada', []);
        $preenchimentoManual = $request->has('morada_id') && trim((string) $request->query('morada_id')) === '';
        $moradaSelecionadaId = (int) $request->query('morada_id', 0);

        if ($preenchimentoManual) {
            $dadosMorada = [];
            $moradaSelecionadaId = 0;
        } elseif ($moradaSelecionadaId > 0) {
            $moradaSelecionada = $moradas->firstWhere('id', $moradaSelecionadaId);

            if ($moradaSelecionada) {
                $dadosMorada = $this->converterMoradaParaCheckout($moradaSelecionada);
            }
        }

        if (!$preenchimentoManual && empty($dadosMorada) && $moradas->isNotEmpty()) {
            $moradaPadrao = $moradas->first();
            $moradaSelecionadaId = (int) $moradaPadrao->id;
            $dadosMorada = $this->converterMoradaParaCheckout($moradaPadrao);
        }

        return view('checkout.morada', compact('itens', 'total', 'dadosMorada', 'moradas', 'moradaSelecionadaId', 'preenchimentoManual'));
    }

    public function guardarMorada(Request $request): RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        $dados = $request->validate([
            'morada_id' => ['nullable', 'integer'],
            'titulo' => ['nullable', 'string', 'max:80'],
            'nome_destinatario' => ['required', 'string', 'max:255'],
            'telemovel_destinatario' => ['required', 'string', 'max:40'],
            'morada_linha_1' => ['required', 'string', 'max:255'],
            'morada_linha_2' => ['nullable', 'string', 'max:255'],
            'codigo_postal' => ['required', 'string', 'max:20'],
            'cidade' => ['required', 'string', 'max:120'],
            'pais' => ['required', 'string', 'size:2'],
        ]);

        $dados['pais'] = strtoupper($dados['pais']);
        $moradaId = (int) ($dados['morada_id'] ?? 0);

        unset($dados['morada_id']);

        $moradaGuardada = null;

        if ($moradaId > 0) {
            $moradaGuardada = Morada::query()
                ->where('user_id', $user->id)
                ->where('id', $moradaId)
                ->first();

            if ($moradaGuardada) {
                $moradaGuardada->update($dados);
            }
        }

        if (!$moradaGuardada) {
            $moradaGuardada = Morada::query()->where([
                'user_id' => $user->id,
                'titulo' => $dados['titulo'] ?? null,
                'nome_destinatario' => $dados['nome_destinatario'],
                'telemovel_destinatario' => $dados['telemovel_destinatario'],
                'morada_linha_1' => $dados['morada_linha_1'],
                'morada_linha_2' => $dados['morada_linha_2'] ?? null,
                'codigo_postal' => $dados['codigo_postal'],
                'cidade' => $dados['cidade'],
                'pais' => $dados['pais'],
            ])->first();

            if (!$moradaGuardada) {
                $moradaGuardada = Morada::create([
                    'user_id' => $user->id,
                    ...$dados,
                ]);
            }
        }

        session([
            'checkout.morada' => $dados,
            'checkout.morada_id' => $moradaGuardada->id,
        ]);

        return redirect()->route('checkout.pagamento');
    }

    public function pagamento(): View|RedirectResponse
    {
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        $carrinho = Carrinho::firstOrCreate(['user_id' => $user->id]);
        $itens = $carrinho->itens()->with('livro')->get();

        if ($itens->isEmpty()) {
            return redirect()->route('carrinho.index')->with('popup_info', 'O carrinho está vazio.');
        }

        $dadosMorada = session('checkout.morada');

        if (!$dadosMorada) {
            return redirect()->route('checkout.morada')->with('popup_info', 'Preencha a morada de entrega antes do pagamento.');
        }

        $total = $itens->sum(fn ($item) => $item->subtotal);

        return view('checkout.pagamento', compact('itens', 'total', 'dadosMorada'));
    }

    public function criarSessaoStripe(): RedirectResponse
    {
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        $chaveStripe = trim((string) config('services.stripe.secret'));

        if ($chaveStripe === '') {
            return redirect()->route('checkout.pagamento')->with('popup_info', 'Stripe não configurado. Defina STRIPE_SECRET na configuração.');
        }

        $carrinho = Carrinho::firstOrCreate(['user_id' => $user->id]);
        $itens = $carrinho->itens()->with('livro')->get();

        if ($itens->isEmpty()) {
            return redirect()->route('carrinho.index')->with('popup_info', 'O carrinho está vazio.');
        }

        $dadosMorada = session('checkout.morada');

        if (!$dadosMorada) {
            return redirect()->route('checkout.morada')->with('popup_info', 'Preencha a morada de entrega antes do pagamento.');
        }

        $lineItems = [];
        $total = 0;

        foreach ($itens as $item) {
            $precoUnitario = (float) $item->preco_unitario;

            if ($precoUnitario <= 0) {
                return redirect()->route('checkout.pagamento')->with('popup_info', 'Existem livros no carrinho com preço inválido.');
            }

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $item->livro?->nome ?? 'Livro removido',
                    ],
                    'unit_amount' => (int) round($precoUnitario * 100),
                ],
                'quantity' => (int) $item->quantidade,
            ];

            $total += $precoUnitario * (int) $item->quantidade;
        }

        $encomenda = DB::transaction(function () use ($user, $dadosMorada, $itens, $total) {
            $encomenda = Encomenda::create([
                'user_id' => $user->id,
                'estado' => 'pendente_pagamento',
                'nome_destinatario' => $dadosMorada['nome_destinatario'],
                'telemovel_destinatario' => $dadosMorada['telemovel_destinatario'],
                'morada_linha_1' => $dadosMorada['morada_linha_1'],
                'morada_linha_2' => $dadosMorada['morada_linha_2'] ?? null,
                'codigo_postal' => $dadosMorada['codigo_postal'],
                'cidade' => $dadosMorada['cidade'],
                'pais' => $dadosMorada['pais'],
                'total' => $total,
            ]);

            foreach ($itens as $item) {
                $quantidade = (int) $item->quantidade;
                $preco = (float) $item->preco_unitario;

                $encomenda->itens()->create([
                    'livro_id' => $item->livro_id,
                    'livro_nome' => $item->livro?->nome ?? 'Livro removido',
                    'livro_isbn' => $item->livro?->isbn,
                    'quantidade' => $quantidade,
                    'preco_unitario' => $preco,
                    'subtotal' => $preco * $quantidade,
                ]);
            }

            return $encomenda;
        });

        $payload = [
            'mode' => 'payment',
            'success_url' => route('checkout.sucesso') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.pagamento'),
            'metadata[encomenda_id]' => (string) $encomenda->id,
            'metadata[user_id]' => (string) $user->id,
        ];

        foreach ($lineItems as $index => $lineItem) {
            $payload["line_items[{$index}][quantity]"] = $lineItem['quantity'];
            $payload["line_items[{$index}][price_data][currency]"] = $lineItem['price_data']['currency'];
            $payload["line_items[{$index}][price_data][unit_amount]"] = $lineItem['price_data']['unit_amount'];
            $payload["line_items[{$index}][price_data][product_data][name]"] = $lineItem['price_data']['product_data']['name'];
        }

        $response = Http::withBasicAuth($chaveStripe, '')
            ->asForm()
            ->post('https://api.stripe.com/v1/checkout/sessions', $payload);

        if (!$response->successful()) {
            $erroStripe = (string) data_get($response->json(), 'error.message', '');

            Log::warning('Falha ao criar sessão Stripe', [
                'status' => $response->status(),
                'stripe_error' => $erroStripe,
                'encomenda_id' => $encomenda->id,
                'user_id' => $user->id,
            ]);

            $mensagem = 'Não foi possível criar sessão de pagamento Stripe.';

            if ($erroStripe !== '') {
                $mensagem .= ' ' . $erroStripe;
            }

            return redirect()->route('checkout.pagamento')->with('popup_info', $mensagem);
        }

        $session = $response->json();
        $sessionId = (string) ($session['id'] ?? '');
        $sessionUrl = (string) ($session['url'] ?? '');

        if ($sessionId === '' || $sessionUrl === '') {
            return redirect()->route('checkout.pagamento')->with('popup_info', 'Resposta inválida do Stripe ao criar sessão de pagamento.');
        }

        $encomenda->update([
            'stripe_checkout_session_id' => $sessionId,
        ]);

        $admins = \App\Models\User::query()
            ->where('role', 'admin')
            ->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new EncomendaCriadaNotification($encomenda, $user, ['database']));

            try {
                Notification::send($admins, new EncomendaCriadaNotification($encomenda, $user, ['mail']));
            } catch (\Throwable $e) {
                Log::warning('Falha ao enviar email de nova encomenda', [
                    'encomenda_id' => $encomenda->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->away($sessionUrl);
    }

    public function sucesso(Request $request): View|RedirectResponse
    {
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        $sessionId = (string) $request->query('session_id', '');

        if ($sessionId === '') {
            return redirect()->route('carrinho.index')->with('popup_info', 'Sessão de pagamento inválida.');
        }

        $chaveStripe = trim((string) config('services.stripe.secret'));

        if ($chaveStripe === '') {
            return redirect()->route('carrinho.index')->with('popup_info', 'Stripe não configurado.');
        }

        $response = Http::withBasicAuth($chaveStripe, '')
            ->timeout(15)
            ->retry(2, 500)
            ->get("https://api.stripe.com/v1/checkout/sessions/{$sessionId}");

        $session = $response->successful() ? $response->json() : [];

        if (!$response->successful()) {
            Log::warning('Falha ao validar sessão Stripe no retorno', [
                'session_id' => $sessionId,
                'status' => $response->status(),
                'body' => $response->body(),
                'user_id' => $user->id,
            ]);
        }

        $encomenda = Encomenda::with('itens')
            ->where('user_id', $user->id)
            ->where('stripe_checkout_session_id', $sessionId)
            ->first();

        if (!$encomenda) {
            $encomendaId = (int) data_get($session, 'metadata.encomenda_id', 0);

            if ($encomendaId > 0) {
                $encomenda = Encomenda::with('itens')
                    ->where('id', $encomendaId)
                    ->where('user_id', $user->id)
                    ->first();

                if ($encomenda && (string) ($encomenda->stripe_checkout_session_id ?? '') !== $sessionId) {
                    $encomenda->update([
                        'stripe_checkout_session_id' => $sessionId,
                    ]);
                }
            }
        }

        if (!$encomenda) {
            Log::warning('Retorno Stripe sem encomenda localizada', [
                'session_id' => $sessionId,
                'user_id' => $user->id,
            ]);

            return redirect()->route('carrinho.index')->with('popup_info', 'Não foi possível localizar a encomenda deste pagamento.');
        }

        if (($session['payment_status'] ?? null) === 'paid' || !$response->successful()) {
            if ($encomenda->estado !== 'enviado') {
                $encomenda->update([
                    'estado' => 'enviado',
                    'stripe_payment_intent_id' => (string) ($session['payment_intent'] ?? ''),
                    'pago_em' => now(),
                    'transportadora' => 'CTT',
                    'numero_rastreio' => $encomenda->numero_rastreio ?: $this->gerarNumeroRastreioCtt(),
                ]);
            }

            $carrinho = Carrinho::where('user_id', $user->id)->first();

            if ($carrinho) {
                $carrinho->itens()->delete();
                $carrinho->lembrete_abandono_enviado_em = null;
                $carrinho->save();
                $carrinho->touch();
            }

            session()->forget('checkout.morada');
            session()->forget('checkout.morada_id');
        }

        return view('checkout.sucesso', compact('encomenda'));
    }

    private function gerarNumeroRastreioCtt(): string
    {
        do {
            $codigo = 'CTT'.strtoupper(Str::random(12));
        } while (Encomenda::where('numero_rastreio', $codigo)->exists());

        return $codigo;
    }

    private function converterMoradaParaCheckout(Morada $morada): array
    {
        return [
            'nome_destinatario' => $morada->nome_destinatario,
            'telemovel_destinatario' => $morada->telemovel_destinatario,
            'morada_linha_1' => $morada->morada_linha_1,
            'morada_linha_2' => $morada->morada_linha_2,
            'codigo_postal' => $morada->codigo_postal,
            'cidade' => $morada->cidade,
            'pais' => $morada->pais,
        ];
    }
}
