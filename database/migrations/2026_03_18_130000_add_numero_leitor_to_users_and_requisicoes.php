<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** Adiciona número de leitor ao utilizador e snapshot nas requisições. */
    public function up(): void
    {
        // Adiciona coluna de numero de leitor em users (unica) para identificacao do cidadao.
        Schema::table('users', function (Blueprint $table) {
            $table->string('numero_leitor', 20)->nullable()->unique()->after('email');
        });

        // Descobre maior sequencial ja existente para continuar numeracao sem colisao.
        $maxSequencial = DB::table('users')
            ->whereNotNull('numero_leitor')
            ->pluck('numero_leitor')
            ->map(function ($numero) {
                // Extrai apenas os digitos (ex.: L000123 -> 123).
                return (int) preg_replace('/\D+/', '', (string) $numero);
            })
            ->max() ?? 0;

        // Define proximo numero sequencial disponivel.
        $proximoSequencial = $maxSequencial + 1;

        // Preenche numero_leitor para cidadaos que ainda nao possuem numero.
        DB::table('users')
            ->where('role', 'cidadao')
            ->whereNull('numero_leitor')
            ->orderBy('id')
            ->select('id')
            ->get()
            ->each(function ($user) use (&$proximoSequencial) {
                // Atribui formato padrao L000001 e incrementa sequencial.
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'numero_leitor' => sprintf('L%06d', $proximoSequencial++),
                    ]);
            });

        // Adiciona snapshot do numero de leitor na tabela requisicoes.
        Schema::table('requisicoes', function (Blueprint $table) {
            $table->string('cidadao_numero_leitor', 20)->nullable()->after('cidadao_email');
        });

        // Backfill do snapshot usando join com users para manter historico por requisicao.
        DB::table('requisicoes')
            ->leftJoin('users', 'users.id', '=', 'requisicoes.user_id')
            ->select('requisicoes.id', 'users.numero_leitor')
            ->orderBy('requisicoes.id')
            ->get()
            ->each(function ($requisicao) {
                // Copia numero de leitor atual do user para o snapshot da requisicao.
                DB::table('requisicoes')
                    ->where('id', $requisicao->id)
                    ->update([
                        'cidadao_numero_leitor' => $requisicao->numero_leitor,
                    ]);
            });
    }

    /** Remove os campos de número de leitor. */
    public function down(): void
    {
        // Remove snapshot do numero de leitor em requisicoes.
        Schema::table('requisicoes', function (Blueprint $table) {
            $table->dropColumn('cidadao_numero_leitor');
        });

        // Remove unicidade e coluna numero_leitor em users, restaurando schema anterior.
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_numero_leitor_unique');
            $table->dropColumn('numero_leitor');
        });
    }
};



