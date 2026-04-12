<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** Adiciona soft deletes e remove a constraint de unicidade para permitir historico de requisicoes. */
    public function up(): void
    {
        // Ajusta a tabela requisicoes para manter historico de emprestimos/devolucoes.
        Schema::table('requisicoes', function (Blueprint $table) {
            // Adiciona coluna deleted_at para permitir soft delete (registo historico sem apagar fisicamente).
            $table->softDeletes();
            // Remove unicidade user_id + livro_id para permitir multiplas requisicoes do mesmo livro ao longo do tempo.
            $table->dropUnique(['user_id', 'livro_id']);
        });
    }

    /** Reverte a adicao de soft deletes e restaura a constraint de unicidade. */
    public function down(): void
    {
        // Restaura o schema anterior sem historico por soft delete.
        Schema::table('requisicoes', function (Blueprint $table) {
            // Remove coluna deleted_at criada pelo softDeletes().
            $table->dropSoftDeletes();
            // Restaura regra antiga: um utilizador nao pode ter mais de uma requisicao do mesmo livro.
            $table->unique(['user_id', 'livro_id']);
        });
    }
};



