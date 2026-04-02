<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('autors', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('nome');
        });

        $autores = DB::table('autors')
            ->select('id', 'nome')
            ->orderBy('id')
            ->get();

        $usedSlugs = [];

        foreach ($autores as $autor) {
            $base = Str::slug((string) $autor->nome);
            $base = $base !== '' ? $base : 'autor';

            $slug = $base;
            $suffix = 2;

            while (in_array($slug, $usedSlugs, true)) {
                $slug = $base . '-' . $suffix;
                $suffix++;
            }

            $usedSlugs[] = $slug;

            DB::table('autors')
                ->where('id', $autor->id)
                ->update(['slug' => $slug]);
        }

        Schema::table('autors', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('autors', function (Blueprint $table) {
            $table->dropUnique('autors_slug_unique');
            $table->dropColumn('slug');
        });
    }
};
