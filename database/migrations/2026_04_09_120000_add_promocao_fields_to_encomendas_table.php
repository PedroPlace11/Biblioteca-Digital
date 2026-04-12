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
        // Adiciona campos de promocao/desconto na tabela encomendas.
        Schema::table('encomendas', function (Blueprint $table) {
            // Codigo promocional aplicado no checkout (quando existir).
            $table->string('codigo_promocional', 40)->nullable()->after('total');
            // Percentual de desconto aplicado (0 a 100, conforme regra da aplicacao).
            $table->unsignedTinyInteger('desconto_percentual')->default(0)->after('codigo_promocional');
            // Valor monetario total do desconto aplicado.
            $table->decimal('valor_desconto', 10, 2)->default(0)->after('desconto_percentual');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove os campos de promocao para restaurar o schema anterior.
        Schema::table('encomendas', function (Blueprint $table) {
            $table->dropColumn(['codigo_promocional', 'desconto_percentual', 'valor_desconto']);
        });
    }
};
