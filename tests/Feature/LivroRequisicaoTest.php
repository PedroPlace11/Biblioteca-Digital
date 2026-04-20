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

    $csrfToken = 'csrf-token-disponivel';

    $response = $this
        ->actingAs($user)
        ->withSession(['_token' => $csrfToken])
        ->withHeader('X-CSRF-TOKEN', $csrfToken)
        ->from(route('livros.show', $livro))
        ->post(route('livros.requisitar', $livro));

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

    $csrfToken = 'csrf-token-indisponivel';

    $response = $this
        ->actingAs($userAtual)
        ->withSession(['_token' => $csrfToken])
        ->withHeader('X-CSRF-TOKEN', $csrfToken)
        ->from(route('livros.show', $livro))
        ->post(route('livros.requisitar', $livro));

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

    $csrfToken = 'csrf-token-duplicado';

    $response = $this
        ->actingAs($user)
        ->withSession(['_token' => $csrfToken])
        ->withHeader('X-CSRF-TOKEN', $csrfToken)
        ->from(route('livros.show', $livro))
        ->post(route('livros.requisitar', $livro));

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

    $csrfToken = 'csrf-token-criacao';

    $response = $this
        ->actingAs($user)
        ->withSession(['_token' => $csrfToken])
        ->withHeader('X-CSRF-TOKEN', $csrfToken)
        ->from(route('livros.show', $livro))
        ->post(route('livros.requisitar', $livro));

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

    $csrfToken = 'csrf-token-invalido';

    $response = $this
        ->actingAs($user)
        ->withSession(['_token' => $csrfToken])
        ->withHeader('X-CSRF-TOKEN', $csrfToken)
        ->from(route('livros.index'))
        ->post(route('livros.requisitar', 999999));

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

it('lista apenas as requisicoes do utilizador autenticado', function () {
    $utilizadorAlvo = User::factory()->create([
        'role' => 'cidadao',
    ]);
    $outroUtilizador = User::factory()->create([
        'role' => 'cidadao',
    ]);

    $editora = Editora::create([
        'nome' => 'Editora Listagem',
    ]);

    $livroDoAlvo1 = Livro::create([
        'isbn' => 'isbn-listagem-alvo-001',
        'nome' => 'Livro Alvo 1',
        'editora_id' => $editora->id,
        'bibliografia' => 'Livro do utilizador alvo',
        'preco' => 12.50,
    ]);

    $livroDoAlvo2 = Livro::create([
        'isbn' => 'isbn-listagem-alvo-002',
        'nome' => 'Livro Alvo 2',
        'editora_id' => $editora->id,
        'bibliografia' => 'Segundo livro do utilizador alvo',
        'preco' => 14.00,
    ]);

    $livroDeOutro = Livro::create([
        'isbn' => 'isbn-listagem-outro-001',
        'nome' => 'Livro Outro Utilizador',
        'editora_id' => $editora->id,
        'bibliografia' => 'Livro de outro utilizador',
        'preco' => 18.00,
    ]);

    $requisicaoDoAlvo1 = Requisicao::create([
        'user_id' => $utilizadorAlvo->id,
        'livro_id' => $livroDoAlvo1->id,
        'cidadao_nome' => $utilizadorAlvo->name,
        'cidadao_email' => $utilizadorAlvo->email,
        'cidadao_numero_leitor' => $utilizadorAlvo->numero_leitor,
    ]);

    $requisicaoDoAlvo2 = Requisicao::create([
        'user_id' => $utilizadorAlvo->id,
        'livro_id' => $livroDoAlvo2->id,
        'cidadao_nome' => $utilizadorAlvo->name,
        'cidadao_email' => $utilizadorAlvo->email,
        'cidadao_numero_leitor' => $utilizadorAlvo->numero_leitor,
    ]);

    $requisicaoDeOutro = Requisicao::create([
        'user_id' => $outroUtilizador->id,
        'livro_id' => $livroDeOutro->id,
        'cidadao_nome' => $outroUtilizador->name,
        'cidadao_email' => $outroUtilizador->email,
        'cidadao_numero_leitor' => $outroUtilizador->numero_leitor,
    ]);

    $response = $this->actingAs($utilizadorAlvo)->get(route('dashboard'));

    $response->assertOk();
    $response->assertViewHas('minhasRequisicoes', function ($minhasRequisicoes) use ($utilizadorAlvo, $requisicaoDoAlvo1, $requisicaoDoAlvo2, $requisicaoDeOutro) {
        $ids = $minhasRequisicoes->pluck('id');
        $userIds = $minhasRequisicoes->pluck('user_id')->unique();

        return $ids->contains($requisicaoDoAlvo1->id)
            && $ids->contains($requisicaoDoAlvo2->id)
            && !$ids->contains($requisicaoDeOutro->id)
            && $userIds->count() === 1
            && $userIds->first() === $utilizadorAlvo->id;
    });
});

it('impede requisitar um livro quando o stock e zero', function () {
    $user = User::factory()->create([
        'role' => 'cidadao',
    ]);

    $editora = Editora::create([
        'nome' => 'Editora Sem Stock',
    ]);

    $livro = Livro::create([
        'isbn' => 'isbn-stock-zero-001',
        'nome' => 'Livro Sem Stock',
        'editora_id' => $editora->id,
        'bibliografia' => 'Teste de stock zero',
        'preco' => 19.99,
        'stock' => 0,
    ]);

    $csrfToken = 'csrf-token-stock-zero';

    $response = $this
        ->actingAs($user)
        ->withSession(['_token' => $csrfToken])
        ->withHeader('X-CSRF-TOKEN', $csrfToken)
        ->from(route('livros.show', $livro))
        ->post(route('livros.requisitar', $livro));

    $response->assertRedirect(route('livros.show', $livro));
    $response->assertSessionHas('info', 'Livro sem stock disponível no momento.');

    $this->assertDatabaseMissing('requisicoes', [
        'user_id' => $user->id,
        'livro_id' => $livro->id,
    ]);
});

