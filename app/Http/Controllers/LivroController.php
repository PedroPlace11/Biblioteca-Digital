<?php
namespace App\Http\Controllers;

use App\Exports\LivrosExport;
use App\Models\AlertaDisponibilidadeLivro;
use App\Models\Livro;
use App\Models\Autor;
use App\Models\Editora;
use App\Models\Requisicao;
use App\Models\User;
use App\Notifications\DevolucaoSolicitadaNotification;
use App\Notifications\LivroDisponivelNotification;
use App\Notifications\RecepcaoConfirmadaNotification;
use App\Notifications\RequisicaoCriadaNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\GoogleBooksService;

// Controlador responsavel pelas operacoes CRUD de livros e exportacao.
class LivroController extends Controller
{
    // Pesquisa livros na Google Books API
    public function pesquisarGoogleBooks(Request $request, GoogleBooksService $googleBooksService)
    {
        // Obtem termo de pesquisa a partir da query string.
        $query = $request->input('q');
        $resultados = [];
        $nenhumResultado = false;
        // Executa pesquisa na API quando termo e fornecido.
        if ($query) {
            $resultados = $googleBooksService->searchBooks($query);
            $nenhumResultado = empty($resultados);
        }
        // Mapeia para formato simplificado para exibir na view
        $livros = array_map(fn($item) => $googleBooksService->mapToLivro($item), $resultados);

        // Últimos livros adicionados localmente
        $ultimosLivros = \App\Models\Livro::with('autores')->latest()->take(5)->get();

        // Renderiza view com resultados da pesquisa e historico recente.
        return view('livros.googlebooks', [
            'livros' => $livros,
            'query' => $query,
            'resultados' => $resultados,
            'nenhumResultado' => $nenhumResultado,
            'ultimosLivros' => $ultimosLivros,
        ]);
    }

    // Salva um livro pesquisado da Google Books na base local
    public function salvarGoogleBook(Request $request, GoogleBooksService $googleBooksService)
    {
        // Valida campos obrigatorios e opcionais da importacao.
        $data = $request->validate([
            'isbn' => 'required|string|max:20',
            'titulo' => 'required',
            'editora' => 'nullable|string',
            'autores' => 'nullable|string',
            'ano' => 'nullable|string',
            'descricao' => 'nullable|string',
            'capa_url' => 'nullable|url',
            'preco' => 'nullable|numeric',
        ]);

        // Cria ou encontra editora para evitar duplicacoes.
        $editora = null;
        if (!empty($data['editora'])) {
            $editora = Editora::firstOrCreate(['nome' => $data['editora']]);
        }

        // Cria livro com dados validados da pesquisa.
        $livro = Livro::create([
            'isbn' => $data['isbn'],
            'nome' => $data['titulo'],
            'editora_id' => $editora ? $editora->id : null,
            'bibliografia' => $data['descricao'] ?? null,
            'imagem_capa' => $data['capa_url'] ?? null,
            'preco' => isset($data['preco']) ? $data['preco'] : 0,
        ]);

        // Processa autores a partir de lista separada por virgula.
        if (!empty($data['autores'])) {
            // Separa e limpa nomes de autores.
            $nomesAutores = array_map('trim', explode(',', $data['autores']));
            $idsAutores = [];
            // Cria autores inexistentes ou encontra existentes.
            foreach ($nomesAutores as $nomeAutor) {
                $autor = Autor::firstOrCreate(['nome' => $nomeAutor]);
                $idsAutores[] = $autor->id;
            }
            // Sincroniza vinculos muitos-para-muitos.
            $livro->autores()->sync($idsAutores);
        }

        // Redireciona para detalhe do livro importado.
        return redirect()->route('livros.show', $livro)->with('popup_success', 'Livro importado com sucesso!');
    }
    // Exporta os livros para um ficheiro Excel.
    public function export()
    {
        // Baixa ficheiro Excel com lista completa de livros.
        return Excel::download(new LivrosExport, 'livros.xlsx');
    }

    // Regista requisicao de um livro por um utilizador cidadao.
    public function requisitar(Livro $livro)
    {
        // Redireciona para login se nao autenticado.
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Bloqueia acesso se nao for usuario valido.
        if (!$user instanceof User) {
            abort(403);
        }

        // Executa validacoes dentro de transacao para evitar inconsistencias.
        $resultado = DB::transaction(function () use ($livro, $user) {
            $userId = $user->id;

            // Bloqueia livro para evitar race conditions com outras requisicoes.
            Livro::whereKey($livro->id)->lockForUpdate()->first();

            // Verifica se usuario ja tem requisicao ativa deste livro.
            $jaRequisitou = Requisicao::where('livro_id', $livro->id)
                ->where('user_id', $userId)
                ->whereNull('deleted_at')
                ->exists();

            if ($jaRequisitou) {
                return 'ja_requisitado_por_mim';
            }

            // Conta requisicoes ativas do usuario para validar limite de 3.
            $totalRequisicoesAtivas = Requisicao::where('user_id', $userId)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->count();

            if ($totalRequisicoesAtivas >= 3) {
                return 'limite_requisicoes_ativas';
            }

            // Verifica disponibilidade do livro para outro usuario.
            $livroIndisponivel = Requisicao::where('livro_id', $livro->id)
                ->where('user_id', '!=', $userId)
                ->whereNull('deleted_at')
                ->exists();

            if ($livroIndisponivel) {
                return 'indisponivel';
            }

            // Cria nova requisicao com dados do usuario e data prevista de 5 dias.
            Requisicao::create([
                'user_id' => $userId,
                'livro_id' => $livro->id,
                'cidadao_nome' => $user->name,
                'cidadao_email' => $user->email,
                'cidadao_numero_leitor' => $user->numero_leitor,
                'cidadao_foto_path' => $user->profile_photo_path,
                'data_fim_prevista' => Carbon::now()->addDays(5),
            ]);

            return 'ok';
        });

        // Valida resultado da transacao e retorna erros especificos.
        if ($resultado === 'ja_requisitado_por_mim') {
            return redirect()->route('livros.index')->with('info', 'Já requisitou este livro.');
        }

        if ($resultado === 'indisponivel') {
            return redirect()->route('livros.show', $livro)->with('info', 'Livro indisponível no momento.');
        }

        if ($resultado === 'limite_requisicoes_ativas') {
            return redirect()->back()->with('popup_info', 'Já atingiu o limite de 3 livros requisitados em simultâneo.');
        }

        // Recupera requisicao criada para notificacoes.
        $requisicaoCriada = Requisicao::with('livro')
            ->where('user_id', $user->id)
            ->where('livro_id', $livro->id)
            ->whereNull('deleted_at')
            ->latest('id')
            ->first();

        // Notifica admins e usuario via database (sino) e email.
        if ($requisicaoCriada instanceof Requisicao) {
            $admins = User::where('role', 'admin')->get();
            // Cria notificacoes em canais separados para permitir fallback independente.
            $databaseNotification = new RequisicaoCriadaNotification($requisicaoCriada, $user, $livro, ['database']);
            $mailNotification = new RequisicaoCriadaNotification($requisicaoCriada, $user, $livro, ['mail']);

            // Sempre grava no sino (database), mesmo que o SMTP falhe.
            Notification::send($admins, $databaseNotification);

            // Notifica usuario se nao for admin.
            if (!$admins->contains('id', $user->id)) {
                $user->notify($databaseNotification);
            }

            // Tenta enviar emails com tratamento de erro.
            try {
                Notification::send($admins, $mailNotification);

                if (!$admins->contains('id', $user->id)) {
                    $user->notify($mailNotification);
                }
            } catch (\Throwable $e) {
                // Registra erro mas nao bloqueia flujo (database ja foi gravada).
                Log::warning('Falha no envio de email de confirmação de requisição.', [
                    'livro_id' => $livro->id,
                    'requisicao_id' => $requisicaoCriada->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Redireciona para detalhe do livro apos criacao bem-sucedida.
        return redirect()->route('livros.show', $livro)->with('popup_success', 'Livro requisitado com sucesso.');
    }

    // Cancela a requisicao de um livro feita pelo utilizador autenticado.
    public function cancelarRequisicao(Livro $livro)
    {
        // Redireciona para login se nao autenticado.
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Bloqueia acesso se nao for usuario valido.
        if (!$user instanceof User) {
            abort(403);
        }

        // Recupera requisicao ativa do usuario para este livro.
        $requisicaoAtiva = Requisicao::where('user_id', Auth::id())
            ->where('livro_id', $livro->id)
            ->whereNull('deleted_at')
            ->first();

        // Valida existencia de requisicao ativa.
        if (!$requisicaoAtiva) {
            return redirect()->back()->with('popup_info', 'Não existe uma requisição ativa para este livro.');
        }

        // Bloqueia multiplos pedidos de devolucao para mesma requisicao.
        if ($requisicaoAtiva->devolucao_solicitada_em) {
            return redirect()->back()->with('popup_info', 'A devolução já foi solicitada e aguarda confirmação de um admin.');
        }

        // Marca timestamp de solicitacao de devolucao.
        $requisicaoAtiva->update([
            'devolucao_solicitada_em' => Carbon::now(),
        ]);

        // Determina destinatarios das notificacoes (excluindo user se for admin).
        $admins = User::where('role', 'admin')->get();
        $destinatarios = $user->role === 'admin'
            ? $admins->where('id', '!=', $user->id)->values()
            : $admins;

        // Notifica destinatarios via database e email.
        if ($destinatarios->isNotEmpty()) {
            $databaseNotification = new DevolucaoSolicitadaNotification($requisicaoAtiva, $user, $livro, ['database']);
            $mailNotification = new DevolucaoSolicitadaNotification($requisicaoAtiva, $user, $livro, ['mail']);

            // Grava notificacao no banco de dados.
            Notification::send($destinatarios, $databaseNotification);

            // Tenta enviar email com fallback.
            try {
                Notification::send($destinatarios, $mailNotification);
            } catch (\Throwable $e) {
                // Registra erro de email sem bloquear fluxo.
                Log::warning('Falha no envio de email de pedido de devolução.', [
                    'livro_id' => $livro->id,
                    'requisicao_id' => $requisicaoAtiva->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Confirma pedido de devolucao ao usuario.
        return redirect()->back()->with('popup_success', 'Pedido de devolução enviado. Aguarde a confirmação do admin.');
    }

    // Regista interesse para receber alerta quando o livro voltar a ficar disponível.
    public function ativarAlertaDisponibilidade(Livro $livro)
    {
        // Redireciona para login se nao autenticado.
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Bloqueia acesso se nao for usuario valido.
        if (!$user instanceof User) {
            abort(403);
        }

        // Reserva alertas apenas para cidadaos (nao admins).
        if ($user->role !== 'cidadao') {
            return redirect()->back()->with('popup_info', 'Apenas cidadãos podem ativar alertas de disponibilidade.');
        }

        // Verifica se livro esta indisponivel (necessario para alerta ter sentido).
        $livroIndisponivel = Requisicao::where('livro_id', $livro->id)
            ->whereNull('deleted_at')
            ->exists();

        if (!$livroIndisponivel) {
            return redirect()->route('livros.show', $livro)->with('popup_info', 'Este livro já está disponível para requisição.');
        }

        // Valida que usuario nao tem requisicao ativa (nao faz sentido alerta nesse caso).
        $requisitadoPorMim = Requisicao::where('livro_id', $livro->id)
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->exists();

        if ($requisitadoPorMim) {
            return redirect()->route('livros.show', $livro)->with('popup_info', 'Já tem uma requisição ativa deste livro.');
        }

        // Cria ou recupera alerta existente para evitar duplicacoes.
        $alerta = AlertaDisponibilidadeLivro::firstOrCreate([
            'user_id' => $user->id,
            'livro_id' => $livro->id,
        ]);

        // Valida se alerta foi newamente criado para devolver mensagem apropriada.
        if ($alerta->wasRecentlyCreated) {
            return redirect()->route('livros.show', $livro)->with('popup_success', 'Alerta ativado. Será notificado quando o livro ficará disponível.');
        }

        // Comunica que alerta ja existe.
        return redirect()->route('livros.show', $livro)->with('popup_info', 'O alerta para este livro já se encontra ativo.');
    }

    // Confirma a receção do livro por um admin e encerra a requisição.
    public function confirmarRecepcao(Requisicao $requisicao)
    {
        // Bloqueia acesso se nao for admin autenticado.
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            abort(403);
        }

        // Valida que requisicao nao foi ja encerrada (soft delete).
        if ($requisicao->deleted_at) {
            return redirect()->back()->with('popup_info', 'Esta requisição já se encontra encerrada.');
        }

        // Previne que mesmo admin que criou requisicao confirme a devolucao (segregacao de pares).
        if ($requisicao->user_id === Auth::id()) {
            return redirect()->back()->with('popup_info', 'Outro admin deve confirmar a receção desta devolução.');
        }

        $adminConfirmador = Auth::user();

        // Valida que admin e usuario valido.
        if (!$adminConfirmador instanceof User) {
            abort(403);
        }

        $utilizadorDaRequisicao = $requisicao->user;

        // Calcula data de recepcao atual e dias decorridos.
        $dataRececaoReal = Carbon::now();
        $diasDecorridos = null;

        // Se requisicao tem data de criacao, calcula dias (arredonda para cima).
        if ($requisicao->created_at) {
            // Guarda dias inteiros (arredondado para cima) para evitar valores decimais.
            $diasDecorridos = (int) ceil(max(0, $requisicao->created_at->diffInHours($dataRececaoReal) / 24));
        }

        // Atualiza requisicao com dados de confirmacao e admin confirmador.
        $requisicao->update([
            'data_recepcao_real' => $dataRececaoReal,
            'dias_decorridos' => $diasDecorridos,
            'confirmado_por_admin_id' => Auth::id(),
        ]);

        // Notifica usuario da recepcao confirmada via database e email.
        if ($utilizadorDaRequisicao instanceof User) {
            $databaseNotification = new RecepcaoConfirmadaNotification($requisicao, $adminConfirmador, $requisicao->livro, ['database']);
            $mailNotification = new RecepcaoConfirmadaNotification($requisicao, $adminConfirmador, $requisicao->livro, ['mail']);

            // Grava notificacao no sino.
            $utilizadorDaRequisicao->notify($databaseNotification);

            // Tenta enviar email com fallback.
            try {
                $utilizadorDaRequisicao->notify($mailNotification);
            } catch (\Throwable $e) {
                // Registra erro sem bloquear fluxo (database ja foi gravada).
                Log::warning('Falha no envio de email de confirmação de receção.', [
                    'livro_id' => $requisicao->livro_id,
                    'requisicao_id' => $requisicao->id,
                    'user_id' => $utilizadorDaRequisicao->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Notifica interessados que livro voltou a estar disponivel.
        $livro = $requisicao->livro;

        if ($livro instanceof Livro) {
            $this->notificarInteressadosLivroDisponivel($livro);
        }

        // Soft delete encerra requisicao.
        $requisicao->delete();

        // Confirma sucesso ao admin.
        return redirect()->back()->with('popup_success', 'Receção confirmada com sucesso e requisição encerrada.');
    }

    // Lista livros com filtros de pesquisa e ordenacao dinamica.
    public function index(Request $request)
    {
        // Le e normaliza parametros de busca e ordenacao.
        $search = trim((string) $request->input('search', ''));
        $sortBy = $request->input('sort_by', 'nome');
        $sortOrder = $request->input('sort_order', 'asc');

        // Valida ordenacao para prevenir SQL injection.
        if (!in_array($sortBy, ['nome', 'editora', 'autor'], true)) {
            $sortBy = 'nome';
        }

        if (!in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = 'asc';
        }

        // Carrega relacoes e conta requisicoes para cada livro.
        $query = Livro::with('autores', 'editora')->withCount('requisicoes');

        // Aplica filtro de pesquisa em multiplos campos.
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                // Pesquisa em nome, isbn, descricao e relacoes (autores/editora).
                $q->where('nome', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%")
                    ->orWhere('bibliografia', 'like', "%{$search}%")
                    ->orWhereHas('autores', function ($autorQuery) use ($search) {
                        $autorQuery->where('nome', 'like', "%{$search}%");
                    })
                    ->orWhereHas('editora', function ($editoraQuery) use ($search) {
                        $editoraQuery->where('nome', 'like', "%{$search}%");
                    });
            });
        }

        // Aplica ordenacao com joins apropriados conforme escolha do usuario.
        if ($sortBy === 'editora') {
            // Ordena por nome da editora com join e distinct para evitar duplicacoes.
            $query->join('editoras', 'livros.editora_id', '=', 'editoras.id')
                ->select('livros.*')
                ->distinct()
                ->orderBy('editoras.nome', $sortOrder);
        } elseif ($sortBy === 'autor') {
            // Ordena por nome do autor com joins na tabela pivot e autores.
            $query->join('autor_livro', 'livros.id', '=', 'autor_livro.livro_id')
                ->join('autors', 'autor_livro.autor_id', '=', 'autors.id')
                ->select('livros.*')
                ->distinct()
                ->orderBy('autors.nome', $sortOrder);
        } else {
            // Ordenar por nome do livro (padrao).
            $query->orderBy('nome', $sortOrder);
        }

        // Pagina resultados e preserva query string para manter filtros.
        $livros = $query->paginate(10)->withQueryString();
        $livrosRequisitadosIds = [];

        // Se usuario autenticado, recupera lista de livros que ja requisitou.
        if (Auth::check()) {
            $livrosRequisitadosIds = Requisicao::where('user_id', Auth::id())
                ->pluck('livro_id')
                ->toArray();
        }

        // Renderiza listagem com valores para filtros e requisicoes do usuario.
        return view('livros.index', compact('livros', 'search', 'sortBy', 'sortOrder', 'livrosRequisitadosIds'));
    }

    // Exibe o formulario de criacao de livro.
    public function create()
    {
        // Carrega listas completas de autores e editoras para o formulario.
        $autores = Autor::all();
        $editoras = Editora::all();
        // Renderiza formulario de criacao com opcoes disponiveis.
        return view('livros.create', compact('autores', 'editoras'));
    }

    // Cria um livro e sincroniza os autores selecionados.
    public function store(Request $request)
    {
        // Valida campos obrigatorios e opcionais do formulario.
        $data = $request->validate([
            'isbn' => 'required|string|max:13',
            'nome' => 'required',
            'editora_id' => 'required',
            'preco' => 'nullable|numeric',
            'bibliografia' => 'nullable',
            'imagem_capa' => 'nullable|image'
        ]);
        // Processa upload da capa quando fornecida.
        if ($request->hasFile('imagem_capa')) {
            $path = $request->file('imagem_capa')->store('capas', 'public');
            $data['imagem_capa'] = 'storage/' . $path;
        }
        // Cria novo livro com dados validados.
        $livro = Livro::create($data);
        // Sincroniza autores selecionados para relacao muitos-para-muitos.
        $livro->autores()->sync($request->autores);
        // Redireciona para listagem apos criacao.
        return redirect()->route('livros.index');
    }

    // Exibe o formulario de edicao de livro.
    public function edit(Livro $livro)
    {
        // Carrega listas de autores e editoras para o formulario de edicao.
        $autores = Autor::all();
        $editoras = Editora::all();
        // Renderiza formulario preenchido com dados atuais do livro.
        return view('livros.edit', compact('livro', 'autores', 'editoras'));
    }

    // Exibe os detalhes completos do livro.
    public function show(Request $request, Livro $livro)
    {
        // Valida URL canonica do recurso.
        $parametroRota = (string) $request->segment(2);

        // Redireciona permanentemente para URL canonica se necessario.
        if ($parametroRota !== (string) $livro->getRouteKey()) {
            return redirect()->route('livros.show', $livro, 301);
        }

        // Carrega autores e editora para detalhe completo.
        $livro->load('autores', 'editora');
        // Verifica disponibilidade do livro (se tem requisicoes ativas).
        $livroIndisponivel = Requisicao::where('livro_id', $livro->id)
            ->whereNull('deleted_at')
            ->exists();
        $requisitadoPorMim = false;
        $minhaRequisicaoAtiva = null;

        // Se usuario autenticado, verifica sua requisicao ativa.
        if (Auth::check()) {
            $minhaRequisicaoAtiva = Requisicao::where('livro_id', $livro->id)
                ->where('user_id', Auth::id())
                ->whereNull('deleted_at')
                ->first();

            $requisitadoPorMim = (bool) $minhaRequisicaoAtiva;
        }

        // Verifica se ciudadao tem alerta ativo para este livro indisponivel.
        $alertaDisponibilidadeAtivo = false;

        // So cidadaos podem ter alertas, e so se livro esta indisponivel e nao requisitado por eles.
        if (Auth::check() && Auth::user()?->role === 'cidadao' && $livroIndisponivel && !$requisitadoPorMim) {
            $alertaDisponibilidadeAtivo = AlertaDisponibilidadeLivro::where('livro_id', $livro->id)
                ->where('user_id', Auth::id())
                ->exists();
        }

        // Carrega livros relacionados por similaridade de descricao.
        $livrosRelacionados = $livro->relacionados(4);

        // Consulta historico de requisicoes (incluindo soft-deleted) com dados do usuario.
        $historicoQuery = Requisicao::withTrashed()
            ->with('user:id,name,email,profile_photo_path')
            ->where('livro_id', $livro->id);

        // Aplica restricoes de acesso: cidadaos ve apenas seu historico, admins veem tudo.
        if (!Auth::check()) {
            // Usuario nao autenticado nao ve historico.
            $historicoQuery->whereRaw('1 = 0');
        } elseif (Auth::user()?->role !== 'admin') {
            // Cidadao ve apenas seu proprio historico.
            $historicoQuery->where('user_id', Auth::id());
        }
        // Admin ve historico completo (nao ha filtro adicional).

        // Recupera historico e agrupa por usuario para exibir por cidadao.
        $historicoRequisicoesPorCidadao = $historicoQuery
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('user_id');

        // Carrega reviews ativos (nao rejeitados/suspensos) para este livro.
        $reviewsAtivos = \App\Models\Review::where('livro_id', $livro->id)
            ->where('estado', 'ativo')
            ->with('user')
            ->latest()
            ->get();

        // Renderiza detalhe com todos os dados compilados.
        return view('livros.show', compact('livro', 'livroIndisponivel', 'requisitadoPorMim', 'minhaRequisicaoAtiva', 'alertaDisponibilidadeAtivo', 'historicoRequisicoesPorCidadao', 'reviewsAtivos', 'livrosRelacionados'));
    }

    /**
     * Notifica os cidadãos inscritos quando o livro volta a estar disponível.
     */
    protected function notificarInteressadosLivroDisponivel(Livro $livro): void
    {
        // Carrega todos os alertas ativos para este livro com dados do usuario.
        $alertas = AlertaDisponibilidadeLivro::with('user')
            ->where('livro_id', $livro->id)
            ->get();

        // Se nao ha alertas, nada a fazer.
        if ($alertas->isEmpty()) {
            return;
        }

        // Filtra usuarios cidadaos validos mais uma unica vez (unique).
        $destinatarios = $alertas
            ->pluck('user')
            ->filter(fn ($user) => $user instanceof User && $user->role === 'cidadao')
            ->unique('id')
            ->values();

        // Se nao ha destinatarios validos, limpa alertas e retorna.
        if ($destinatarios->isEmpty()) {
            AlertaDisponibilidadeLivro::where('livro_id', $livro->id)->delete();
            return;
        }

        // Cria notificacoes em canais separados para permite falha independente.
        $databaseNotification = new LivroDisponivelNotification($livro, ['database']);
        $mailNotification = new LivroDisponivelNotification($livro, ['mail']);

        // Envia notificacao de database (sino) a todos os destinararios.
        Notification::send($destinatarios, $databaseNotification);

        // Tenta enviar emails com tratamento de erro.
        try {
            Notification::send($destinatarios, $mailNotification);
        } catch (\Throwable $e) {
            // Registra erro mas nao bloqueia fluxo (database ja foi enviada).
            Log::warning('Falha no envio de email de livro disponível.', [
                'livro_id' => $livro->id,
                'destinatarios' => $destinatarios->pluck('id')->all(),
                'error' => $e->getMessage(),
            ]);
        }

        // Limpa alertas que foram notificados (impede duplicacao de notificacoes).
        AlertaDisponibilidadeLivro::where('livro_id', $livro->id)
            ->whereIn('user_id', $destinatarios->pluck('id'))
            ->delete();
    }

    // Atualiza os dados do livro, incluindo capa e autores vinculados.
    public function update(Request $request, Livro $livro)
    {
        // Valida dados da edicao incluindo array de autores.
        $data = $request->validate([
            'isbn' => 'nullable',
            'nome' => 'required',
            'editora_id' => 'required',
            'preco' => 'nullable|numeric',
            'bibliografia' => 'nullable',
            'imagem_capa' => 'nullable|image',
            'autores' => 'nullable|array',
            'autores.*' => 'integer|exists:autors,id'
        ]);

        // Sustituye capa apenas se novo ficheiro foi upload.
        if ($request->hasFile('imagem_capa')) {
            $path = $request->file('imagem_capa')->store('capas', 'public');
            $data['imagem_capa'] = 'storage/' . $path;
        } else {
            // Remove chave capa para nao sobrescrever com null.
            unset($data['imagem_capa']);
        }

        // Persiste alteracoes dos dados do livro.
        $livro->update($data);
        // Sincroniza autores com lista enviada (remove nao selecionados).
        $livro->autores()->sync($request->autores ?? []);
        // Redireciona para detalhe apos atualizacao.
        return redirect()->route('livros.show', $livro);
    }

    // Remove os vinculos de autores e depois exclui o livro.
    public function destroy(Livro $livro)
    {
        // Armazena ID da editora para validacao posterior.
        $editoraId = $livro->editora_id;
        // Remove vinculos N:N com autores (necessario antes de deletar livro).
        $livro->autores()->detach();
        // Executa delecao do livro.
        $livro->delete();

        // Limpa editora orfao se nao tiver mais livros.
        if ($editoraId) {
            // Verifica se editora ainda tem outros livros.
            $temLivros = \App\Models\Livro::where('editora_id', $editoraId)->exists();
            // Se nao tem livros, deleta editora tambem (limpeza orfaos).
            if (!$temLivros) {
                \App\Models\Editora::where('id', $editoraId)->delete();
            }
        }

        // Redireciona para listagem apos exclusao completa.
        return redirect()->route('livros.index');
    }

}
// Fim do LivroController



