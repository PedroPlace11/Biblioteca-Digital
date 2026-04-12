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
        // Adiciona campos de faturacao para emissao de fatura com dados fiscais.
        Schema::table('encomendas', function (Blueprint $table) {
            // Indica se cliente solicitou fatura com NIF.
            $table->boolean('fatura_com_nif')->default(false)->after('pais');
            // NIF para faturacao (opcional).
            $table->string('fatura_nif')->nullable()->after('fatura_com_nif');
            // Nome fiscal a constar na fatura (opcional).
            $table->string('fatura_nome')->nullable()->after('fatura_nif');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove os campos de faturacao para restaurar o schema anterior.
        Schema::table('encomendas', function (Blueprint $table) {
            $table->dropColumn(['fatura_com_nif', 'fatura_nif', 'fatura_nome']);
        });
    }
};
