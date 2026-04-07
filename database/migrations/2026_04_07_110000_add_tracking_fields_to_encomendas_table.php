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
        Schema::table('encomendas', function (Blueprint $table) {
            $table->string('transportadora', 40)->nullable()->after('stripe_payment_intent_id');
            $table->string('numero_rastreio', 60)->nullable()->after('transportadora');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('encomendas', function (Blueprint $table) {
            $table->dropColumn(['transportadora', 'numero_rastreio']);
        });
    }
};
