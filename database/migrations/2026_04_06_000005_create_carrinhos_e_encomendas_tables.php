<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('carrinhos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamp('lembrete_abandono_enviado_em')->nullable();
            $table->timestamps();
        });

        Schema::create('carrinho_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrinho_id')->constrained('carrinhos')->cascadeOnDelete();
            $table->foreignId('livro_id')->constrained('livros')->cascadeOnDelete();
            $table->unsignedInteger('quantidade')->default(1);
            $table->decimal('preco_unitario', 10, 2);
            $table->timestamps();

            $table->unique(['carrinho_id', 'livro_id']);
        });

        Schema::create('encomendas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('estado', 40)->default('pendente_pagamento');
            $table->string('nome_destinatario');
            $table->string('telemovel_destinatario', 40);
            $table->string('morada_linha_1');
            $table->string('morada_linha_2')->nullable();
            $table->string('codigo_postal', 20);
            $table->string('cidade', 120);
            $table->string('pais', 2)->default('PT');
            $table->decimal('total', 10, 2);
            $table->string('stripe_checkout_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->timestamp('pago_em')->nullable();
            $table->timestamps();
        });

        Schema::create('encomenda_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encomenda_id')->constrained('encomendas')->cascadeOnDelete();
            $table->foreignId('livro_id')->nullable()->constrained('livros')->nullOnDelete();
            $table->string('livro_nome');
            $table->string('livro_isbn', 30)->nullable();
            $table->unsignedInteger('quantidade');
            $table->decimal('preco_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encomenda_itens');
        Schema::dropIfExists('encomendas');
        Schema::dropIfExists('carrinho_itens');
        Schema::dropIfExists('carrinhos');
    }
};
