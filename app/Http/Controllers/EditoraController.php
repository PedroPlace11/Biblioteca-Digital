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
        $search = $request->input('search', '');
        $sortOrder = $request->input('sort_order', 'asc');
        $query = Editora::with('livros');

        if ($search) {
            $query->where('nome', 'like', "%{$search}%");
        }

        $query->orderBy('nome', $sortOrder);
        $editoras = $query->paginate(10);
        return view('editoras.index', compact('editoras', 'search', 'sortOrder'));
    }

    // Exibe o formulario para criar uma nova editora.
    public function create()
    {
        return view('editoras.create');
    }

    // Salva uma editora e armazena o logotipo quando enviado.
    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => 'required',
            'logotipo' => 'nullable|image'
        ]);
        if ($request->hasFile('logotipo')) {
            $path = $request->file('logotipo')->store('editoras', 'public');
            $data['logotipo'] = 'storage/' . $path;
        }
        Editora::create($data);
        return redirect()->route('editoras.index');
    }

    // Exibe a pagina da editora com livros publicados e autores relacionados.
    public function show(Request $request, Editora $editora)
    {
        $parametroRota = (string) $request->segment(2);

        if ($parametroRota !== (string) $editora->getRouteKey()) {
            return redirect()->route('editoras.show', $editora, 301);
        }

        $livros = $editora->livros()->with('autores')->get();
        $autores = $livros->flatMap(function ($livro) {
            return $livro->autores;
        })->unique('id');
        return view('editoras.show', compact('editora', 'livros', 'autores'));
    }

    // Exibe o formulario de edicao da editora.
    public function edit(Request $request, Editora $editora)
    {
        $parametroRota = (string) $request->segment(2);

        if ($parametroRota !== (string) $editora->getRouteKey()) {
            return redirect()->route('editoras.edit', $editora, 301);
        }

        return view('editoras.edit', compact('editora'));
    }

    // Atualiza os dados da editora e substitui o logotipo quando enviado.
    public function update(Request $request, Editora $editora)
    {
        $data = $request->validate([
            'nome' => 'required',
            'logotipo' => 'nullable|image'
        ]);

        if ($request->hasFile('logotipo')) {
            $path = $request->file('logotipo')->store('editoras', 'public');
            $data['logotipo'] = 'storage/' . $path;
        } else {
            unset($data['logotipo']);
        }

        $editora->update($data);
        return redirect()->route('editoras.show', $editora);
    }

    // Remove livros e vinculos relacionados antes de excluir a editora.
    public function destroy(Editora $editora)
    {
        DB::transaction(function () use ($editora) {
            $editora->livros()->get()->each(function (Livro $livro) {
                $livro->autores()->detach();
                $livro->delete();
            });

            $editora->delete();
        });

        return redirect()->route('editoras.index');
    }
}



