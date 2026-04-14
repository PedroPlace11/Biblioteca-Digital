<?php



use App\Models\Editora;
use App\Models\Livro;
use App\Models\Requisicao;
use App\Models\User;
use Illuminate\Support\Carbon;

it('permite requisitar um livro quando ele esta disponivel', function () {
    $user = User::factory()->create();
    $editora = Editora::create([
        'nome' => 'Penguin',
    ]);
    $livro = Livro::create([
        'isbn' => 'isbn-disponivel-001',
        'nome' => 'Livro Disponivel',
        'editora_id' => $editora->id,
        'bibliografia' => 'Teste de disponibilidade',
        'preco' => 19.90,
    ]);

    $response = $this->actingAs($user)->post(route('livros.requisitar', $livro));

    $response->assertRedirect(route('livros.show', $livro));
    $response->assertSessionHas('popup_success', 'Livro requisitado com sucesso.');

    $this->assertDatabaseHas('requisicoes', [
        'user_id' => $user->id,
        'livro_id' => $livro->id,
    ]);
});

it('impede requisitar um livro quando ele ja esta requisitado por outro utilizador', function () {
    $userAtual = User::factory()->create();
    $outroUser = User::factory()->create();
    $editora = Editora::create([
        'nome' => 'Rocco',
    ]);
    $livro = Livro::create([
        'isbn' => 'isbn-indisponivel-001',
        'nome' => 'Livro Indisponivel',
        'editora_id' => $editora->id,
        'bibliografia' => 'Teste de indisponibilidade',
        'preco' => 29.90,
    ]);

    Requisicao::create([
        'user_id' => $outroUser->id,
        'livro_id' => $livro->id,
    ]);

    $response = $this->actingAs($userAtual)->post(route('livros.requisitar', $livro));

    $response->assertRedirect(route('livros.show', $livro));
    $response->assertSessionHas('info', 'Livro indisponível no momento.');

    $this->assertDatabaseMissing('requisicoes', [
        'user_id' => $userAtual->id,
        'livro_id' => $livro->id,
    ]);

    expect(Requisicao::count())->toBe(1);
});

it('nao cria uma segunda requisicao quando o mesmo utilizador tenta requisitar o mesmo livro novamente', function () {
    $user = User::factory()->create();
    $editora = Editora::create([
        'nome' => 'Companhia das Letras',
    ]);
    $livro = Livro::create([
        'isbn' => 'isbn-repetido-001',
        'nome' => 'Livro Repetido',
        'editora_id' => $editora->id,
        'bibliografia' => 'Teste de duplicidade',
        'preco' => 39.90,
    ]);

    Requisicao::create([
        'user_id' => $user->id,
        'livro_id' => $livro->id,
    ]);

    $response = $this->actingAs($user)->post(route('livros.requisitar', $livro));

    $response->assertRedirect(route('livros.index'));
    $response->assertSessionHas('info', 'Já requisitou este livro.');

    expect(Requisicao::count())->toBe(1);
});

it('cria requisicao de livro com dados corretos do utilizador e do livro', function () {
    Carbon::setTestNow('2026-04-14 10:00:00');

    $user = User::factory()->create([
        'name' => 'Utilizador Requisicao',
        'email' => 'requisicao.teste@example.com',
        'role' => 'cidadao',
    ]);

    $editora = Editora::create([
        'nome' => 'Editora Requisicao',
    ]);

    $livro = Livro::create([
        'isbn' => 'isbn-criacao-requisicao-001',
        'nome' => 'Livro para Requisicao',
        'editora_id' => $editora->id,
        'bibliografia' => 'Teste de criacao de requisicao',
        'preco' => 15.50,
    ]);

    $response = $this->actingAs($user)->post(route('livros.requisitar', $livro));

    $response->assertRedirect(route('livros.show', $livro));
    $response->assertSessionHas('popup_success', 'Livro requisitado com sucesso.');

    $requisicao = Requisicao::query()
        ->where('user_id', $user->id)
        ->where('livro_id', $livro->id)
        ->whereNull('deleted_at')
        ->first();

    expect($requisicao)->not->toBeNull()
        ->and($requisicao->user_id)->toBe($user->id)
        ->and($requisicao->livro_id)->toBe($livro->id)
        ->and($requisicao->cidadao_nome)->toBe('Utilizador Requisicao')
        ->and($requisicao->cidadao_email)->toBe('requisicao.teste@example.com')
        ->and($requisicao->cidadao_numero_leitor)->toBe($user->numero_leitor)
        ->and($requisicao->data_fim_prevista?->format('Y-m-d H:i:s'))->toBe('2026-04-19 10:00:00');

    Carbon::setTestNow();
});

it('nao permite criar requisicao sem livro valido', function () {
    $user = User::factory()->create([
        'role' => 'cidadao',
    ]);

    $response = $this->actingAs($user)->post(route('livros.requisitar', 999999));

    // O route model binding do Laravel bloqueia IDs de livro inexistentes com 404.
    $response->assertNotFound();

    $this->assertDatabaseCount('requisicoes', 0);
});

it('permite solicitar devolucao de livro e atualiza a requisicao ativa', function () {
    Carbon::setTestNow('2026-04-14 12:30:00');

    $user = User::factory()->create([
        'role' => 'cidadao',
    ]);

    $editora = Editora::create([
        'nome' => 'Editora Devolucao',
    ]);

    $livro = Livro::create([
        'isbn' => 'isbn-devolucao-001',
        'nome' => 'Livro Devolucao',
        'editora_id' => $editora->id,
        'bibliografia' => 'Teste de devolucao',
        'preco' => 22.00,
    ]);

    $requisicao = Requisicao::create([
        'user_id' => $user->id,
        'livro_id' => $livro->id,
        'cidadao_nome' => $user->name,
        'cidadao_email' => $user->email,
        'cidadao_numero_leitor' => $user->numero_leitor,
        'data_fim_prevista' => Carbon::now()->addDays(5),
    ]);

    $csrfToken = 'csrf-token-teste-devolucao';

    $response = $this
        ->actingAs($user)
        ->withSession(['_token' => $csrfToken])
        ->withHeader('X-CSRF-TOKEN', $csrfToken)
        ->from(route('livros.show', $livro))
        ->delete(route('livros.cancelar-requisicao', $livro));

    $response->assertRedirect(route('livros.show', $livro));
    $response->assertSessionHas('popup_success', 'Pedido de devolução enviado. Aguarde a confirmação do admin.');

    $requisicao->refresh();

    expect($requisicao->devolucao_solicitada_em)->not->toBeNull()
        ->and($requisicao->devolucao_solicitada_em?->format('Y-m-d H:i:s'))->toBe('2026-04-14 12:30:00')
        ->and($requisicao->deleted_at)->toBeNull();

    Carbon::setTestNow();
});

