<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Adiciona controlo de envio do lembrete de entrega por requisição.
     */
    public function up(): void
    {
        // Adiciona timestamp para registar quando o lembrete de devolucao foi enviado.
        Schema::table('requisicoes', function (Blueprint $table) {
            // Null indica que o lembrete ainda nao foi disparado para a requisicao.
            $table->timestamp('lembrete_devolucao_enviado_em')->nullable()->after('data_fim_prevista');
        });
    }

    /**
     * Remove o controlo de envio do lembrete de entrega.
     */
    public function down(): void
    {
        // Remove a coluna de controlo para restaurar o schema anterior.
        Schema::table('requisicoes', function (Blueprint $table) {
            $table->dropColumn('lembrete_devolucao_enviado_em');
        });
    }
};



