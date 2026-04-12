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
        // Cria a tabela principal de livros do catalogo.
        Schema::create('livros', function (Blueprint $table) {

            // Chave primaria da tabela.
            $table->id();
            // ISBN unico para identificar cada livro sem duplicidade.
            $table->string('isbn')->unique();
            // Nome/titulo do livro.
            $table->string('nome');
            // Relacao obrigatoria com a editora (chave estrangeira).
            $table->foreignId('editora_id')->constrained();
            // Campo opcional para descricao, resumo ou bibliografia.
            $table->text('bibliografia')->nullable();
            // Caminho/URL da imagem de capa (opcional).
            $table->string('imagem_capa')->nullable();
            // Preco do livro com 8 digitos totais e 2 casas decimais.
            $table->decimal('preco',8,2);
            // created_at e updated_at.
            $table->timestamps();
        });
    }

    /**
     * Reverte as migrations.
     */
    public function down(): void
    {
        // Remove a tabela de livros caso exista.
        Schema::dropIfExists('livros');
    }
};



