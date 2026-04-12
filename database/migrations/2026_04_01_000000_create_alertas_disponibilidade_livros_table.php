<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** Cria a lista de interessados em alerta quando um livro voltar a ficar disponível. */
    public function up(): void
    {
        // Cria tabela de subscricoes para avisar utilizadores quando livro voltar a estar disponivel.
        Schema::create('alertas_disponibilidade_livros', function (Blueprint $table) {
            // Chave primaria da tabela.
            $table->id();
            // FK para utilizador que pediu o alerta (apaga alerta ao apagar utilizador).
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // FK para livro monitorizado (apaga alerta ao apagar livro).
            $table->foreignId('livro_id')->constrained('livros')->cascadeOnDelete();
            // created_at e updated_at.
            $table->timestamps();

            // Impede alertas duplicados para o mesmo utilizador e livro.
            $table->unique(['user_id', 'livro_id']);
            // Otimiza pesquisas por livro para disparo de notificacoes em lote.
            $table->index('livro_id');
        });
    }

    /** Remove a tabela de alertas de disponibilidade. */
    public function down(): void
    {
        // Remove a tabela de alertas de disponibilidade caso exista.
        Schema::dropIfExists('alertas_disponibilidade_livros');
    }
};
