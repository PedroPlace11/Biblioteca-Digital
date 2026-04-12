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
        // Cria tabela de carrinhos (um carrinho por utilizador).
        Schema::create('carrinhos', function (Blueprint $table) {
            // Chave primaria da tabela.
            $table->id();
            // FK unica para user: garante relacao 1:1 entre utilizador e carrinho.
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            // Marca quando alerta de abandono foi enviado.
            $table->timestamp('lembrete_abandono_enviado_em')->nullable();
            // created_at e updated_at.
            $table->timestamps();
        });

        // Cria itens do carrinho (livros adicionados e quantidades).
        Schema::create('carrinho_itens', function (Blueprint $table) {
            // Chave primaria da tabela.
            $table->id();
            // FK para carrinho dono do item.
            $table->foreignId('carrinho_id')->constrained('carrinhos')->cascadeOnDelete();
            // FK para livro adicionado ao carrinho.
            $table->foreignId('livro_id')->constrained('livros')->cascadeOnDelete();
            // Quantidade do livro no carrinho (padrao 1).
            $table->unsignedInteger('quantidade')->default(1);
            // Preco unitario no momento da adicao.
            $table->decimal('preco_unitario', 10, 2);
            // created_at e updated_at.
            $table->timestamps();

            // Impede duplicidade do mesmo livro no mesmo carrinho.
            $table->unique(['carrinho_id', 'livro_id']);
        });

        // Cria tabela de encomendas (checkout convertido de carrinho para pedido).
        Schema::create('encomendas', function (Blueprint $table) {
            // Chave primaria da tabela.
            $table->id();
            // FK do utilizador que realizou a encomenda.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Estado do fluxo de pagamento/expedicao.
            $table->string('estado', 40)->default('pendente_pagamento');
            // Dados de entrega (snapshot no momento da compra).
            $table->string('nome_destinatario');
            $table->string('telemovel_destinatario', 40);
            $table->string('morada_linha_1');
            $table->string('morada_linha_2')->nullable();
            $table->string('codigo_postal', 20);
            $table->string('cidade', 120);
            $table->string('pais', 2)->default('PT');
            // Total financeiro da encomenda.
            $table->decimal('total', 10, 2);
            // IDs de integracao com Stripe (sessao checkout e payment intent).
            $table->string('stripe_checkout_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable();
            // Data/hora de confirmacao de pagamento.
            $table->timestamp('pago_em')->nullable();
            // created_at e updated_at.
            $table->timestamps();
        });

        // Cria itens da encomenda com snapshot dos dados do livro no momento da compra.
        Schema::create('encomenda_itens', function (Blueprint $table) {
            // Chave primaria da tabela.
            $table->id();
            // FK para encomenda pai.
            $table->foreignId('encomenda_id')->constrained('encomendas')->cascadeOnDelete();
            // FK opcional para livro (null se livro for removido depois).
            $table->foreignId('livro_id')->nullable()->constrained('livros')->nullOnDelete();
            // Snapshot textual para preservar historico mesmo se dados do livro mudarem.
            $table->string('livro_nome');
            $table->string('livro_isbn', 30)->nullable();
            // Quantidade e valores financeiros por item.
            $table->unsignedInteger('quantidade');
            $table->decimal('preco_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);
            // created_at e updated_at.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove tabelas em ordem inversa de dependencia para evitar erro de FK.
        Schema::dropIfExists('encomenda_itens');
        Schema::dropIfExists('encomendas');
        Schema::dropIfExists('carrinho_itens');
        Schema::dropIfExists('carrinhos');
    }
};
