<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** Adiciona campos para confirmação de receção e cálculo de dias decorridos. */
    public function up(): void
    {
        // Adiciona colunas para controlar o fluxo de devolucao e confirmacao administrativa.
        Schema::table('requisicoes', function (Blueprint $table) {
            // Momento em que o utilizador solicitou a devolucao do livro.
            $table->timestamp('devolucao_solicitada_em')->nullable()->after('data_fim_prevista');
            // Momento real em que a rececao da devolucao foi confirmada.
            $table->timestamp('data_recepcao_real')->nullable()->after('devolucao_solicitada_em');
            // Quantidade de dias decorridos entre criacao da requisicao e rececao real.
            $table->unsignedInteger('dias_decorridos')->nullable()->after('data_recepcao_real');
            // Admin responsavel pela confirmacao; se admin for removido, valor fica null.
            $table->foreignId('confirmado_por_admin_id')->nullable()->after('dias_decorridos')->constrained('users')->nullOnDelete();
        });
    }

    /** Remove os campos de confirmação de receção. */
    public function down(): void
    {
        // Reverte os campos de confirmacao para restaurar o schema anterior.
        Schema::table('requisicoes', function (Blueprint $table) {
            // Remove primeiro a FK para manter consistencia na reversao.
            $table->dropConstrainedForeignId('confirmado_por_admin_id');
            // Remove colunas auxiliares de devolucao/rececao.
            $table->dropColumn([
                'devolucao_solicitada_em',
                'data_recepcao_real',
                'dias_decorridos',
            ]);
        });
    }
};



