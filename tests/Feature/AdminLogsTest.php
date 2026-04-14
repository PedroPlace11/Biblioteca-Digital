<?php

use App\Models\LogSistema;
use App\Models\User;

it('regista acao de utilizador autenticado na tabela de logs', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.encomendas.index'))
        ->assertOk();

    expect(LogSistema::query()->count())->toBeGreaterThan(0);

    $log = LogSistema::query()->latest('id')->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($admin->id)
        ->and($log->modulo)->toBe('Encomendas')
        ->and($log->metodo)->toBe('GET')
        ->and($log->ip)->not->toBeNull();
});

it('permite admin aceder ao menu logs', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.logs.index'))
        ->assertOk()
        ->assertSee('Logs')
        ->assertSee('Data')
        ->assertSee('Browser');
});

it('impede cidadao de aceder ao menu logs', function () {
    $cidadao = User::factory()->create([
        'role' => 'cidadao',
    ]);

    $this->actingAs($cidadao)
        ->get(route('admin.logs.index'))
        ->assertForbidden();
});
