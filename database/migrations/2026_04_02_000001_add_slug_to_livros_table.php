<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        // Adiciona coluna slug inicialmente nullable para permitir preenchimento gradual.
        Schema::table('livros', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('nome');
        });

        // Carrega livros existentes para gerar slugs retroativamente.
        $livros = DB::table('livros')
            ->select('id', 'nome')
            ->orderBy('id')
            ->get();

        // Lista local para controlar slugs já usados durante o backfill.
        $usedSlugs = [];

        // Gera slug para cada livro existente.
        foreach ($livros as $livro) {
            // Converte nome para formato URL-friendly.
            $base = Str::slug((string) $livro->nome);
            // Fallback caso nome gere slug vazio.
            $base = $base !== '' ? $base : 'livro';

            // Inicia com slug base e incrementa sufixo em caso de colisão.
            $slug = $base;
            $suffix = 2;

            // Garante unicidade dentro do conjunto processado nesta migration.
            while (in_array($slug, $usedSlugs, true)) {
                $slug = $base . '-' . $suffix;
                $suffix++;
            }

            // Marca slug como utilizado.
            $usedSlugs[] = $slug;

            // Persiste slug no livro atual.
            DB::table('livros')
                ->where('id', $livro->id)
                ->update(['slug' => $slug]);
        }

        // Após preencher todos os registros, aplica restrição de unicidade no banco.
        Schema::table('livros', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        // Remove índice único e coluna slug para restaurar schema anterior.
        Schema::table('livros', function (Blueprint $table) {
            $table->dropUnique('livros_slug_unique');
            $table->dropColumn('slug');
        });
    }
};
