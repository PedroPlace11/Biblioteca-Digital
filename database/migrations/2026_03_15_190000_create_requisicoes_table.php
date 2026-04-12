<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** Cria a tabela de requisicoes de livros com chaves estrangeiras e constraint de unicidade. */
    public function up(): void
    {
        // Cria a tabela que regista requisicoes de livros por utilizador.
        Schema::create('requisicoes', function (Blueprint $table) {
            // Chave primaria da tabela.
            $table->id();
            // FK para utilizador que realizou a requisicao (remove requisicoes ao apagar utilizador).
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // FK para livro requisitado (remove requisicoes ao apagar livro).
            $table->foreignId('livro_id')->constrained('livros')->cascadeOnDelete();
            // created_at e updated_at.
            $table->timestamps();

            // Impede que o mesmo utilizador tenha requisicoes duplicadas do mesmo livro.
            $table->unique(['user_id', 'livro_id']);
        });
    }

    /** Remove a tabela de requisicoes. */
    public function down(): void
    {
        // Remove a tabela de requisicoes caso exista.
        Schema::dropIfExists('requisicoes');
    }
};



