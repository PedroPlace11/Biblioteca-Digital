<?php



use App\Models\User;
use App\Models\LogSistema;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    expect(LogSistema::query()->where('user_id', $user->id)->where('alteracao', 'Inicio de sessão')->exists())->toBeTrue();
});

test('users cannot authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout using post request', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/logout');

    $this->assertGuest();
});

test('new citizen registrations are written to logs', function () {
    $response = $this->post('/register', [
        'name' => 'Cidadao Novo',
        'email' => 'cidadao.novo@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertStatus(302);

    $user = User::query()->where('email', 'cidadao.novo@example.com')->first();

    expect($user)->not->toBeNull();
    expect(LogSistema::query()->where('user_id', $user->id)->exists())->toBeTrue();
    expect(LogSistema::query()->where('user_id', $user->id)->where('alteracao', 'Criacao de conta de cidadao')->exists())->toBeTrue();
});

