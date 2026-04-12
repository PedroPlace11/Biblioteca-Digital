<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cria tabela de moradas de entrega associadas a utilizadores.
        Schema::create('moradas', function (Blueprint $table) {
            // Chave primaria da tabela.
            $table->id();
            // FK do utilizador dono da morada (apaga moradas ao apagar utilizador).
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Dados de destinatario e endereco.
            $table->string('nome_destinatario');
            $table->string('telemovel_destinatario', 40);
            $table->string('morada_linha_1');
            $table->string('morada_linha_2')->nullable();
            $table->string('codigo_postal', 20);
            $table->string('cidade', 120);
            $table->string('pais', 2);
            // created_at e updated_at.
            $table->timestamps();

            // Indice para listagens por utilizador e ordenacao temporal.
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        // Remove a tabela moradas caso exista.
        Schema::dropIfExists('moradas');
    }
};
