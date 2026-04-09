<?php

namespace App\Http\Controllers;

use App\Models\Carrinho;
use App\Models\Encomenda;
use App\Models\Morada;
use App\Models\User;
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
    private const CODIGO_PROMOCIONAL = 'PPLACE20';
    private const DESCONTO_PROMOCIONAL_PERCENTUAL = 20;

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

        $subtotal = (float) $itens->sum(fn ($item) => $item->subtotal);
        $quantidadeItens = (int) $itens->sum('quantidade');
        $promocaoAtiva = $this->obterPromocaoValidaDaSessao();
        $totais = $this->calcularTotaisComPromocao($subtotal, $quantidadeItens, $promocaoAtiva);
        $total = $totais['total'];
        $moradas = Morada::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->get();
        $dadosMorada = session('checkout.morada', []);
        $preenchimentoManual = $request->has('morada_id') && trim((string) $request->query('morada_id')) === '';
        $moradaSelecionadaId = (int) $request->query('morada_id', 0);
        $moradaSessaoId = (int) session('checkout.morada_id', 0);

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
            $moradaPadrao = null;

            if ($moradaSessaoId > 0) {
                $moradaPadrao = $moradas->firstWhere('id', $moradaSessaoId);
            }

            if (!$moradaPadrao) {
                $moradaPadrao = $moradas->first(function (Morada $morada) {
                    $titulo = mb_strtolower(trim((string) ($morada->titulo ?? '')));

                    return in_array($titulo, ['casa 1', 'casa1'], true);
                });
            }

            if (!$moradaPadrao) {
                $moradaPadrao = $moradas->first();
            }

            $moradaSelecionadaId = (int) $moradaPadrao->id;
            $dadosMorada = $this->converterMoradaParaCheckout($moradaPadrao);
        }

        return view('checkout.morada', compact('itens', 'total', 'totais', 'promocaoAtiva', 'dadosMorada', 'moradas', 'moradaSelecionadaId', 'preenchimentoManual'));
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

    public function atualizarCodigoPromocional(Request $request): RedirectResponse
    {
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        $dados = $request->validate([
            'codigo_promocional' => ['nullable', 'string', 'max:40'],
        ]);

        $codigo = strtoupper(trim((string) ($dados['codigo_promocional'] ?? '')));

        if ($codigo === '') {
            session()->forget('checkout.promocao');

            return back();
        }

        if ($codigo !== self::CODIGO_PROMOCIONAL) {
            return back()
                ->withInput()
                ->withErrors([
                    'codigo_promocional' => 'Código promocional inválido.',
                ]);
        }

        session([
            'checkout.promocao' => [
                'codigo' => self::CODIGO_PROMOCIONAL,
                'percentual' => self::DESCONTO_PROMOCIONAL_PERCENTUAL,
            ],
        ]);

        return back();
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

        $subtotal = (float) $itens->sum(fn ($item) => $item->subtotal);
        $quantidadeItens = (int) $itens->sum('quantidade');
        $promocaoAtiva = $this->obterPromocaoValidaDaSessao();
        $totais = $this->calcularTotaisComPromocao($subtotal, $quantidadeItens, $promocaoAtiva);
        $total = $totais['total'];
        $publishableKey = trim((string) config('services.stripe.key'));

        if ($publishableKey === '' || !str_starts_with($publishableKey, 'pk_')) {
            return redirect()->route('checkout.pagamento')->with('popup_info', 'Stripe não configurado corretamente. Defina STRIPE_KEY com chave pública válida.');
        }

        $encomenda = $this->obterOuCriarEncomendaCheckout($user, $itens, $dadosMorada, $totais, $promocaoAtiva);
        $clientSecret = $this->criarPaymentIntentCheckout($user, $encomenda, $total);

        if ($clientSecret === '') {
            return redirect()->route('checkout.pagamento')->with('popup_info', 'Não foi possível preparar o pagamento.');
        }

        session([
            'checkout.encomenda_id' => $encomenda->id,
        ]);

        return view('checkout.pagamento', compact('itens', 'total', 'totais', 'promocaoAtiva', 'dadosMorada', 'clientSecret', 'publishableKey'));
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
        $subtotal = (float) $itens->sum(fn ($item) => $item->subtotal);
        $quantidadeItens = (int) $itens->sum('quantidade');
        $promocaoAtiva = $this->obterPromocaoValidaDaSessao();
        $totais = $this->calcularTotaisComPromocao($subtotal, $quantidadeItens, $promocaoAtiva);
        $fatorDesconto = 1 - ($totais['desconto_percentual'] / 100);

        foreach ($itens as $item) {
            $precoUnitario = (float) $item->preco_unitario;

            if ($precoUnitario <= 0) {
                return redirect()->route('checkout.pagamento')->with('popup_info', 'Existem livros no carrinho com preço inválido.');
            }

            $nomeLivro = (string) ($item->livro?->nome ?? 'Livro removido');
            $autores = trim((string) $item->livro?->autores?->pluck('nome')->join(', '));
            $descricaoLivro = $autores !== '' ? ('Autor(es): ' . $autores) : null;
            $imagemLivro = null;

            if (!empty($item->livro?->imagem_capa)) {
                $imagemLivro = asset($item->livro->imagem_capa);
            }

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $nomeLivro,
                        'description' => $descricaoLivro,
                        'image' => $imagemLivro,
                    ],
                    'unit_amount' => (int) round(($precoUnitario * $fatorDesconto) * 100),
                ],
                'quantity' => (int) $item->quantidade,
            ];
        }

        $portes = $totais['portes'];
        $total = $totais['total'];

        if ($portes > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Portes',
                        'description' => 'Envio standard',
                    ],
                    'unit_amount' => 199,
                ],
                'quantity' => 1,
            ];
        }

        $encomenda = DB::transaction(function () use ($user, $dadosMorada, $itens, $total, $totais, $promocaoAtiva) {
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
                'codigo_promocional' => $promocaoAtiva['codigo'] ?? null,
                'desconto_percentual' => $totais['desconto_percentual'],
                'valor_desconto' => $totais['desconto_valor'],
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
            'locale' => 'pt',
            'submit_type' => 'pay',
            'customer_email' => (string) $user->email,
            'metadata[encomenda_id]' => (string) $encomenda->id,
            'metadata[user_id]' => (string) $user->id,
        ];

        foreach ($lineItems as $index => $lineItem) {
            $payload["line_items[{$index}][quantity]"] = $lineItem['quantity'];
            $payload["line_items[{$index}][price_data][currency]"] = $lineItem['price_data']['currency'];
            $payload["line_items[{$index}][price_data][unit_amount]"] = $lineItem['price_data']['unit_amount'];
            $payload["line_items[{$index}][price_data][product_data][name]"] = $lineItem['price_data']['product_data']['name'];

            if (!empty($lineItem['price_data']['product_data']['description'])) {
                $payload["line_items[{$index}][price_data][product_data][description]"] = $lineItem['price_data']['product_data']['description'];
            }

            if (!empty($lineItem['price_data']['product_data']['image'])) {
                $payload["line_items[{$index}][price_data][product_data][images][0]"] = $lineItem['price_data']['product_data']['image'];
            }
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
        $paymentIntentId = (string) $request->query('payment_intent', '');

        if ($sessionId === '' && $paymentIntentId === '') {
            return redirect()->route('carrinho.index')->with('popup_info', 'Pagamento inválido.');
        }

        $chaveStripe = trim((string) config('services.stripe.secret'));

        if ($chaveStripe === '') {
            return redirect()->route('carrinho.index')->with('popup_info', 'Stripe não configurado.');
        }

        $session = [];
        $paymentIntent = [];

        if ($sessionId !== '') {
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
        }

        if ($paymentIntentId !== '') {
            $response = Http::withBasicAuth($chaveStripe, '')
                ->timeout(15)
                ->retry(2, 500)
                ->get("https://api.stripe.com/v1/payment_intents/{$paymentIntentId}");

            $paymentIntent = $response->successful() ? $response->json() : [];

            if (!$response->successful()) {
                Log::warning('Falha ao validar payment intent Stripe no retorno', [
                    'payment_intent_id' => $paymentIntentId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'user_id' => $user->id,
                ]);
            }
        }

        $encomenda = Encomenda::with('itens')
            ->where('user_id', $user->id)
            ->when($sessionId !== '', fn ($query) => $query->where('stripe_checkout_session_id', $sessionId))
            ->when($paymentIntentId !== '', fn ($query) => $query->where('stripe_payment_intent_id', $paymentIntentId))
            ->first();

        if (!$encomenda) {
            $encomendaId = (int) data_get($paymentIntent, 'metadata.encomenda_id', 0);

            if ($encomendaId <= 0) {
                $encomendaId = (int) data_get($session, 'metadata.encomenda_id', 0);
            }

            if ($encomendaId <= 0) {
                $encomendaId = (int) session('checkout.encomenda_id', 0);
            }

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

        $pagamentoConcluido = ($session['payment_status'] ?? null) === 'paid'
            || ($paymentIntent['status'] ?? null) === 'succeeded';

        if (!$pagamentoConcluido) {
            return redirect()->route('checkout.pagamento')->with('popup_info', 'O pagamento ainda não foi concluído.');
        }

        if ($encomenda->estado !== 'enviado') {
            $encomenda->update([
                'estado' => 'enviado',
                'stripe_payment_intent_id' => (string) ($paymentIntent['id'] ?? $session['payment_intent'] ?? $paymentIntentId),
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
        session()->forget('checkout.encomenda_id');
        session()->forget('checkout.promocao');

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

    private function obterOuCriarEncomendaCheckout(User $user, $itens, array $dadosMorada, array $totais, ?array $promocaoAtiva): Encomenda
    {
        $encomendaId = (int) session('checkout.encomenda_id', 0);

        if ($encomendaId > 0) {
            $encomendaExistente = Encomenda::query()
                ->where('id', $encomendaId)
                ->where('user_id', $user->id)
                ->first();

            if ($encomendaExistente) {
                $encomendaExistente->update([
                    'nome_destinatario' => $dadosMorada['nome_destinatario'],
                    'telemovel_destinatario' => $dadosMorada['telemovel_destinatario'],
                    'morada_linha_1' => $dadosMorada['morada_linha_1'],
                    'morada_linha_2' => $dadosMorada['morada_linha_2'] ?? null,
                    'codigo_postal' => $dadosMorada['codigo_postal'],
                    'cidade' => $dadosMorada['cidade'],
                    'pais' => $dadosMorada['pais'],
                    'total' => $totais['total'],
                    'codigo_promocional' => $promocaoAtiva['codigo'] ?? null,
                    'desconto_percentual' => $totais['desconto_percentual'],
                    'valor_desconto' => $totais['desconto_valor'],
                ]);

                $this->sincronizarItensEncomenda($encomendaExistente, $itens);

                return $encomendaExistente;
            }
        }

        $encomenda = DB::transaction(function () use ($user, $dadosMorada, $itens, $totais, $promocaoAtiva) {
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
                'total' => $totais['total'],
                'codigo_promocional' => $promocaoAtiva['codigo'] ?? null,
                'desconto_percentual' => $totais['desconto_percentual'],
                'valor_desconto' => $totais['desconto_valor'],
            ]);

            $this->sincronizarItensEncomenda($encomenda, $itens);

            return $encomenda;
        });

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

        session(['checkout.encomenda_id' => $encomenda->id]);

        return $encomenda;
    }

    private function sincronizarItensEncomenda(Encomenda $encomenda, $itens): void
    {
        $encomenda->itens()->delete();

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
    }

    private function criarPaymentIntentCheckout(User $user, Encomenda $encomenda, float $total): string
    {
        $chaveStripe = trim((string) config('services.stripe.secret'));

        if ($chaveStripe === '') {
            return '';
        }

        $response = Http::withBasicAuth($chaveStripe, '')
            ->asForm()
            ->post('https://api.stripe.com/v1/payment_intents', [
                'amount' => (int) round($total * 100),
                'currency' => 'eur',
                'automatic_payment_methods[enabled]' => 'true',
                'receipt_email' => (string) $user->email,
                'description' => 'Encomenda #' . $encomenda->id,
                'metadata[encomenda_id]' => (string) $encomenda->id,
                'metadata[user_id]' => (string) $user->id,
            ]);

        if (!$response->successful()) {
            Log::warning('Falha ao criar payment intent Stripe', [
                'status' => $response->status(),
                'body' => $response->body(),
                'encomenda_id' => $encomenda->id,
                'user_id' => $user->id,
            ]);

            return '';
        }

        $paymentIntent = $response->json();
        $paymentIntentId = (string) ($paymentIntent['id'] ?? '');
        $clientSecret = (string) ($paymentIntent['client_secret'] ?? '');

        if ($paymentIntentId === '' || $clientSecret === '') {
            Log::warning('Resposta inválida ao criar payment intent Stripe', [
                'encomenda_id' => $encomenda->id,
                'user_id' => $user->id,
                'response' => $paymentIntent,
            ]);

            return '';
        }

        $encomenda->update([
            'stripe_payment_intent_id' => $paymentIntentId,
        ]);

        session(['checkout.payment_intent_id' => $paymentIntentId]);

        return $clientSecret;
    }

    private function obterPromocaoValidaDaSessao(): ?array
    {
        $promocao = session('checkout.promocao');

        if (!is_array($promocao)) {
            return null;
        }

        $codigo = strtoupper(trim((string) ($promocao['codigo'] ?? '')));
        $percentual = (int) ($promocao['percentual'] ?? 0);

        if ($codigo !== self::CODIGO_PROMOCIONAL || $percentual !== self::DESCONTO_PROMOCIONAL_PERCENTUAL) {
            session()->forget('checkout.promocao');

            return null;
        }

        return [
            'codigo' => $codigo,
            'percentual' => $percentual,
        ];
    }

    private function calcularTotaisComPromocao(float $subtotal, int $quantidadeItens, ?array $promocaoAtiva): array
    {
        $portes = $subtotal < 50 ? 1.99 : 0.0;
        $valorSemIva = $subtotal / 1.06;
        $valorIva = $subtotal - $valorSemIva;
        $descontoPercentual = $quantidadeItens >= 2 ? (int) ($promocaoAtiva['percentual'] ?? 0) : 0;
        $descontoValor = $descontoPercentual > 0 ? round($subtotal * ($descontoPercentual / 100), 2) : 0.0;
        $total = round(max(0, $subtotal - $descontoValor + $portes), 2);

        return [
            'subtotal_com_iva' => $subtotal,
            'valor_sem_iva' => $valorSemIva,
            'valor_iva' => $valorIva,
            'portes' => $portes,
            'desconto_percentual' => $descontoPercentual,
            'desconto_valor' => $descontoValor,
            'total' => $total,
        ];
    }
}
