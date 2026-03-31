<?php
namespace App\Http\Controllers;

use App\Exports\LivrosExport;
use App\Models\Livro;
use App\Models\Autor;
use App\Models\Editora;
use App\Models\Requisicao;
use App\Models\User;
use App\Notifications\DevolucaoSolicitadaNotification;
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
        $query = $request->input('q');
        $resultados = [];
        $nenhumResultado = false;
        if ($query) {
            $resultados = $googleBooksService->searchBooks($query);
            $nenhumResultado = empty($resultados);
        }
        // Mapeia para formato simplificado para exibir na view
        $livros = array_map(fn($item) => $googleBooksService->mapToLivro($item), $resultados);

        // Últimos livros adicionados localmente
        $ultimosLivros = \App\Models\Livro::with('autores')->latest()->take(5)->get();

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

        // Editora
        $editora = null;
        if (!empty($data['editora'])) {
            $editora = Editora::firstOrCreate(['nome' => $data['editora']]);
        }

        // Livro
        $livro = Livro::create([
            'isbn' => $data['isbn'],
            'nome' => $data['titulo'],
            'editora_id' => $editora ? $editora->id : null,
            'bibliografia' => $data['descricao'] ?? null,
            'imagem_capa' => $data['capa_url'] ?? null,
            'preco' => isset($data['preco']) ? $data['preco'] : 0,
        ]);

        // Autores
        if (!empty($data['autores'])) {
            $nomesAutores = array_map('trim', explode(',', $data['autores']));
            $idsAutores = [];
            foreach ($nomesAutores as $nomeAutor) {
                $autor = Autor::firstOrCreate(['nome' => $nomeAutor]);
                $idsAutores[] = $autor->id;
            }
            $livro->autores()->sync($idsAutores);
        }

        return redirect()->route('livros.show', $livro->id)->with('popup_success', 'Livro importado com sucesso!');
    }
    // Exporta os livros para um ficheiro Excel.
    public function export()
    {
        return Excel::download(new LivrosExport, 'livros.xlsx');
    }

    // Regista requisicao de um livro por um utilizador cidadao.
    public function requisitar(Livro $livro)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if (!$user instanceof User) {
            abort(403);
        }

        $resultado = DB::transaction(function () use ($livro, $user) {
            $userId = $user->id;

            // Evita duas requisições concorrentes para o mesmo livro.
            Livro::whereKey($livro->id)->lockForUpdate()->first();

            $jaRequisitou = Requisicao::where('livro_id', $livro->id)
                ->where('user_id', $userId)
                ->whereNull('deleted_at')
                ->exists();

            if ($jaRequisitou) {
                return 'ja_requisitado_por_mim';
            }

            // Cada utilizador pode ter no máximo 3 livros ativos em simultâneo.
            $totalRequisicoesAtivas = Requisicao::where('user_id', $userId)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->count();

            if ($totalRequisicoesAtivas >= 3) {
                return 'limite_requisicoes_ativas';
            }

            $livroIndisponivel = Requisicao::where('livro_id', $livro->id)
                ->where('user_id', '!=', $userId)
                ->whereNull('deleted_at')
                ->exists();

            if ($livroIndisponivel) {
                return 'indisponivel';
            }

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

        if ($resultado === 'ja_requisitado_por_mim') {
            return redirect()->route('livros.index')->with('info', 'Já requisitou este livro.');
        }

        if ($resultado === 'indisponivel') {
            return redirect()->route('livros.show', $livro->id)->with('info', 'Livro indisponível no momento.');
        }

        if ($resultado === 'limite_requisicoes_ativas') {
            return redirect()->back()->with('popup_info', 'Já atingiu o limite de 3 livros requisitados em simultâneo.');
        }

        $requisicaoCriada = Requisicao::with('livro')
            ->where('user_id', $user->id)
            ->where('livro_id', $livro->id)
            ->whereNull('deleted_at')
            ->latest('id')
            ->first();

        if ($requisicaoCriada instanceof Requisicao) {
            $admins = User::where('role', 'admin')->get();
            $databaseNotification = new RequisicaoCriadaNotification($requisicaoCriada, $user, $livro, ['database']);
            $mailNotification = new RequisicaoCriadaNotification($requisicaoCriada, $user, $livro, ['mail']);

            // Sempre grava no sino (database), mesmo que o SMTP falhe.
            Notification::send($admins, $databaseNotification);

            if (!$admins->contains('id', $user->id)) {
                $user->notify($databaseNotification);
            }

            try {
                Notification::send($admins, $mailNotification);

                if (!$admins->contains('id', $user->id)) {
                    $user->notify($mailNotification);
                }
            } catch (\Throwable $e) {
                Log::warning('Falha no envio de email de confirmação de requisição.', [
                    'livro_id' => $livro->id,
                    'requisicao_id' => $requisicaoCriada->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('livros.show', $livro->id)->with('popup_success', 'Livro requisitado com sucesso.');
    }

    // Cancela a requisicao de um livro feita pelo utilizador autenticado.
    public function cancelarRequisicao(Livro $livro)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if (!$user instanceof User) {
            abort(403);
        }

        $requisicaoAtiva = Requisicao::where('user_id', Auth::id())
            ->where('livro_id', $livro->id)
            ->whereNull('deleted_at')
            ->first();

        if (!$requisicaoAtiva) {
            return redirect()->back()->with('popup_info', 'Não existe uma requisição ativa para este livro.');
        }

        if ($requisicaoAtiva->devolucao_solicitada_em) {
            return redirect()->back()->with('popup_info', 'A devolução já foi solicitada e aguarda confirmação de um admin.');
        }

        $requisicaoAtiva->update([
            'devolucao_solicitada_em' => Carbon::now(),
        ]);

        $admins = User::where('role', 'admin')->get();
        $destinatarios = $user->role === 'admin'
            ? $admins->where('id', '!=', $user->id)->values()
            : $admins;

        if ($destinatarios->isNotEmpty()) {
            $databaseNotification = new DevolucaoSolicitadaNotification($requisicaoAtiva, $user, $livro, ['database']);
            $mailNotification = new DevolucaoSolicitadaNotification($requisicaoAtiva, $user, $livro, ['mail']);

            Notification::send($destinatarios, $databaseNotification);

            try {
                Notification::send($destinatarios, $mailNotification);
            } catch (\Throwable $e) {
                Log::warning('Falha no envio de email de pedido de devolução.', [
                    'livro_id' => $livro->id,
                    'requisicao_id' => $requisicaoAtiva->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->back()->with('popup_success', 'Pedido de devolução enviado. Aguarde a confirmação do admin.');
    }

    // Confirma a receção do livro por um admin e encerra a requisição.
    public function confirmarRecepcao(Requisicao $requisicao)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            abort(403);
        }

        if ($requisicao->deleted_at) {
            return redirect()->back()->with('popup_info', 'Esta requisição já se encontra encerrada.');
        }

        if ($requisicao->user_id === Auth::id()) {
            return redirect()->back()->with('popup_info', 'Outro admin deve confirmar a receção desta devolução.');
        }

        $adminConfirmador = Auth::user();

        if (!$adminConfirmador instanceof User) {
            abort(403);
        }

        $utilizadorDaRequisicao = $requisicao->user;

        $dataRececaoReal = Carbon::now();
        $diasDecorridos = null;

        if ($requisicao->created_at) {
            // Guarda dias inteiros (arredondado para cima) para evitar valores decimais.
            $diasDecorridos = (int) ceil(max(0, $requisicao->created_at->diffInHours($dataRececaoReal) / 24));
        }

        $requisicao->update([
            'data_recepcao_real' => $dataRececaoReal,
            'dias_decorridos' => $diasDecorridos,
            'confirmado_por_admin_id' => Auth::id(),
        ]);

        if ($utilizadorDaRequisicao instanceof User) {
            $databaseNotification = new RecepcaoConfirmadaNotification($requisicao, $adminConfirmador, $requisicao->livro, ['database']);
            $mailNotification = new RecepcaoConfirmadaNotification($requisicao, $adminConfirmador, $requisicao->livro, ['mail']);

            $utilizadorDaRequisicao->notify($databaseNotification);

            try {
                $utilizadorDaRequisicao->notify($mailNotification);
            } catch (\Throwable $e) {
                Log::warning('Falha no envio de email de confirmação de receção.', [
                    'livro_id' => $requisicao->livro_id,
                    'requisicao_id' => $requisicao->id,
                    'user_id' => $utilizadorDaRequisicao->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $requisicao->delete();

        return redirect()->back()->with('popup_success', 'Receção confirmada com sucesso e requisição encerrada.');
    }

    // Lista livros com filtros de pesquisa e ordenacao dinamica.
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $sortBy = $request->input('sort_by', 'nome');
        $sortOrder = $request->input('sort_order', 'asc');

        if (!in_array($sortBy, ['nome', 'editora', 'autor'], true)) {
            $sortBy = 'nome';
        }

        if (!in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = 'asc';
        }

        $query = Livro::with('autores', 'editora')->withCount('requisicoes');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
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

        // Aplicar ordenação
        if ($sortBy === 'editora') {
            $query->join('editoras', 'livros.editora_id', '=', 'editoras.id')
                ->select('livros.*')
                ->distinct()
                ->orderBy('editoras.nome', $sortOrder);
        } elseif ($sortBy === 'autor') {
            $query->join('autor_livro', 'livros.id', '=', 'autor_livro.livro_id')
                ->join('autors', 'autor_livro.autor_id', '=', 'autors.id')
                ->select('livros.*')
                ->distinct()
                ->orderBy('autors.nome', $sortOrder);
        } else {
            // Ordenar por nome do livro
            $query->orderBy('nome', $sortOrder);
        }

        $livros = $query->paginate(10)->withQueryString();
        $livrosRequisitadosIds = [];

        if (Auth::check()) {
            $livrosRequisitadosIds = Requisicao::where('user_id', Auth::id())
                ->pluck('livro_id')
                ->toArray();
        }

        return view('livros.index', compact('livros', 'search', 'sortBy', 'sortOrder', 'livrosRequisitadosIds'));
    }

    // Exibe o formulario de criacao de livro.
    public function create()
    {
        $autores = Autor::all();
        $editoras = Editora::all();
        return view('livros.create', compact('autores', 'editoras'));
    }

    // Cria um livro e sincroniza os autores selecionados.
    public function store(Request $request)
    {
        $data = $request->validate([
            'isbn' => 'required|string|max:13',
            'nome' => 'required',
            'editora_id' => 'required',
            'preco' => 'nullable|numeric',
            'bibliografia' => 'nullable',
            'imagem_capa' => 'nullable|image'
        ]);
        if ($request->hasFile('imagem_capa')) {
            $path = $request->file('imagem_capa')->store('capas', 'public');
            $data['imagem_capa'] = 'storage/' . $path;
        }
        $livro = Livro::create($data);
        $livro->autores()->sync($request->autores);
        return redirect()->route('livros.index');
    }

    // Exibe o formulario de edicao de livro.
    public function edit(Livro $livro)
    {
        $autores = Autor::all();
        $editoras = Editora::all();
        return view('livros.edit', compact('livro', 'autores', 'editoras'));
    }

    // Exibe os detalhes completos do livro.
    public function show(Livro $livro)
    {
        $livro->load('autores', 'editora');
        $livroIndisponivel = Requisicao::where('livro_id', $livro->id)
            ->whereNull('deleted_at')
            ->exists();
        $requisitadoPorMim = false;
        $minhaRequisicaoAtiva = null;

        if (Auth::check()) {
            $minhaRequisicaoAtiva = Requisicao::where('livro_id', $livro->id)
                ->where('user_id', Auth::id())
                ->whereNull('deleted_at')
                ->first();

            $requisitadoPorMim = (bool) $minhaRequisicaoAtiva;
        }

        // Livros relacionados por similaridade de descrição
        $livrosRelacionados = $livro->relacionados(4);

        $historicoQuery = Requisicao::withTrashed()
            ->with('user:id,name,email,profile_photo_path')
            ->where('livro_id', $livro->id);

        // Cidadãos só podem consultar o próprio histórico deste livro.
        if (!Auth::check()) {
            $historicoQuery->whereRaw('1 = 0');
        } elseif (Auth::user()?->role !== 'admin') {
            $historicoQuery->where('user_id', Auth::id());
        }

        $historicoRequisicoesPorCidadao = $historicoQuery
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('user_id');

        // Reviews ativos deste livro
        $reviewsAtivos = \App\Models\Review::where('livro_id', $livro->id)
            ->where('estado', 'ativo')
            ->with('user')
            ->latest()
            ->get();

        return view('livros.show', compact('livro', 'livroIndisponivel', 'requisitadoPorMim', 'minhaRequisicaoAtiva', 'historicoRequisicoesPorCidadao', 'reviewsAtivos', 'livrosRelacionados'));
    }

    // Atualiza os dados do livro, incluindo capa e autores vinculados.
    public function update(Request $request, Livro $livro)
    {
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

        if ($request->hasFile('imagem_capa')) {
            $path = $request->file('imagem_capa')->store('capas', 'public');
            $data['imagem_capa'] = 'storage/' . $path;
        } else {
            unset($data['imagem_capa']);
        }

        $livro->update($data);
        $livro->autores()->sync($request->autores ?? []);
        return redirect()->route('livros.show', $livro->id);
    }

    // Remove os vinculos de autores e depois exclui o livro.
    public function destroy(Livro $livro)
    {
        $editoraId = $livro->editora_id;
        $livro->autores()->detach();
        $livro->delete();

        // Se a editora não tiver mais livros, apaga a editora
        if ($editoraId) {
            $temLivros = \App\Models\Livro::where('editora_id', $editoraId)->exists();
            if (!$temLivros) {
                \App\Models\Editora::where('id', $editoraId)->delete();
            }
        }

        return redirect()->route('livros.index');
    }

}
// Fim do LivroController



