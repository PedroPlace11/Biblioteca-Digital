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
        // Cria a tabela de editoras do catalogo.
        Schema::create('editoras', function (Blueprint $table) {
            // Chave primaria da tabela.
            $table->id();
            // Nome da editora.
            $table->string('nome');
            // Caminho/URL do logotipo da editora (opcional).
            $table->string('logotipo')->nullable();
            // created_at e updated_at.
            $table->timestamps();
        });
    }

    /**
     * Reverte as migrations.
     */
    public function down(): void
    {
        // Remove a tabela de editoras caso exista.
        Schema::dropIfExists('editoras');
    }
};



