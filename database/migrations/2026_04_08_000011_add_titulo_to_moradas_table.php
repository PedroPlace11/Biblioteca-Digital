<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adiciona campo opcional para rotular moradas (ex.: Casa, Trabalho).
        Schema::table('moradas', function (Blueprint $table) {
            $table->string('titulo', 80)->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        // Remove o campo titulo para restaurar o schema anterior.
        Schema::table('moradas', function (Blueprint $table) {
            $table->dropColumn('titulo');
        });
    }
};
