<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Preenche número de leitor em utilizadores já existentes que ainda não tenham número.
     * Também atualiza snapshot de requisições sem número.
     */
    public function up(): void
    {
        // Descobre maior sequencial ja usado para continuar numeracao sem duplicar.
        $maxSequencial = DB::table('users')
            ->whereNotNull('numero_leitor')
            ->pluck('numero_leitor')
            ->map(function ($numero) {
                // Extrai apenas os digitos do formato L000001.
                return (int) preg_replace('/\D+/', '', (string) $numero);
            })
            ->max() ?? 0;

        // Define proximo numero sequencial disponivel.
        $proximoSequencial = $maxSequencial + 1;

        // Preenche numero_leitor para utilizadores que ainda nao possuem valor.
        DB::table('users')
            ->whereNull('numero_leitor')
            ->orderBy('id')
            ->select('id')
            ->get()
            ->each(function ($user) use (&$proximoSequencial) {
                // Atribui numero no formato L000001 e incrementa sequencial.
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'numero_leitor' => sprintf('L%06d', $proximoSequencial++),
                    ]);
            });

        // Atualiza snapshots de requisicoes que ainda estao sem numero de leitor.
        DB::table('requisicoes')
            ->leftJoin('users', 'users.id', '=', 'requisicoes.user_id')
            ->whereNull('requisicoes.cidadao_numero_leitor')
            ->whereNotNull('users.numero_leitor')
            ->select('requisicoes.id', 'users.numero_leitor')
            ->orderBy('requisicoes.id')
            ->get()
            ->each(function ($requisicao) {
                // Copia numero de leitor atual do user para o campo snapshot da requisicao.
                DB::table('requisicoes')
                    ->where('id', $requisicao->id)
                    ->update([
                        'cidadao_numero_leitor' => $requisicao->numero_leitor,
                    ]);
            });
    }

    public function down(): void
    {
        // Migracao de dados sem reversao segura.
    }
};



