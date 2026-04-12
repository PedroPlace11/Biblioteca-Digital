<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** Adiciona snapshot do cidadão e data prevista de fim às requisições existentes e futuras. */
    public function up(): void
    {
        // Adiciona colunas de snapshot para preservar dados do cidadão na requisição.
        Schema::table('requisicoes', function (Blueprint $table) {
            // Nome do cidadão no momento da requisição (snapshot histórico).
            $table->string('cidadao_nome')->nullable()->after('livro_id');
            // Email do cidadão no momento da requisição.
            $table->string('cidadao_email')->nullable()->after('cidadao_nome');
            // Caminho da foto de perfil (snapshot) para manter histórico visual.
            $table->string('cidadao_foto_path', 2048)->nullable()->after('cidadao_email');
            // Data prevista de fim da requisição (ex.: created_at + 5 dias).
            $table->timestamp('data_fim_prevista')->nullable()->after('updated_at');
        });

        // Carrega utilizadores em memória para evitar queries repetidas por requisição.
        $users = DB::table('users')
            ->select('id', 'name', 'email', 'profile_photo_path')
            ->get()
            ->keyBy('id');

        // Percorre requisições existentes para preencher snapshots e data_fim_prevista retroativamente.
        DB::table('requisicoes')
            ->select('id', 'user_id', 'created_at')
            ->orderBy('id')
            ->get()
            ->each(function ($requisicao) use ($users) {
                // Obtém dados do utilizador associado à requisição atual.
                $user = $users->get($requisicao->user_id);

                // Atualiza cada requisição com os campos de snapshot e prazo previsto.
                DB::table('requisicoes')
                    ->where('id', $requisicao->id)
                    ->update([
                        'cidadao_nome' => $user?->name,
                        'cidadao_email' => $user?->email,
                        'cidadao_foto_path' => $user?->profile_photo_path,
                        // Define prazo padrão de 5 dias a partir da data de criação, quando disponível.
                        'data_fim_prevista' => $requisicao->created_at
                            ? Carbon::parse($requisicao->created_at)->addDays(5)
                            : null,
                    ]);
            });
    }

    /** Remove os campos de snapshot do cidadão e a data prevista de fim. */
    public function down(): void
    {
        // Remove as colunas adicionadas para restaurar o schema anterior.
        Schema::table('requisicoes', function (Blueprint $table) {
            $table->dropColumn([
                'cidadao_nome',
                'cidadao_email',
                'cidadao_foto_path',
                'data_fim_prevista',
            ]);
        });
    }
};



