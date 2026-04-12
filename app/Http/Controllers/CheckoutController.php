<?php

namespace App\Http\Controllers;

use App\Models\Carrinho;
use App\Models\Encomenda;
use App\Models\Morada;
use App\Models\User;
use App\Notifications\EncomendaCriadaNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    // Codigo promocional unico aceite no checkout.
    private const CODIGO_PROMOCIONAL = 'PPLACE20';
    // Percentual de desconto aplicado ao codigo promocional valido.
    private const DESCONTO_PROMOCIONAL_PERCENTUAL = 20;

    // Mostra etapa de morada do checkout e prepara dados iniciais da entrega.
    public function morada(Request $request): View|RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        // Carrinho do utilizador com itens para validar continuidade do checkout.
        $carrinho = Carrinho::firstOrCreate(['user_id' => $user->id]);
        $itens = $carrinho->itens()->with('livro')->get();

        // Impede avancar no checkout quando carrinho esta vazio.
        if ($itens->isEmpty()) {
            return redirect()->route('carrinho.index')->with('popup_info', 'Adicione livros ao carrinho antes de avançar.');
        }

        // Calcula totais e carrega dados persistidos de promocao/faturacao.
        $subtotal = (float) $itens->sum(fn ($item) => $item->subtotal);
        $quantidadeItens = (int) $itens->sum('quantidade');
        $promocaoAtiva = $this->obterPromocaoValidaDaSessao();
        $dadosFaturacao = $this->normalizarDadosFaturacao((array) session('checkout.faturacao', []));
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

        // Quando pedido manual, limpa selecao de morada pre-existente.
        if ($preenchimentoManual) {
            $dadosMorada = [];
            $moradaSelecionadaId = 0;
            session()->forget('checkout.morada_id');
        } elseif ($moradaSelecionadaId > 0) {
            // Seleciona e sincroniza em sessao a morada escolhida pelo utilizador.
            $moradaSelecionada = $moradas->firstWhere('id', $moradaSelecionadaId);

            if ($moradaSelecionada) {
                $dadosMorada = $this->converterMoradaParaCheckout($moradaSelecionada);
                session([
                    'checkout.morada' => $dadosMorada,
                    'checkout.morada_id' => $moradaSelecionada->id,
                ]);
            }
        }

        // Define morada padrao quando ainda nao existe uma morada em sessao.
        if (!$preenchimentoManual && empty($dadosMorada) && $moradas->isNotEmpty()) {
            $moradaPadrao = null;

            if ($moradaSessaoId > 0) {
                $moradaPadrao = $moradas->firstWhere('id', $moradaSessaoId);
            }

            if (!$moradaPadrao) {
                // Prioriza titulo Casa 1 quando existente.
                $moradaPadrao = $moradas->first(function (Morada $morada) {
                    $titulo = mb_strtolower(trim((string) ($morada->titulo ?? '')));

                    return in_array($titulo, ['casa 1', 'casa1'], true);
                });
            }

            if (!$moradaPadrao) {
                // Fallback para a primeira morada disponivel.
                $moradaPadrao = $moradas->first();
            }

            $moradaSelecionadaId = (int) $moradaPadrao->id;
            $dadosMorada = $this->converterMoradaParaCheckout($moradaPadrao);
            session([
                'checkout.morada' => $dadosMorada,
                'checkout.morada_id' => $moradaPadrao->id,
            ]);
        }

        $moradaSelecionada = $moradaSelecionadaId > 0
            ? $moradas->firstWhere('id', $moradaSelecionadaId)
            : null;

        // Renderiza etapa de morada com totais e dados de apoio ao formulario.
        return view('checkout.morada', compact('itens', 'total', 'totais', 'promocaoAtiva', 'dadosMorada', 'moradas', 'moradaSelecionadaId', 'preenchimentoManual', 'moradaSelecionada', 'dadosFaturacao'));
    }

    // Valida e guarda morada de entrega, atualizando sessao de checkout.
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

        // Normaliza pais para formato ISO em maiusculas.
        $dados['pais'] = strtoupper($dados['pais']);
        $moradaId = (int) ($dados['morada_id'] ?? 0);

        unset($dados['morada_id']);

        $moradaGuardada = null;

        // Se recebeu id, tenta atualizar morada existente do utilizador.
        if ($moradaId > 0) {
            $moradaGuardada = Morada::query()
                ->where('user_id', $user->id)
                ->where('id', $moradaId)
                ->first();

            if ($moradaGuardada) {
                $moradaGuardada->update($dados);
            }
        }

        // Caso nao encontre morada para atualizar, procura duplicado ou cria nova.
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
                // Cria novo registo de morada quando nao existe equivalente.
                $moradaGuardada = Morada::create([
                    'user_id' => $user->id,
                    ...$dados,
                ]);
            }
        }

        // Persiste morada selecionada para as proximas etapas do checkout.
        session([
            'checkout.morada' => $dados,
            'checkout.morada_id' => $moradaGuardada->id,
        ]);

        return redirect()->route('checkout.pagamento');
    }

    // Atualiza codigo promocional em sessao para recalculo de totais.
    public function atualizarCodigoPromocional(Request $request): RedirectResponse
    {
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        $dados = $request->validate([
            'codigo_promocional' => ['nullable', 'string', 'max:40'],
        ]);

        $codigo = strtoupper(trim((string) ($dados['codigo_promocional'] ?? '')));

        // Limpa promocao quando campo chega vazio.
        if ($codigo === '') {
            session()->forget('checkout.promocao');

            return back();
        }

        // Rejeita codigos nao reconhecidos com erro de validacao.
        if ($codigo !== self::CODIGO_PROMOCIONAL) {
            return back()
                ->withInput()
                ->withErrors([
                    'codigo_promocional' => 'Código promocional inválido.',
                ]);
        }

        // Guarda promocao valida para uso nas etapas seguintes.
        session([
            'checkout.promocao' => [
                'codigo' => self::CODIGO_PROMOCIONAL,
                'percentual' => self::DESCONTO_PROMOCIONAL_PERCENTUAL,
            ],
        ]);

        return back();
    }

    // Mostra etapa de pagamento e prepara Payment Intent para Stripe Elements.
    public function pagamento(): View|RedirectResponse
    {
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        // Necessita carrinho com itens e morada preenchida para prosseguir.
        $carrinho = Carrinho::firstOrCreate(['user_id' => $user->id]);
        $itens = $carrinho->itens()->with('livro')->get();

        if ($itens->isEmpty()) {
            return redirect()->route('carrinho.index')->with('popup_info', 'O carrinho está vazio.');
        }

        $dadosMorada = session('checkout.morada');

        if (!$dadosMorada) {
            return redirect()->route('checkout.morada')->with('popup_info', 'Preencha a morada de entrega antes do pagamento.');
        }

        // Recalcula totais e verifica chave publica Stripe configurada.
        $subtotal = (float) $itens->sum(fn ($item) => $item->subtotal);
        $quantidadeItens = (int) $itens->sum('quantidade');
        $promocaoAtiva = $this->obterPromocaoValidaDaSessao();
        $dadosFaturacao = $this->normalizarDadosFaturacao((array) session('checkout.faturacao', []));
        $totais = $this->calcularTotaisComPromocao($subtotal, $quantidadeItens, $promocaoAtiva);
        $total = $totais['total'];
        $publishableKey = trim((string) config('services.stripe.key'));

        if ($publishableKey === '' || !str_starts_with($publishableKey, 'pk_')) {
            return redirect()->route('checkout.pagamento')->with('popup_info', 'Stripe não configurado corretamente. Defina STRIPE_KEY com chave pública válida.');
        }

        // Cria intent de pagamento para iniciar fluxo no frontend.
        $clientSecret = $this->criarPaymentIntentCheckout($user, $total);

        if ($clientSecret === '') {
            return redirect()->route('checkout.pagamento')->with('popup_info', 'Não foi possível preparar o pagamento.');
        }

        // Renderiza tela de pagamento com credenciais publicas e client secret.
        return view('checkout.pagamento', compact('itens', 'total', 'totais', 'promocaoAtiva', 'dadosMorada', 'dadosFaturacao', 'clientSecret', 'publishableKey'));
    }

    // Atualiza dados de faturacao (NIF/nome) na sessao de checkout.
    public function atualizarDadosFaturacao(Request $request): RedirectResponse
    {
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        $dados = $request->validate([
            'morada_id' => ['nullable', 'integer'],
            'fatura_com_nif' => ['nullable', 'boolean'],
            'fatura_nif' => ['nullable', 'digits:9'],
            'fatura_nome' => ['nullable', 'string', 'max:255'],
        ]);

        // Normaliza payload e valida obrigatoriedade quando fatura com NIF ativa.
        $moradaSelecionadaId = (int) ($dados['morada_id'] ?? session('checkout.morada_id', 0));
        $dadosFaturacao = $this->normalizarDadosFaturacao($dados);

        if ($dadosFaturacao['fatura_com_nif']) {
            if ($dadosFaturacao['fatura_nif'] === '') {
                return back()->withErrors(['fatura_nif' => 'Indique o NIF para faturação.'])->withInput();
            }

            if ($dadosFaturacao['fatura_nome'] === '') {
                return back()->withErrors(['fatura_nome' => 'Indique o nome para faturação.'])->withInput();
            }
        }

        // Persiste faturacao para uso na criacao da encomenda.
        session(['checkout.faturacao' => $dadosFaturacao]);

        // Se morada de sessao estiver vazia, tenta reconstruir a partir do id selecionado.
        if (empty(session('checkout.morada')) && $moradaSelecionadaId > 0) {
            $moradaSelecionada = Morada::query()
                ->where('user_id', $user->id)
                ->where('id', $moradaSelecionadaId)
                ->first();

            if ($moradaSelecionada) {
                session([
                    'checkout.morada' => $this->converterMoradaParaCheckout($moradaSelecionada),
                    'checkout.morada_id' => $moradaSelecionada->id,
                ]);
            }
        }

        return back();
    }

    // Cria sessao Stripe Checkout hospedada e redireciona o utilizador.
    public function criarSessaoStripe(): RedirectResponse
    {
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        $chaveStripe = trim((string) config('services.stripe.secret'));

        // Sem chave secreta nao e possivel comunicar com API Stripe.
        if ($chaveStripe === '') {
            return redirect()->route('checkout.pagamento')->with('popup_info', 'Stripe não configurado. Defina STRIPE_SECRET na configuração.');
        }

        // Garante contexto minimo do checkout antes de montar line items.
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
        $dadosFaturacao = $this->normalizarDadosFaturacao((array) session('checkout.faturacao', []));
        $totais = $this->calcularTotaisComPromocao($subtotal, $quantidadeItens, $promocaoAtiva);
        $fatorDesconto = 1 - ($totais['desconto_percentual'] / 100);

        // Converte cada item do carrinho para formato aceito pelo Stripe.
        foreach ($itens as $item) {
            $precoUnitario = (float) $item->preco_unitario;

            // Interrompe quando encontrar item com preco invalido.
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

            // Estrutura de item para Checkout Session, incluindo metadados visuais.
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

        // Acrescenta linha de portes quando aplicavel.
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

        // Cria encomenda em estado de checkout e replica itens em transacao atomica.
        $encomenda = DB::transaction(function () use ($user, $dadosMorada, $itens, $total, $totais, $promocaoAtiva, $dadosFaturacao) {
            $encomenda = Encomenda::create([
                'user_id' => $user->id,
                'estado' => 'checkout_em_progresso',
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
                'fatura_com_nif' => $dadosFaturacao['fatura_com_nif'],
                'fatura_nif' => $dadosFaturacao['fatura_nif'] ?: null,
                'fatura_nome' => $dadosFaturacao['fatura_nome'] ?: null,
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

        // Monta payload de Checkout Session com callbacks de sucesso/cancelamento.
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

        // Traduz cada line item para sintaxe de campos aninhados esperada pela API.
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

        // Chamada HTTP para criacao de sessao Stripe.
        $response = Http::withBasicAuth($chaveStripe, '')
            ->asForm()
            ->post('https://api.stripe.com/v1/checkout/sessions', $payload);

        // Em falha, regista detalhe tecnico e informa utilizador.
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

        // Valida retorno minimo necessario para redirecionar ao checkout hospedado.
        $session = $response->json();
        $sessionId = (string) ($session['id'] ?? '');
        $sessionUrl = (string) ($session['url'] ?? '');

        if ($sessionId === '' || $sessionUrl === '') {
            return redirect()->route('checkout.pagamento')->with('popup_info', 'Resposta inválida do Stripe ao criar sessão de pagamento.');
        }

        // Guarda id da sessao Stripe na encomenda para reconciliacao posterior.
        $encomenda->update([
            'stripe_checkout_session_id' => $sessionId,
        ]);

        return redirect()->away($sessionUrl);
    }

    // Processa retorno de sucesso/callback e finaliza estado da encomenda.
    public function sucesso(Request $request): View|RedirectResponse
    {
        $user = Auth::user();

        abort_unless($user && $user->role === 'cidadao', 403);

        $sessionId = (string) $request->query('session_id', '');
        $paymentIntentId = (string) $request->query('payment_intent', '');

        // Exige identificador de sessao ou payment intent para validar pagamento.
        if ($sessionId === '' && $paymentIntentId === '') {
            return redirect()->route('carrinho.index')->with('popup_info', 'Pagamento inválido.');
        }

        $chaveStripe = trim((string) config('services.stripe.secret'));

        if ($chaveStripe === '') {
            return redirect()->route('carrinho.index')->with('popup_info', 'Stripe não configurado.');
        }

        $session = [];
        $paymentIntent = [];

        // Consulta API Stripe para obter detalhes da sessao quando disponivel.
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

        // Consulta API Stripe para obter estado do payment intent quando indicado.
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

        // Tenta localizar encomenda por ids Stripe associados ao utilizador atual.
        $encomenda = Encomenda::with('itens')
            ->where('user_id', $user->id)
            ->when($sessionId !== '', fn ($query) => $query->where('stripe_checkout_session_id', $sessionId))
            ->when($paymentIntentId !== '', fn ($query) => $query->where('stripe_payment_intent_id', $paymentIntentId))
            ->first();

        // Fallback por metadata/sessao quando nao encontra por campos Stripe diretos.
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

        // Ultimo fallback: reconstrucao de encomenda a partir da sessao de checkout.
        if (!$encomenda) {
            $carrinho = Carrinho::firstOrCreate(['user_id' => $user->id]);
            $itens = $carrinho->itens()->with('livro')->get();
            $dadosMoradaSessao = (array) session('checkout.morada', []);

            $camposMoradaObrigatorios = [
                'nome_destinatario',
                'telemovel_destinatario',
                'morada_linha_1',
                'codigo_postal',
                'cidade',
                'pais',
            ];

            $moradaValida = collect($camposMoradaObrigatorios)
                ->every(fn (string $campo) => !empty($dadosMoradaSessao[$campo]));

            // So cria fallback quando ha itens no carrinho e morada completa.
            if ($itens->isNotEmpty() && $moradaValida) {
                $subtotal = (float) $itens->sum(fn ($item) => $item->subtotal);
                $quantidadeItens = (int) $itens->sum('quantidade');
                $promocaoAtiva = $this->obterPromocaoValidaDaSessao();
                $dadosFaturacao = $this->normalizarDadosFaturacao((array) session('checkout.faturacao', []));
                $totais = $this->calcularTotaisComPromocao($subtotal, $quantidadeItens, $promocaoAtiva);

                $encomenda = $this->obterOuCriarEncomendaCheckout(
                    $user,
                    $itens,
                    $dadosMoradaSessao,
                    $totais,
                    $promocaoAtiva,
                    $dadosFaturacao
                );

                $encomenda->update([
                    'stripe_payment_intent_id' => (string) ($paymentIntent['id'] ?? $session['payment_intent'] ?? $paymentIntentId),
                ]);
            }
        }

        if (!$encomenda) {
            Log::warning('Retorno Stripe sem encomenda localizada', [
                'session_id' => $sessionId,
                'user_id' => $user->id,
            ]);

            return redirect()->route('carrinho.index')->with('popup_info', 'Não foi possível localizar a encomenda deste pagamento.');
        }

        // Determina tipo e estado do pagamento para decidir transicao de estado.
        $paymentIntentStatus = (string) ($paymentIntent['status'] ?? '');
        $paymentIntentTypes = (array) ($paymentIntent['payment_method_types'] ?? []);
        $nextActionType = (string) data_get($paymentIntent, 'next_action.type', '');
        $isMultibanco = in_array('multibanco', $paymentIntentTypes, true) || $nextActionType === 'display_multibanco_details';

        $pagamentoConcluido = ($session['payment_status'] ?? null) === 'paid'
            || $paymentIntentStatus === 'succeeded';

        $pagamentoPendenteMultibanco = $isMultibanco
            && in_array($paymentIntentStatus, ['processing', 'requires_action'], true);

        // Bloqueia finalizacao enquanto pagamento nao estiver concluido/pendente multibanco.
        if (!$pagamentoConcluido && !$pagamentoPendenteMultibanco) {
            return redirect()->route('checkout.pagamento')->with('popup_info', 'O pagamento ainda não foi concluído.');
        }

        // Pagamento confirmado: marca encomenda como enviada e gera rastreio se necessario.
        if ($pagamentoConcluido && $encomenda->estado !== 'enviado') {
            $encomenda->update([
                'estado' => 'enviado',
                'stripe_payment_intent_id' => (string) ($paymentIntent['id'] ?? $session['payment_intent'] ?? $paymentIntentId),
                'pago_em' => now(),
                'checkout_finalizado_em' => now(),
                'transportadora' => 'CTT',
                'numero_rastreio' => $encomenda->numero_rastreio ?: $this->gerarNumeroRastreioCtt(),
            ]);
        }

        // Multibanco pendente: mantém encomenda em pendente_pagamento.
        if ($pagamentoPendenteMultibanco) {
            $encomenda->update([
                'estado' => 'pendente_pagamento',
                'stripe_payment_intent_id' => (string) ($paymentIntent['id'] ?? $session['payment_intent'] ?? $paymentIntentId),
                'checkout_finalizado_em' => now(),
            ]);
        }

        // Dispara notificacao aos administradores sobre nova encomenda.
        $this->notificarAdminsNovaEncomenda($encomenda, $user);

        $carrinho = Carrinho::where('user_id', $user->id)->first();

        // Limpa carrinho apos processamento do checkout.
        if ($carrinho) {
            $carrinho->itens()->delete();
            $carrinho->lembrete_abandono_enviado_em = null;
            $carrinho->save();
            $carrinho->touch();
        }

        // Limpa dados temporarios do fluxo de checkout em sessao.
        session()->forget('checkout.morada');
        session()->forget('checkout.morada_id');
        session()->forget('checkout.encomenda_id');
        session()->forget('checkout.promocao');
        session()->forget('checkout.faturacao');

        // Exibe sucesso com indicador de pendencia para pagamentos multibanco.
        $pagamentoPendente = $pagamentoPendenteMultibanco;
        $prazoLimitePagamento = $pagamentoPendente
            ? $encomenda->created_at?->copy()->addDays(7)
            : null;

        return view('checkout.sucesso', compact('encomenda', 'pagamentoPendente', 'prazoLimitePagamento'));
    }

    // Gera codigo CTT unico para rastreamento da encomenda.
    private function gerarNumeroRastreioCtt(): string
    {
        do {
            $codigo = 'CTT'.strtoupper(Str::random(12));
        } while (Encomenda::where('numero_rastreio', $codigo)->exists());

        return $codigo;
    }

    // Notifica administradores sobre nova encomenda evitando duplicados.
    private function notificarAdminsNovaEncomenda(Encomenda $encomenda, User $user): void
    {
        // Recolhe todos os administradores ativos no sistema.
        $admins = User::query()
            ->where('role', 'admin')
            ->get();

        if ($admins->isEmpty()) {
            return;
        }

        // Filtra administradores que ainda nao receberam notificacao desta encomenda.
        $adminsParaNotificar = $admins->filter(function (User $admin) use ($encomenda) {
            return !DatabaseNotification::query()
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', $admin->id)
                ->where('type', EncomendaCriadaNotification::class)
                ->where('data->encomenda_id', $encomenda->id)
                ->exists();
        });

        if ($adminsParaNotificar->isEmpty()) {
            return;
        }

        // Envia notificacao interna e tenta envio por email sem quebrar fluxo.
        Notification::send($adminsParaNotificar, new EncomendaCriadaNotification($encomenda, $user, ['database']));

        try {
            Notification::send($adminsParaNotificar, new EncomendaCriadaNotification($encomenda, $user, ['mail']));
        } catch (\Throwable $e) {
            Log::warning('Falha ao enviar email de nova encomenda', [
                'encomenda_id' => $encomenda->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // Converte modelo Morada para estrutura serializavel usada na sessao.
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

    // Obtem encomenda de checkout existente na sessao ou cria nova.
    private function obterOuCriarEncomendaCheckout(User $user, $itens, array $dadosMorada, array $totais, ?array $promocaoAtiva, array $dadosFaturacao): Encomenda
    {
        $encomendaId = (int) session('checkout.encomenda_id', 0);

        // Atualiza encomenda ja existente quando o id da sessao ainda e valido.
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
                    'fatura_com_nif' => $dadosFaturacao['fatura_com_nif'],
                    'fatura_nif' => $dadosFaturacao['fatura_nif'] ?: null,
                    'fatura_nome' => $dadosFaturacao['fatura_nome'] ?: null,
                ]);

                // Sincroniza itens para refletir estado atual do carrinho.
                $this->sincronizarItensEncomenda($encomendaExistente, $itens);

                return $encomendaExistente;
            }
        }

        // Cria nova encomenda em transacao quando nao existe referencia valida.
        $encomenda = DB::transaction(function () use ($user, $dadosMorada, $itens, $totais, $promocaoAtiva, $dadosFaturacao) {
            $encomenda = Encomenda::create([
                'user_id' => $user->id,
                'estado' => 'checkout_em_progresso',
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
                'fatura_com_nif' => $dadosFaturacao['fatura_com_nif'],
                'fatura_nif' => $dadosFaturacao['fatura_nif'] ?: null,
                'fatura_nome' => $dadosFaturacao['fatura_nome'] ?: null,
            ]);

            // Replica itens atuais do carrinho na encomenda criada.
            $this->sincronizarItensEncomenda($encomenda, $itens);

            return $encomenda;
        });

        // Guarda referencia da encomenda em sessao para reutilizacao no fluxo.
        session(['checkout.encomenda_id' => $encomenda->id]);

        return $encomenda;
    }

    // Substitui itens da encomenda pelos itens correntes do carrinho.
    private function sincronizarItensEncomenda(Encomenda $encomenda, $itens): void
    {
        // Remove snapshot anterior para evitar duplicacao de linhas.
        $encomenda->itens()->delete();

        foreach ($itens as $item) {
            $quantidade = (int) $item->quantidade;
            $preco = (float) $item->preco_unitario;

            // Grava snapshot do item com dados de livro e valores financeiros.
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

    // Cria Payment Intent Stripe e devolve client secret para o frontend.
    private function criarPaymentIntentCheckout(User $user, float $total): string
    {
        $chaveStripe = trim((string) config('services.stripe.secret'));

        // Sem chave secreta, nao e possivel inicializar Payment Intent.
        if ($chaveStripe === '') {
            return '';
        }

        // Solicita criacao de intent com metodos card, MB WAY e Multibanco.
        $response = Http::withBasicAuth($chaveStripe, '')
            ->asForm()
            ->post('https://api.stripe.com/v1/payment_intents', [
                'amount' => (int) round($total * 100),
                'currency' => 'eur',
                'payment_method_types[0]' => 'card',
                'payment_method_types[1]' => 'mb_way',
                'payment_method_types[2]' => 'multibanco',
                'receipt_email' => (string) $user->email,
                'description' => 'Checkout Biblioteca - utilizador #' . $user->id,
                'metadata[user_id]' => (string) $user->id,
            ]);

        // Regista falha da API Stripe e devolve vazio para tratamento na camada superior.
        if (!$response->successful()) {
            Log::warning('Falha ao criar payment intent Stripe', [
                'status' => $response->status(),
                'body' => $response->body(),
                'user_id' => $user->id,
            ]);

            return '';
        }

        // Valida campos minimos retornados para continuidade do pagamento.
        $paymentIntent = $response->json();
        $paymentIntentId = (string) ($paymentIntent['id'] ?? '');
        $clientSecret = (string) ($paymentIntent['client_secret'] ?? '');

        if ($paymentIntentId === '' || $clientSecret === '') {
            Log::warning('Resposta inválida ao criar payment intent Stripe', [
                'user_id' => $user->id,
                'response' => $paymentIntent,
            ]);

            return '';
        }

        // Guarda id do intent em sessao para reconciliacao posterior.
        session(['checkout.payment_intent_id' => $paymentIntentId]);

        return $clientSecret;
    }

    // Le promocao da sessao e remove entradas inconsistentes.
    private function obterPromocaoValidaDaSessao(): ?array
    {
        $promocao = session('checkout.promocao');

        if (!is_array($promocao)) {
            return null;
        }

        $codigo = strtoupper(trim((string) ($promocao['codigo'] ?? '')));
        $percentual = (int) ($promocao['percentual'] ?? 0);

        // Qualquer divergencia invalida a promocao e limpa a sessao.
        if ($codigo !== self::CODIGO_PROMOCIONAL || $percentual !== self::DESCONTO_PROMOCIONAL_PERCENTUAL) {
            session()->forget('checkout.promocao');

            return null;
        }

        return [
            'codigo' => $codigo,
            'percentual' => $percentual,
        ];
    }

    // Calcula totais fiscais, portes e desconto aplicado ao checkout.
    private function calcularTotaisComPromocao(float $subtotal, int $quantidadeItens, ?array $promocaoAtiva): array
    {
        $portes = $subtotal < 50 ? 1.99 : 0.0;
        $valorSemIva = $subtotal / 1.06;
        $valorIva = $subtotal - $valorSemIva;
        $descontoPercentual = (int) ($promocaoAtiva['percentual'] ?? 0);
        $descontoValor = $descontoPercentual > 0 ? round($subtotal * ($descontoPercentual / 100), 2) : 0.0;
        $total = round(max(0, $subtotal - $descontoValor + $portes), 2);

        // Retorna estrutura padrao consumida pelas views e criacao de encomenda.
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

    // Normaliza dados de faturacao enviados pelo formulario.
    private function normalizarDadosFaturacao(array $dados): array
    {
        $faturaComNif = filter_var($dados['fatura_com_nif'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $faturaNif = preg_replace('/\D+/', '', (string) ($dados['fatura_nif'] ?? ''));
        $faturaNome = trim((string) ($dados['fatura_nome'] ?? ''));

        // Quando fatura sem NIF, limpa campos associados por consistencia.
        if (!$faturaComNif) {
            return [
                'fatura_com_nif' => false,
                'fatura_nif' => '',
                'fatura_nome' => '',
            ];
        }

        return [
            'fatura_com_nif' => true,
            'fatura_nif' => $faturaNif,
            'fatura_nome' => $faturaNome,
        ];
    }
}
