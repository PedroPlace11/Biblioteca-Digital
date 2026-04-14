<?php

it('mostra comando pest em modo dry run', function () {
    $this->artisan('projeto:testar --dry-run --suite=Feature --filtro=Carrinho')
    ->expectsOutputToContain('Comando Pest: php artisan test --testsuite=Feature --filter=Carrinho')
        ->assertSuccessful();
});

it('falha quando suite informada e invalida', function () {
    $this->artisan('projeto:testar --dry-run --suite=Integracao')
        ->expectsOutputToContain('Suite inválida. Use apenas Feature ou Unit.')
        ->assertFailed();
});
