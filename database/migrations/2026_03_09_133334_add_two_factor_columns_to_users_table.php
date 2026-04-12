<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executa as migrations.
     */
    public function up(): void
    {
        // Adiciona colunas de suporte a autenticacao de dois fatores na tabela users.
        Schema::table('users', function (Blueprint $table) {
            // Segredo principal do 2FA (normalmente usado por apps autenticadoras).
            $table->text('two_factor_secret')
                ->after('password')
                ->nullable();

            // Codigos de recuperacao para acesso quando o segundo fator nao estiver disponivel.
            $table->text('two_factor_recovery_codes')
                ->after('two_factor_secret')
                ->nullable();

            // Data/hora em que o 2FA foi confirmado pelo utilizador.
            $table->timestamp('two_factor_confirmed_at')
                ->after('two_factor_recovery_codes')
                ->nullable();
        });
    }

    /**
     * Reverte as migrations.
     */
    public function down(): void
    {
        // Remove as colunas de 2FA para voltar ao estado anterior da tabela users.
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
            ]);
        });
    }
};



