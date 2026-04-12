<?php

use App\Models\Carrinho;
use App\Models\CarrinhoItem;
use App\Models\Editora;
use App\Models\Livro;
use App\Models\User;
use App\Notifications\CarrinhoAbandonadoNotification;
use Illuminate\Support\Facades\Notification;

it('notifica cidadao por sininho e email quando carrinho fica abandonado por mais de 1 hora', function () {
    Notification::fake();

    $user = User::factory()->create([
        'role' => 'cidadao',
    ]);

    $editora = Editora::query()->create([
        'nome' => 'Editora Teste',
    ]);

    $livro = Livro::query()->create([
        'isbn' => '9780000000001',
        'nome' => 'Livro Teste',
        'editora_id' => $editora->id,
        'preco' => 12.50,
    ]);

    $carrinho = Carrinho::query()->create([
        'user_id' => $user->id,
    ]);

    CarrinhoItem::query()->create([
        'carrinho_id' => $carrinho->id,
        'livro_id' => $livro->id,
        'quantidade' => 2,
        'preco_unitario' => 12.50,
    ]);

    $carrinho->timestamps = false;
    $carrinho->updated_at = now()->subHour()->subMinute();
    $carrinho->save();

    $this->artisan('carrinho:notificar-abandonados')
        ->expectsOutputToContain('Notificações de carrinho abandonado processadas: 1')
        ->assertSuccessful();

    Notification::assertSentTo(
        $user,
        CarrinhoAbandonadoNotification::class,
        function (CarrinhoAbandonadoNotification $notification, array $channels) {
            return $channels === ['database'];
        }
    );

    Notification::assertSentTo(
        $user,
        CarrinhoAbandonadoNotification::class,
        function (CarrinhoAbandonadoNotification $notification, array $channels) {
            return $channels === ['mail'];
        }
    );

    expect($carrinho->fresh()->lembrete_abandono_enviado_em)->not()->toBeNull();
});

it('nao notifica quando carrinho ainda nao chegou a 1 hora', function () {
    Notification::fake();

    $user = User::factory()->create([
        'role' => 'cidadao',
    ]);

    $editora = Editora::query()->create([
        'nome' => 'Editora Rapida',
    ]);

    $livro = Livro::query()->create([
        'isbn' => '9780000000002',
        'nome' => 'Livro Recente',
        'editora_id' => $editora->id,
        'preco' => 9.90,
    ]);

    $carrinho = Carrinho::query()->create([
        'user_id' => $user->id,
    ]);

    CarrinhoItem::query()->create([
        'carrinho_id' => $carrinho->id,
        'livro_id' => $livro->id,
        'quantidade' => 1,
        'preco_unitario' => 9.90,
    ]);

    $carrinho->timestamps = false;
    $carrinho->updated_at = now()->subMinutes(59);
    $carrinho->save();

    $this->artisan('carrinho:notificar-abandonados')->assertSuccessful();

    Notification::assertNothingSent();
    expect($carrinho->fresh()->lembrete_abandono_enviado_em)->toBeNull();
});
