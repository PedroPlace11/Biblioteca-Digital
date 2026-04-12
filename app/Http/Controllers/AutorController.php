<?php
namespace App\Http\Controllers;

use App\Models\Autor;
use Illuminate\Http\Request;

// Controlador responsavel pelas operacoes CRUD de autores.
class AutorController extends Controller
{
    // Lista autores com pesquisa e ordenacao por nome.
    public function index(Request $request)
    {
        // Le termos de pesquisa e sentido da ordenacao a partir da query string.
        $search = $request->input('search', '');
        $sortOrder = $request->input('sort_order', 'asc');
        // Inclui livros para evitar consultas adicionais na listagem.
        $query = Autor::with('livros');

        // Aplica filtro por nome quando foi informado termo de pesquisa.
        if ($search) {
            $query->where('nome', 'like', "%{$search}%");
        }

        // Ordena por nome e pagina os resultados para a vista.
        $query->orderBy('nome', $sortOrder);
        $autores = $query->paginate(10);

        // Renderiza a listagem com estado atual dos filtros.
        return view('autores.index', compact('autores', 'search', 'sortOrder'));
    }

    // Exibe o formulario de criacao de autor.
    public function create()
    {
        // Mostra formulario para cadastro de novo autor.
        return view('autores.create');
    }

    // Valida e grava um novo autor, incluindo upload opcional de foto.
    public function store(Request $request)
    {
        // Valida campos obrigatorios e ficheiro de imagem opcional.
        $data = $request->validate([
            'nome' => 'required',
            'bibliografia' => 'nullable',
            'foto' => 'nullable|image'
        ]);

        // Se houver foto, guarda no disco publico e salva caminho relativo.
        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('autores', 'public');
            $data['foto'] = 'storage/' . $path;
        }

        // Cria registo de autor com os dados validados.
        Autor::create($data);

        // Redireciona para a listagem apos criacao.
        return redirect()->route('autores.index');
    }

    // Exibe detalhes do autor com seus livros e editoras relacionadas.
    public function show(Request $request, Autor $autor)
    {
        // Obtem segmento da rota para validar URL canonica do recurso.
        $parametroRota = (string) $request->segment(2);

        // Redireciona para URL canonica quando a chave da rota diverge.
        if ($parametroRota !== (string) $autor->getRouteKey()) {
            return redirect()->route('autores.show', $autor, 301);
        }

        // Carrega livros e respetivas editoras para exibir no detalhe.
        $autor->load('livros.editora');
        // Consolida editoras unicas associadas aos livros do autor.
        $editoras = $autor->livros->pluck('editora')->filter()->unique('id');

        // Renderiza pagina de detalhe do autor.
        return view('autores.show', compact('autor', 'editoras'));
    }

    // Exibe o formulario de edicao do autor.
    public function edit(Request $request, Autor $autor)
    {
        // Obtem parametro da rota para verificar consistencia da URL.
        $parametroRota = (string) $request->segment(2);

        // Forca URL canonica do recurso quando necessario.
        if ($parametroRota !== (string) $autor->getRouteKey()) {
            return redirect()->route('autores.edit', $autor, 301);
        }

        // Mostra formulario de edicao preenchido com dados atuais.
        return view('autores.edit', compact('autor'));
    }

    // Atualiza os dados do autor e substitui a foto quando enviada.
    public function update(Request $request, Autor $autor)
    {
        // Revalida dados permitidos para atualizacao do autor.
        $data = $request->validate([
            'nome' => 'required',
            'bibliografia' => 'nullable',
            'foto' => 'nullable|image'
        ]);

        // Substitui foto apenas quando novo ficheiro e enviado.
        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('autores', 'public');
            $data['foto'] = 'storage/' . $path;
        } else {
            // Mantem foto atual removendo chave para nao sobrescrever com null.
            unset($data['foto']);
        }

        // Persiste alteracoes do autor e redireciona para detalhe.
        $autor->update($data);
        return redirect()->route('autores.show', $autor);
    }

    // Remove os vinculos com livros antes de excluir o autor.
    public function destroy(Autor $autor)
    {
        // Remove associacoes N:N para evitar referencias pendentes.
        $autor->livros()->detach();

        // Exclui registo do autor e volta a listagem.
        $autor->delete();
        return redirect()->route('autores.index');
    }
}



