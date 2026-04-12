<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cria tabela de reviews/comentarios dos utilizadores sobre livros.
        Schema::create('reviews', function (Blueprint $table) {
            // Chave primaria da tabela.
            $table->id();
            // FK para utilizador autor da review (apaga reviews ao apagar utilizador).
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // FK para livro avaliado (apaga reviews ao apagar livro).
            $table->foreignId('livro_id')->constrained('livros')->onDelete('cascade');
            // Texto principal da review submetida.
            $table->text('conteudo');
            // Avaliacao numerica opcional (ex.: 1 a 5, conforme regra da aplicacao).
            $table->unsignedTinyInteger('rating')->nullable();
            // Estado de moderacao: suspenso (pendente), ativo (publicado), recusado.
            $table->enum('estado', ['suspenso', 'ativo', 'recusado'])->default('suspenso');
            // Motivo da rejeicao quando estado = recusado (opcional).
            $table->text('justificacao')->nullable();
            // created_at e updated_at.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Remove a tabela de reviews caso exista.
        Schema::dropIfExists('reviews');
    }
};
