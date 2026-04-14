<?php
namespace App\Http\Controllers;

use App\Models\LogSistema;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminUserController extends Controller
{
    // Mostra a área de gestão com número total e lista de administradores.
    public function index()
    {
        // Carrega todos os utilizadores com role admin por ordem decrescente.
        $admins = User::where('role', 'admin')
            ->latest()
            ->get();

        // Calcula total para apresentar no resumo da interface.
        $totalAdmins = $admins->count();

        // Renderiza vista principal da gestão de administradores.
        return view('admin.users.index', compact('admins', 'totalAdmins'));
    }

    // Mostra o formulario para criar um novo utilizador admin.
    public function create()
    {
        // Exibe formulario de criacao de conta administrativa.
        return view('admin.users.create');
    }

    // Cria um novo utilizador com papel de admin.
    public function store(Request $request)
    {
        // Valida dados obrigatorios e confirma password antes de persistir.
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Cria admin com nome inicial derivado da parte local do email.
        $admin = User::create([
            'name' => Str::before($data['email'], '@'),
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'admin',
        ]);

        if (Schema::hasTable('logs')) {
            $route = request()->route();
            $routeName = $route?->getName();

            LogSistema::query()->create([
                'ocorrido_em' => now(),
                'user_id' => $admin->id,
                'user_nome' => $admin->name,
                'modulo' => 'Admins',
                'objeto_id' => (string) $admin->id,
                'alteracao' => 'Criacao de conta de admin',
                'ip' => request()->ip(),
                'browser' => (string) request()->userAgent(),
                'metodo' => request()->method(),
                'url' => request()->fullUrl(),
                'route_name' => $routeName,
            ]);
        }

        // Regressa a listagem com mensagem de sucesso.
        return redirect()->route('admins.index')->with('success', 'Novo admin criado com sucesso.');
    }

    // Remove um administrador, impedindo a remoção da própria conta autenticada.
    public function destroy(User $admin)
    {
        // Impede operacao caso o registo recebido nao seja efetivamente admin.
        if ($admin->role !== 'admin') {
            return redirect()->route('admins.index')->with('error', 'O utilizador selecionado não é um admin.');
        }

        // Bloqueia auto-remocao para evitar perda de acesso administrativo.
        if (Auth::id() === $admin->id) {
            return redirect()->route('admins.index')->with('error', 'Não pode apagar a sua própria conta de admin.');
        }

        // Remove o administrador selecionado.
        $admin->delete();

        // Retorna para a listagem confirmando a eliminacao.
        return redirect()->route('admins.index')->with('success', 'Admin apagado com sucesso.');
    }
}



