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
        $search = $request->input('search', '');
        $sortOrder = $request->input('sort_order', 'asc');
        $query = Autor::with('livros');

        if ($search) {
            $query->where('nome', 'like', "%{$search}%");
        }

        $query->orderBy('nome', $sortOrder);
        $autores = $query->paginate(10);
        return view('autores.index', compact('autores', 'search', 'sortOrder'));
    }

    // Exibe o formulario de criacao de autor.
    public function create()
    {
        return view('autores.create');
    }

    // Valida e grava um novo autor, incluindo upload opcional de foto.
    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => 'required',
            'bibliografia' => 'nullable',
            'foto' => 'nullable|image'
        ]);
        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('autores', 'public');
            $data['foto'] = 'storage/' . $path;
        }
        Autor::create($data);
        return redirect()->route('autores.index');
    }

    // Exibe detalhes do autor com seus livros e editoras relacionadas.
    public function show(Autor $autor)
    {
        $autor->load('livros.editora');
        $editoras = $autor->livros->pluck('editora')->filter()->unique('id');
        return view('autores.show', compact('autor', 'editoras'));
    }

    // Exibe o formulario de edicao do autor.
    public function edit(Autor $autor)
    {
        return view('autores.edit', compact('autor'));
    }

    // Atualiza os dados do autor e substitui a foto quando enviada.
    public function update(Request $request, Autor $autor)
    {
        $data = $request->validate([
            'nome' => 'required',
            'bibliografia' => 'nullable',
            'foto' => 'nullable|image'
        ]);

        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('autores', 'public');
            $data['foto'] = 'storage/' . $path;
        } else {
            unset($data['foto']);
        }

        $autor->update($data);
        return redirect()->route('autores.show', $autor->id);
    }

    // Remove os vinculos com livros antes de excluir o autor.
    public function destroy(Autor $autor)
    {
        $autor->livros()->detach();
        $autor->delete();
        return redirect()->route('autores.index');
    }
}



