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
        Schema::table('reviews', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('livro_id');
        });

        // Carrega reviews com nome do livro para gerar slugs retroativamente.
        $reviews = DB::table('reviews')
            ->join('livros', 'reviews.livro_id', '=', 'livros.id')
            ->select('reviews.id', 'livros.nome as livro_nome')
            ->orderBy('reviews.id')
            ->get();

        // Lista local para evitar colisoes de slug durante processamento.
        $usedSlugs = [];

        // Gera slug para cada review com base no nome do livro associado.
        foreach ($reviews as $review) {
            // Converte nome do livro para formato URL-friendly.
            $base = Str::slug((string) $review->livro_nome);
            // Fallback para casos em que o nome nao gere slug valido.
            $base = $base !== '' ? $base : 'review';

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

            // Persiste slug na review atual.
            DB::table('reviews')
                ->where('id', $review->id)
                ->update(['slug' => $slug]);
        }

        // Aplica restricao de unicidade apos preencher todos os slugs.
        Schema::table('reviews', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        // Remove indice unico e coluna slug para restaurar o schema anterior.
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique('reviews_slug_unique');
            $table->dropColumn('slug');
        });
    }
};
