<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SyncOnlineStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-online-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza o status online dos utilizadores com base nas sessões ativas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Obtém IDs dos utilizadores com sessões ativas
        $activeSessions = \DB::table('sessions')
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        // Marca utilizadores com sessões ativas como online
        User::whereIn('id', $activeSessions)->update([
            'is_online' => true,
            'last_seen_at' => now(),
        ]);

        // Marca utilizadores sem sessões como offline
        User::whereNotIn('id', $activeSessions)
            ->where('is_online', true)
            ->update([
                'is_online' => false,
                'last_seen_at' => now(),
            ]);

        $this->info('Status online sincronizado com sucesso!');
    }
}
