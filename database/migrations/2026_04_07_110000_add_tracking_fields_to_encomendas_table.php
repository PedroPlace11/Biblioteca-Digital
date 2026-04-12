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
        // Adiciona campos de rastreio logístico na tabela encomendas.
        Schema::table('encomendas', function (Blueprint $table) {
            // Nome/codigo da transportadora usada no envio.
            $table->string('transportadora', 40)->nullable()->after('stripe_payment_intent_id');
            // Numero de rastreio para acompanhamento da entrega.
            $table->string('numero_rastreio', 60)->nullable()->after('transportadora');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove os campos de rastreio para restaurar o schema anterior.
        Schema::table('encomendas', function (Blueprint $table) {
            $table->dropColumn(['transportadora', 'numero_rastreio']);
        });
    }
};
