<?php

use App\Models\LogSistema;
use App\Models\User;

it('cria logs para utilizadores antigos sem duplicar os existentes', function () {
    $user = User::factory()->create([
        'role' => 'cidadao',
    ]);

    LogSistema::query()->where('user_id', $user->id)->delete();

    $this->artisan('logs:backfill-user-creations --user-id=' . $user->id)
        ->expectsOutputToContain('Backfill concluido.')
        ->assertSuccessful();

    expect(LogSistema::query()->where('user_id', $user->id)->where('alteracao', 'Criacao de conta de cidadao')->exists())->toBeTrue();

    $countBefore = LogSistema::query()->where('user_id', $user->id)->where('alteracao', 'Criacao de conta de cidadao')->count();

    $this->artisan('logs:backfill-user-creations --user-id=' . $user->id)
        ->assertSuccessful();

    expect(LogSistema::query()->where('user_id', $user->id)->where('alteracao', 'Criacao de conta de cidadao')->count())->toBe($countBefore);
});
