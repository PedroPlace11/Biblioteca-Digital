<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('editoras', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('nome');
        });

        $editoras = DB::table('editoras')
            ->select('id', 'nome')
            ->orderBy('id')
            ->get();

        $usedSlugs = [];

        foreach ($editoras as $editora) {
            $base = Str::slug((string) $editora->nome);
            $base = $base !== '' ? $base : 'editora';

            $slug = $base;
            $suffix = 2;

            while (in_array($slug, $usedSlugs, true)) {
                $slug = $base . '-' . $suffix;
                $suffix++;
            }

            $usedSlugs[] = $slug;

            DB::table('editoras')
                ->where('id', $editora->id)
                ->update(['slug' => $slug]);
        }

        Schema::table('editoras', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('editoras', function (Blueprint $table) {
            $table->dropUnique('editoras_slug_unique');
            $table->dropColumn('slug');
        });
    }
};
