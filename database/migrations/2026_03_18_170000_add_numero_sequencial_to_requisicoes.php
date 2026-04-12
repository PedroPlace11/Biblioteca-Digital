<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Adiciona numero sequencial das requisicoes e preenche registos existentes.
     */
    public function up(): void
    {
        // Adiciona coluna técnica sequencial para identificar requisições em ordem crescente.
        Schema::table('requisicoes', function (Blueprint $table) {
            $table->unsignedBigInteger('numero_requisicao_seq')->nullable()->unique()->after('id');
        });

        // Carrega todas as requisições existentes para preencher o sequencial retroativamente.
        $requisicoes = DB::table('requisicoes')
            ->select('id')
            ->orderBy('id')
            ->get();

        // Contador sequencial inicial.
        $seq = 0;

        // Preenche cada registro com sequência incremental conforme ordem por ID.
        foreach ($requisicoes as $requisicao) {
            $seq++;

            // Atualiza a requisição atual com o número sequencial calculado.
            DB::table('requisicoes')
                ->where('id', $requisicao->id)
                ->update(['numero_requisicao_seq' => $seq]);
        }
    }

    /**
     * Remove o numero sequencial das requisicoes.
     */
    public function down(): void
    {
        // Remove índice único e coluna técnica de sequência para restaurar schema anterior.
        Schema::table('requisicoes', function (Blueprint $table) {
            $table->dropUnique('requisicoes_numero_requisicao_seq_unique');
            $table->dropColumn('numero_requisicao_seq');
        });
    }
};



