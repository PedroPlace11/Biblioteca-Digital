<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('ocorrido_em');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_nome')->nullable();
            $table->string('modulo');
            $table->string('objeto_id')->nullable();
            $table->text('alteracao');
            $table->string('ip', 45)->nullable();
            $table->text('browser')->nullable();
            $table->string('metodo', 10);
            $table->text('url');
            $table->string('route_name')->nullable();
            $table->timestamps();

            $table->index(['ocorrido_em']);
            $table->index(['modulo']);
            $table->index(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
