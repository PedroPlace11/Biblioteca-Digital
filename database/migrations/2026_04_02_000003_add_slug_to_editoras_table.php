<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        // Adiciona coluna slug inicialmente nullable para permitir backfill antes da constraint unica.
        Schema::table('editoras', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('nome');
        });

        // Carrega editoras existentes para gerar slugs retroativamente.
        $editoras = DB::table('editoras')
            ->select('id', 'nome')
            ->orderBy('id')
            ->get();

        // Lista local para evitar colisoes de slug durante processamento.
        $usedSlugs = [];

        // Gera slug para cada editora.
        foreach ($editoras as $editora) {
            // Converte nome para formato URL-friendly.
            $base = Str::slug((string) $editora->nome);
            // Fallback para nomes que resultem em slug vazio.
            $base = $base !== '' ? $base : 'editora';

            // Inicia com slug base e incrementa sufixo em caso de colisao.
            $slug = $base;
            $suffix = 2;

            // Garante unicidade entre os registros processados.
            while (in_array($slug, $usedSlugs, true)) {
                $slug = $base . '-' . $suffix;
                $suffix++;
            }

            // Marca slug como utilizado.
            $usedSlugs[] = $slug;

            // Persiste slug na editora atual.
            DB::table('editoras')
                ->where('id', $editora->id)
                ->update(['slug' => $slug]);
        }

        // Aplica restricao de unicidade apos preencher todos os slugs.
        Schema::table('editoras', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        // Remove indice unico e coluna slug para restaurar o schema anterior.
        Schema::table('editoras', function (Blueprint $table) {
            $table->dropUnique('editoras_slug_unique');
            $table->dropColumn('slug');
        });
    }
};
