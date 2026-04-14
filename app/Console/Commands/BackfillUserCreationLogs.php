<?php

namespace App\Console\Commands;

use App\Models\LogSistema;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class BackfillUserCreationLogs extends Command
{
    protected $signature = 'logs:backfill-user-creations {--user-id= : Backfill apenas para um utilizador especifico}';

    protected $description = 'Cria entradas de log para contas antigas de utilizadores que foram criadas antes da auditoria existir';

    public function handle(): int
    {
        if (! Schema::hasTable('logs')) {
            $this->error('A tabela de logs ainda nao existe. Execute a migracao primeiro.');

            return self::FAILURE;
        }

        $query = User::query()->orderBy('id');

        if ($this->option('user-id')) {
            $query->where('id', (int) $this->option('user-id'));
        }

        $users = $query->get();
        $created = 0;
        $skipped = 0;
        $ipFallback = 'Nao disponivel';
        $browserFallback = 'Nao disponivel';

        foreach ($users as $user) {
            $alteracao = 'Criacao de conta de ' . ($user->role ?? 'cidadao');

            $alreadyExists = LogSistema::query()
                ->where('user_id', $user->id)
                ->where('alteracao', $alteracao)
                ->exists();

            if ($alreadyExists) {
                LogSistema::query()
                    ->where('user_id', $user->id)
                    ->where('alteracao', $alteracao)
                    ->whereNull('ip')
                    ->update([
                        'ip' => $ipFallback,
                        'browser' => $browserFallback,
                        'metodo' => 'SYSTEM',
                        'url' => 'backfill:user-creations',
                        'route_name' => 'logs.backfill-user-creations',
                    ]);

                $skipped++;
                continue;
            }

            LogSistema::query()->create([
                'ocorrido_em' => $user->created_at ?? now(),
                'user_id' => $user->id,
                'user_nome' => $user->name,
                'modulo' => $user->role === 'admin' ? 'Admins' : 'Register',
                'objeto_id' => (string) $user->id,
                'alteracao' => $alteracao,
                'ip' => $ipFallback,
                'browser' => $browserFallback,
                'metodo' => 'SYSTEM',
                'url' => 'backfill:user-creations',
                'route_name' => 'logs.backfill-user-creations',
            ]);

            $created++;
        }

        $this->info("Backfill concluido. Novos logs: {$created}. Ja existentes: {$skipped}.");

        return self::SUCCESS;
    }
}
