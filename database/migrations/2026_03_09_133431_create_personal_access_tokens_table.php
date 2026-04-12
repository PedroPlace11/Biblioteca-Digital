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
        // Cria tabela para tokens pessoais (ex.: Sanctum API tokens).
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            // Chave primaria da tabela.
            $table->id();
            // Relacao polimorfica com o modelo dono do token (ex.: User).
            $table->morphs('tokenable');
            // Nome amigavel do token (ex.: "App Mobile", "Integração ERP").
            $table->text('name');
            // Hash/token unico com 64 caracteres para autenticacao.
            $table->string('token', 64)->unique();
            // Permissoes/abilities associadas ao token (JSON/text), opcional.
            $table->text('abilities')->nullable();
            // Ultima vez que o token foi usado.
            $table->timestamp('last_used_at')->nullable();
            // Data de expiracao do token com indice para consultas eficientes.
            $table->timestamp('expires_at')->nullable()->index();
            // created_at e updated_at.
            $table->timestamps();
        });
    }

    /**
     * Reverte as migrations.
     */
    public function down(): void
    {
        // Remove a tabela de tokens pessoais caso exista.
        Schema::dropIfExists('personal_access_tokens');
    }
};



