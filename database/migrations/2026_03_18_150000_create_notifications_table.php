<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** Cria a tabela padrão de notificações do Laravel. */
    public function up(): void
    {
        // Cria tabela de notificacoes persistidas (canal database do Laravel).
        Schema::create('notifications', function (Blueprint $table) {
            // Identificador unico da notificacao em formato UUID.
            $table->uuid('id')->primary();
            // Classe/tipo da notificacao (ex.: App\Notifications\...).
            $table->string('type');
            // Relacao polimorfica com o modelo notificado (ex.: User).
            $table->morphs('notifiable');
            // Payload JSON/text com os dados exibidos no frontend.
            $table->text('data');
            // Data/hora em que a notificacao foi lida (null = nao lida).
            $table->timestamp('read_at')->nullable();
            // created_at e updated_at.
            $table->timestamps();
        });
    }

    /** Remove a tabela de notificações. */
    public function down(): void
    {
        // Remove a tabela de notificacoes caso exista.
        Schema::dropIfExists('notifications');
    }
};



