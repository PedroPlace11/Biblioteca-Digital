<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('livro_id');
        });

        $reviews = DB::table('reviews')
            ->join('livros', 'reviews.livro_id', '=', 'livros.id')
            ->select('reviews.id', 'livros.nome as livro_nome')
            ->orderBy('reviews.id')
            ->get();

        $usedSlugs = [];

        foreach ($reviews as $review) {
            $base = Str::slug((string) $review->livro_nome);
            $base = $base !== '' ? $base : 'review';

            $slug = $base;
            $suffix = 2;

            while (in_array($slug, $usedSlugs, true)) {
                $slug = $base . '-' . $suffix;
                $suffix++;
            }

            $usedSlugs[] = $slug;

            DB::table('reviews')
                ->where('id', $review->id)
                ->update(['slug' => $slug]);
        }

        Schema::table('reviews', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique('reviews_slug_unique');
            $table->dropColumn('slug');
        });
    }
};
