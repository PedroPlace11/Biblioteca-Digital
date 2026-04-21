<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('room_users', function (Blueprint $table) {
            $table->string('role', 20)->default('member')->after('joined_at');
        });

        // Garante valor padrão consistente e promove criador da sala para admin da sala.
        DB::table('room_users')->whereNull('role')->update(['role' => 'member']);

        DB::table('room_users')
            ->join('rooms', 'rooms.id', '=', 'room_users.room_id')
            ->whereColumn('room_users.user_id', 'rooms.creator_id')
            ->update(['room_users.role' => 'admin']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
