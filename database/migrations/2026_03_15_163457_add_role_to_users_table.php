<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Adiciona coluna de perfil/permissao do utilizador na tabela users.
        Schema::table('users', function (Blueprint $table) {
            // Define role com valor padrao "cidadao" para novos registos.
            $table->string('role')->default('cidadao');
        });
    }

    public function down()
    {
        // Remove a coluna role para restaurar o schema anterior.
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};



