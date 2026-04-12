<?php
namespace App\Http\Controllers;

use App\Models\Editora;
use App\Models\Livro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Controlador responsavel pelas operacoes CRUD de editoras.
class EditoraController extends Controller
{
    // Lista editoras com pesquisa e ordenacao por nome.
    public function index(Request $request)
    {
        // Le termos de pesquisa e sentido da ordenacao a partir da query string.
        $search = $request->input('search', '');
        $sortOrder = $request->input('sort_order', 'asc');
        // Inclui livros para evitar consultas adicionais na listagem.
        $query = Editora::with('livros');

        // Aplica filtro por nome quando foi informado termo de pesquisa.
        if ($search) {
            $query->where('nome', 'like', "%{$search}%");
        }

        // Ordena por nome e pagina os resultados para a vista.
        $query->orderBy('nome', $sortOrder);
        $editoras = $query->paginate(10);
        // Renderiza a listagem com estado atual dos filtros.
        return view('editoras.index', compact('editoras', 'search', 'sortOrder'));
    }

    // Exibe o formulario para criar uma nova editora.
    public function create()
    {
        // Mostra formulario para cadastro de nova editora.
        return view('editoras.create');
    }

    // Salva uma editora e armazena o logotipo quando enviado.
    public function store(Request $request)
    {
        // Valida campos obrigatorios e ficheiro de imagem opcional.
        $data = $request->validate([
            'nome' => 'required',
            'logotipo' => 'nullable|image'
        ]);
        // Se houver logotipo, guarda no disco publico e salva caminho relativo.
        if ($request->hasFile('logotipo')) {
            $path = $request->file('logotipo')->store('editoras', 'public');
            $data['logotipo'] = 'storage/' . $path;
        }
        // Cria registo de editora com os dados validados.
        Editora::create($data);
        // Redireciona para a listagem apos criacao.
        return redirect()->route('editoras.index');
    }

    // Exibe a pagina da editora com livros publicados e autores relacionados.
    public function show(Request $request, Editora $editora)
    {
        // Obtem segmento da rota para validar URL canonica do recurso.
        $parametroRota = (string) $request->segment(2);

        // Redireciona para URL canonica quando a chave da rota diverge.
        if ($parametroRota !== (string) $editora->getRouteKey()) {
            return redirect()->route('editoras.show', $editora, 301);
        }

        // Carrega livros e respetivos autores para exibir no detalhe.
        $livros = $editora->livros()->with('autores')->get();
        // Consolida autores unicos associados aos livros da editora.
        $autores = $livros->flatMap(function ($livro) {
            return $livro->autores;
        })->unique('id');
        // Renderiza pagina de detalhe da editora.
        return view('editoras.show', compact('editora', 'livros', 'autores'));
    }

    // Exibe o formulario de edicao da editora.
    public function edit(Request $request, Editora $editora)
    {
        // Obtem parametro da rota para verificar consistencia da URL.
        $parametroRota = (string) $request->segment(2);

        // Forca URL canonica do recurso quando necessario.
        if ($parametroRota !== (string) $editora->getRouteKey()) {
            return redirect()->route('editoras.edit', $editora, 301);
        }

        // Mostra formulario de edicao preenchido com dados atuais.
        return view('editoras.edit', compact('editora'));
    }

    // Atualiza os dados da editora e substitui o logotipo quando enviado.
    public function update(Request $request, Editora $editora)
    {
        // Revalida dados permitidos para atualizacao da editora.
        $data = $request->validate([
            'nome' => 'required',
            'logotipo' => 'nullable|image'
        ]);

        // Substitui logotipo apenas quando novo ficheiro e enviado.
        if ($request->hasFile('logotipo')) {
            $path = $request->file('logotipo')->store('editoras', 'public');
            $data['logotipo'] = 'storage/' . $path;
        } else {
            // Mantem logotipo atual removendo chave para nao sobrescrever com null.
            unset($data['logotipo']);
        }

        // Persiste alteracoes da editora e redireciona para detalhe.
        $editora->update($data);
        return redirect()->route('editoras.show', $editora);
    }

    // Remove livros e vinculos relacionados antes de excluir a editora.
    public function destroy(Editora $editora)
    {
        // Executa remocao em transacao atomica para garantir consistencia.
        DB::transaction(function () use ($editora) {
            // Itera por cada livro da editora para remover vinculos N:N com autores.
            $editora->livros()->get()->each(function (Livro $livro) {
                // Remove associacoes para evitar referencias pendentes.
                $livro->autores()->detach();
                // Exclui registo de livro.
                $livro->delete();
            });

            // Finalmente exclui registo da editora.
            $editora->delete();
        });

        // Volta a listagem apos exclusao completa.
        return redirect()->route('editoras.index');
    }
}



