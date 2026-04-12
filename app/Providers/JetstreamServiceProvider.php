<?php



namespace App\Providers;

use App\Actions\Jetstream\DeleteUser;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Laravel\Jetstream\Jetstream;

class JetstreamServiceProvider extends ServiceProvider
{
    /**
     * Regista os servicos da aplicacao.
     */
    public function register(): void
    {
        // Provider sem bindings adicionais no container.
    }

    /**
     * Inicializa os servicos da aplicacao.
     */
    public function boot(): void
    {
        // Configura permissoes disponiveis para tokens API do Jetstream.
        $this->configurePermissions();

        // Define acao executada quando um utilizador elimina a propria conta.
        Jetstream::deleteUsersUsing(DeleteUser::class);

        // Ativa prefetch de assets do Vite para melhorar percepcao de desempenho no frontend.
        Vite::prefetch(concurrency: 3);
    }

    /**
        * Configura as permissoes disponiveis na aplicacao.
     */
    protected function configurePermissions(): void
    {
        // Permissao padrao aplicada a novos API tokens.
        Jetstream::defaultApiTokenPermissions(['read']);

        // Conjunto completo de permissoes que podem ser atribuidas aos tokens.
        Jetstream::permissions([
            'create',
            'read',
            'update',
            'delete',
        ]);
    }
}



