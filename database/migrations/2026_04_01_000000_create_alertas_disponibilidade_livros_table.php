<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** Cria a lista de interessados em alerta quando um livro voltar a ficar disponível. */
    public function up(): void
    {
        Schema::create('alertas_disponibilidade_livros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('livro_id')->constrained('livros')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'livro_id']);
            $table->index('livro_id');
        });
    }

    /** Remove a tabela de alertas de disponibilidade. */
    public function down(): void
    {
        Schema::dropIfExists('alertas_disponibilidade_livros');
    }
};
