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
        // Cria a tabela de autores do catalogo.
        Schema::create('autors', function (Blueprint $table) {
            // Chave primaria da tabela.
            $table->id();
            // Nome completo do autor.
            $table->string('nome');
            // Caminho/URL da foto do autor (opcional).
            $table->string('foto')->nullable();
            // created_at e updated_at.
            $table->timestamps();
        });
    }

    /**
     * Reverte as migrations.
     */
    public function down(): void
    {
        // Remove a tabela de autores caso exista.
        Schema::dropIfExists('autors');
    }
};



