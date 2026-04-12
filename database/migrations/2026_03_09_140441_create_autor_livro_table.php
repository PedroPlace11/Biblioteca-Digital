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
        // Cria tabela pivot para relacao muitos-para-muitos entre autores e livros.
        Schema::create('autor_livro', function (Blueprint $table) {
            // Chave primaria da tabela pivot.
            $table->id();
            // FK para autor relacionado.
            $table->foreignId('autor_id')->constrained();
            // FK para livro relacionado.
            $table->foreignId('livro_id')->constrained();
        });
    }

    /**
     * Reverte as migrations.
     */
    public function down(): void
    {
        // Remove a tabela pivot de associacao entre autores e livros.
        Schema::dropIfExists('autor_livro');
    }
};



