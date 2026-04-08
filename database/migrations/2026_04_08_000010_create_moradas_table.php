<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moradas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nome_destinatario');
            $table->string('telemovel_destinatario', 40);
            $table->string('morada_linha_1');
            $table->string('morada_linha_2')->nullable();
            $table->string('codigo_postal', 20);
            $table->string('cidade', 120);
            $table->string('pais', 2);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moradas');
    }
};
