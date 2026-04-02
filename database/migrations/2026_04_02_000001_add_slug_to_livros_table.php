<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('livros', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('nome');
        });

        $livros = DB::table('livros')
            ->select('id', 'nome')
            ->orderBy('id')
            ->get();

        $usedSlugs = [];

        foreach ($livros as $livro) {
            $base = Str::slug((string) $livro->nome);
            $base = $base !== '' ? $base : 'livro';

            $slug = $base;
            $suffix = 2;

            while (in_array($slug, $usedSlugs, true)) {
                $slug = $base . '-' . $suffix;
                $suffix++;
            }

            $usedSlugs[] = $slug;

            DB::table('livros')
                ->where('id', $livro->id)
                ->update(['slug' => $slug]);
        }

        Schema::table('livros', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('livros', function (Blueprint $table) {
            $table->dropUnique('livros_slug_unique');
            $table->dropColumn('slug');
        });
    }
};
