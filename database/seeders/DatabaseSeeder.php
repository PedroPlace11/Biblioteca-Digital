<?php



namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

// Seeder principal que orquestra a carga de dados iniciais.
class DatabaseSeeder extends Seeder
{
    /**
     * Executa os seeders da aplicacao.
     */
    public function run(): void
    {
        // Executa seeders base em ordem para respeitar dependencias (autor/editora antes de livro).
        $this->call([
            AutorSeeder::class,
            EditoraSeeder::class,
            LivroSeeder::class
        ]);

        // Cria utilizador administrador inicial para acesso ao painel de administracao.
        // Credenciais destinam-se ao ambiente local de desenvolvimento.
        User::create([
            'name' => 'Pedro Santos',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'numero_leitor_seq' => 1,
            'numero_leitor' => 'L000001',
        ]);
    }
}



