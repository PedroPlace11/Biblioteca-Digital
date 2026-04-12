<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executa as migrations.
     */
    public function up(): void
    {
        // Adiciona coluna bibliografia na tabela de autores.
        Schema::table('autors', function (Blueprint $table) {
            // Campo opcional para biografia/descricao do autor, posicionado apos a foto.
            $table->text('bibliografia')->nullable()->after('foto');
        });
    }

    /**
     * Reverte as migrations.
     */
    public function down(): void
    {
        // Remove a coluna bibliografia para restaurar o schema anterior.
        Schema::table('autors', function (Blueprint $table) {
            $table->dropColumn('bibliografia');
        });
    }
};



