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
        // Adiciona timestamp para registar quando checkout foi finalizado.
        Schema::table('encomendas', function (Blueprint $table) {
            $table->timestamp('checkout_finalizado_em')->nullable()->after('pago_em');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove o campo de controlo de finalizacao do checkout.
        Schema::table('encomendas', function (Blueprint $table) {
            $table->dropColumn('checkout_finalizado_em');
        });
    }
};
