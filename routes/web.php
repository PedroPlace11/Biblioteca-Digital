<?php

use App\Http\Controllers\LivroController;
use App\Http\Controllers\AutorController;
use App\Http\Controllers\EditoraController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\Admin\EncomendaController as AdminEncomendaController;
use App\Http\Controllers\CarrinhoController;
use App\Http\Controllers\Cidadao\EncomendaController as CidadaoEncomendaController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RequisicaoController;
use App\Http\Controllers\Cidadao\MoradaController as CidadaoMoradaController;
use App\Models\Livro;
use App\Models\Autor;
use App\Models\Editora;
use App\Models\Requisicao;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Página pública inicial com destaque de livros e contadores gerais.
Route::get('/', function () {
    $harryPotters = Livro::with('autores')->where('nome', 'like', 'Harry Potter%')->take(2)->get();
    $outros = Livro::with('autores')->where('nome', 'not like', 'Harry Potter%')->take(6 - $harryPotters->count())->get();
    $livros = $harryPotters->concat($outros)->take(6);
    $totalLivros = Livro::count();
    $totalAutores = Autor::count();
    $totalEditoras = Editora::count();
    return view('welcome', compact(
        'livros',
        'totalLivros',
        'totalAutores',
        'totalEditoras'
    ));
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    // Painel principal: ramificação admin e ramificação cidadão no mesmo endpoint.
    Route::get('/dashboard', function (Request $request) {
        if (Auth::user()->role == 'admin') {
            $totalLivros = Livro::count();
            $totalAutores = Autor::count();
            $totalEditoras = Editora::count();
            $totalRequisicoes = Requisicao::withTrashed()->count();

            $estado = (string) $request->query('estado', 'todas');
            if (!in_array($estado, ['todas', 'ativa', 'encerrada'], true)) {
                $estado = 'todas';
            }

            $pesquisa = trim((string) $request->query('q', ''));
            $dataInicio = (string) $request->query('data_inicio', '');
            $dataFim = (string) $request->query('data_fim', '');

            // Listagem administrativa de requisições (ativas + encerradas) com filtros.
            $todasRequisicoesQuery = Requisicao::withTrashed()->with('user', 'livro');

            if ($estado === 'ativa') {
                $todasRequisicoesQuery->whereNull('deleted_at');
            } elseif ($estado === 'encerrada') {
                $todasRequisicoesQuery->whereNotNull('deleted_at');
            }

            if ($pesquisa !== '') {
                $pesquisaNumeroRequisicao = null;

                // Só converte para número de requisição quando a pesquisa for um código válido (R000001 ou 000001).
                if (preg_match('/^\s*R?\s*0*(\d+)\s*$/i', $pesquisa, $matchNumeroRequisicao)) {
                    $pesquisaNumeroRequisicao = (int) $matchNumeroRequisicao[1];
                }

                $todasRequisicoesQuery->where(function ($query) use ($pesquisa, $pesquisaNumeroRequisicao) {
                    $query->where('cidadao_nome', 'like', "%{$pesquisa}%")
                        ->orWhere('cidadao_email', 'like', "%{$pesquisa}%")
                        ->orWhere('cidadao_numero_leitor', 'like', "%{$pesquisa}%")
                        ->orWhereHas('user', function ($userQuery) use ($pesquisa) {
                            $userQuery->where('name', 'like', "%{$pesquisa}%")
                                ->orWhere('email', 'like', "%{$pesquisa}%");
                        })
                        ->orWhereHas('livro', function ($livroQuery) use ($pesquisa) {
                            $livroQuery->where('nome', 'like', "%{$pesquisa}%")
                                ->orWhere('isbn', 'like', "%{$pesquisa}%");
                        });

                    if (!is_null($pesquisaNumeroRequisicao) && $pesquisaNumeroRequisicao > 0) {
                        $query->orWhere('numero_requisicao_seq', $pesquisaNumeroRequisicao);
                    }
                });
            }

            if ($dataInicio !== '') {
                $todasRequisicoesQuery->whereDate('created_at', '>=', $dataInicio);
            }

            if ($dataFim !== '') {
                $todasRequisicoesQuery->whereDate('created_at', '<=', $dataFim);
            }

            $todasRequisicoes = $todasRequisicoesQuery
                ->orderByDesc('numero_requisicao_seq')
                ->orderByDesc('id')
                ->paginate(10)
                ->withQueryString()
                ->fragment('secao-requisicoes');

            $totalRequisicoesFiltradas = $todasRequisicoes->total();

            $minhasRequisicoesAdmin = Requisicao::withTrashed()
                ->where('user_id', Auth::id())
                ->with('livro.autores', 'livro.editora')
                ->latest()
                ->get();
            $totalMinhasRequisicoesAdmin = $minhasRequisicoesAdmin->count();
            $totalMinhasRequisicoesAdminAtivas = $minhasRequisicoesAdmin->whereNull('deleted_at')->count();
            $totalMinhasRequisicoesAdminEncerradas = $minhasRequisicoesAdmin->whereNotNull('deleted_at')->count();
            $livrosPorEditora = Editora::has('livros')->withCount('livros')->get();
            $topAutores = Autor::withCount('livros')
                ->orderBy('livros_count', 'desc')
                ->take(5)
                ->get();
            $livroMaisCaro = Livro::orderBy('preco', 'desc')->first();
            $livroMaisBarato = Livro::orderBy('preco', 'asc')->first();
            $ultimosLivros = Livro::latest()
                ->take(5)
                ->with('autores')
                ->get();
            return view('admin.dashboard', compact(
                'totalLivros',
                'totalAutores',
                'totalEditoras',
                'totalRequisicoes',
                'todasRequisicoes',
                'estado',
                'pesquisa',
                'dataInicio',
                'dataFim',
                'totalRequisicoesFiltradas',
                'minhasRequisicoesAdmin',
                'totalMinhasRequisicoesAdmin',
                'totalMinhasRequisicoesAdminAtivas',
                'totalMinhasRequisicoesAdminEncerradas',
                'livrosPorEditora',
                'topAutores',
                'livroMaisCaro',
                'livroMaisBarato',
                'ultimosLivros'
            ));
        }
        $ultimosLivros = Livro::latest()
            ->take(5)
            ->with('autores')
            ->get();
        $minhasRequisicoes = Requisicao::withTrashed()
            ->where('user_id', Auth::id())
            ->with('user', 'livro.autores', 'livro.editora')
            ->latest()
            ->get();
        $ultimaAtualizacaoRequisicoesTs = $minhasRequisicoes
            ->max(fn ($requisicao) => $requisicao->updated_at?->timestamp ?? 0) ?? 0;
        $totalMinhasRequisicoes = $minhasRequisicoes->count();
        $totalMinhasRequisicoesAtivas = $minhasRequisicoes->whereNull('deleted_at')->count();
        $totalMinhasRequisicoesEncerradas = $minhasRequisicoes->whereNotNull('deleted_at')->count();

        return view('cidadao.dashboard', compact(
            'ultimosLivros',
            'minhasRequisicoes',
            'ultimaAtualizacaoRequisicoesTs',
            'totalMinhasRequisicoes',
            'totalMinhasRequisicoesAtivas',
            'totalMinhasRequisicoesEncerradas'
        ));
    })->middleware(['auth'])->name('dashboard');
});

// Rotas exclusivas de administração.
use App\Http\Controllers\AdminReviewController;
Route::middleware(['auth', 'admin'])->group(function () {

    Route::get('livros/export', [LivroController::class, 'export'])->name('livros.export');
    Route::get('admins', [AdminUserController::class, 'index'])->name('admins.index');
    Route::get('admins/create', [AdminUserController::class, 'create'])->name('admins.create');
    Route::post('admins', [AdminUserController::class, 'store'])->name('admins.store');
    Route::delete('admins/{admin}', [AdminUserController::class, 'destroy'])->name('admins.destroy');
    Route::post('/requisicoes/{requisicao}/confirmar-recepcao', [LivroController::class, 'confirmarRecepcao'])->name('requisicoes.confirmar-recepcao');
    Route::resource('livros', LivroController::class)->except(['index', 'show']);
    Route::resource('autores', AutorController::class)
        ->parameters(['autores' => 'autor'])
        ->except(['index', 'show']);
    Route::resource('editoras', EditoraController::class)->except(['index', 'show']);

    // Gestão de reviews
    Route::get('admin/reviews', [AdminReviewController::class, 'index'])->name('admin.reviews.index');
    Route::get('admin/reviews/{review}', [AdminReviewController::class, 'show'])->name('admin.reviews.show');
    Route::patch('admin/reviews/{review}', [AdminReviewController::class, 'update'])->name('admin.reviews.update');
    Route::get('admin/encomendas', [AdminEncomendaController::class, 'index'])->name('admin.encomendas.index');
    Route::get('admin/encomendas/{encomenda}', [AdminEncomendaController::class, 'show'])->name('admin.encomendas.show');
    Route::patch('admin/encomendas/{encomenda}/pagamento', [AdminEncomendaController::class, 'atualizarPagamento'])->name('admin.encomendas.pagamento');

});

// Rotas autenticadas partilhadas entre admin e cidadão.
use App\Http\Controllers\ReviewController;
Route::middleware(['auth'])->group(function () {
    Route::get('/requisicoes', [RequisicaoController::class, 'index'])->name('requisicoes.index');
    // Endpoint de apoio ao polling da dashboard do cidadão para atualização quase em tempo real.
    Route::get('/requisicoes/ultima-atualizacao', function () {
        $lastUpdateTs = Requisicao::withTrashed()
            ->where('user_id', Auth::id())
            ->max('updated_at');

        return response()->json([
            'last_update_ts' => $lastUpdateTs ? strtotime((string) $lastUpdateTs) : 0,
        ]);
    })->name('requisicoes.last-update');
    Route::get('/livros/{livro}/requisitar', function($livro) {
        return redirect()->route('livros.show', $livro)
            ->with('error', 'Para requisitar um livro, utilize o botão apropriado na lista de livros.');
    });
    Route::post('/livros/{livro}/requisitar', [LivroController::class, 'requisitar'])->name('livros.requisitar');
    Route::post('/livros/{livro}/carrinho', [CarrinhoController::class, 'adicionar'])->name('carrinho.adicionar');
    Route::delete('/livros/{livro}/requisitar', [LivroController::class, 'cancelarRequisicao'])->name('livros.cancelar-requisicao');

    Route::get('/carrinho', [CarrinhoController::class, 'index'])->name('carrinho.index');
    Route::patch('/carrinho/itens/{itemId}', [CarrinhoController::class, 'atualizarQuantidade'])->name('carrinho.atualizar');
    Route::delete('/carrinho/itens/{itemId}', [CarrinhoController::class, 'remover'])->name('carrinho.remover');
    Route::delete('/carrinho', [CarrinhoController::class, 'limpar'])->name('carrinho.limpar');

    Route::get('/checkout/morada', [CheckoutController::class, 'morada'])->name('checkout.morada');
    Route::post('/checkout/morada', [CheckoutController::class, 'guardarMorada'])->name('checkout.morada.guardar');
    Route::post('/checkout/promocao', [CheckoutController::class, 'atualizarCodigoPromocional'])->name('checkout.promocao');
    Route::get('/checkout/pagamento', [CheckoutController::class, 'pagamento'])->name('checkout.pagamento');
    Route::get('/checkout/pagamento/stripe', function () {
        return redirect()->route('checkout.pagamento')
            ->with('popup_info', 'Use o botao "Pagar com Stripe" para iniciar o pagamento.');
    });
    Route::post('/checkout/pagamento/stripe', [CheckoutController::class, 'criarSessaoStripe'])->name('checkout.pagamento.stripe');
    Route::get('/checkout/sucesso', [CheckoutController::class, 'sucesso'])->name('checkout.sucesso');

    Route::post('/livros/{livro}/alerta-disponibilidade', [LivroController::class, 'ativarAlertaDisponibilidade'])->name('livros.alerta-disponibilidade');
    Route::post('/notificacoes/{notification}/lida', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notificacoes/ler-todas', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    // Review do cidadão após devolução
    Route::get('/livros/{livro}/review', [ReviewController::class, 'create'])->name('reviews.create');
    Route::post('/livros/{livro}/review', [ReviewController::class, 'store'])->name('reviews.store');
});

use App\Http\Controllers\Cidadao\ReviewController as CidadaoReviewController;
// Rotas para reviews do cidadão (apenas aprovados e recusados)
Route::prefix('conta')->middleware(['auth', 'verified'])->group(function () {
    Route::get('reviews', [CidadaoReviewController::class, 'index'])->name('cidadao.reviews.index');
    Route::get('reviews/{review}', [CidadaoReviewController::class, 'show'])->name('cidadao.reviews.show');
    Route::get('moradas', [CidadaoMoradaController::class, 'index'])->name('cidadao.moradas.index');
    Route::get('moradas/{morada}/editar', [CidadaoMoradaController::class, 'edit'])->name('cidadao.moradas.edit');
    Route::post('moradas', [CidadaoMoradaController::class, 'store'])->name('cidadao.moradas.store');
    Route::patch('moradas/{morada}', [CidadaoMoradaController::class, 'update'])->name('cidadao.moradas.update');
    Route::delete('moradas/{morada}', [CidadaoMoradaController::class, 'destroy'])->name('cidadao.moradas.destroy');
    Route::get('encomendas', [CidadaoEncomendaController::class, 'index'])->name('cidadao.encomendas.index');
    Route::get('encomendas/{encomenda}', [CidadaoEncomendaController::class, 'show'])->name('cidadao.encomendas.show');
});

// Rotas públicas de catálogo.

// Pesquisa Google Books
Route::get('/livros/googlebooks', [LivroController::class, 'pesquisarGoogleBooks'])->name('livros.googlebooks');
Route::post('/livros/googlebooks/salvar', [LivroController::class, 'salvarGoogleBook'])->name('livros.googlebooks.salvar');

Route::get('/livros', [LivroController::class, 'index'])->name('livros.index');
Route::get('/livros/{livro}', [LivroController::class, 'show'])->name('livros.show');

Route::get('/autores', [AutorController::class, 'index'])->name('autores.index');
Route::get('/autores/{autor}', [AutorController::class, 'show'])->name('autores.show');

Route::get('/editoras', [EditoraController::class, 'index'])->name('editoras.index');
Route::get('/editoras/{editora}', [EditoraController::class, 'show'])->name('editoras.show');

// Autenticação unificada em vista combinada.
Route::get('/login', function () {
    return view('auth.combined-auth');
})->middleware('guest')->name('login');

Route::get('/register', function () {
    return view('auth.combined-auth');
})->middleware('guest')->name('register');



