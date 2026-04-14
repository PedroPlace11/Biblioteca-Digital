<?php



namespace App\Providers;

use App\Models\LogSistema;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Regista os servicos da aplicacao.
     */
    public function register(): void
    {
        //
    }

    /**
     * Inicializa os servicos da aplicacao.
     */
    public function boot(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            if (! Schema::hasTable('logs')) {
                return;
            }

            $autenticado = $event->user;
            $route = request()->route();
            $routeName = $route?->getName();

            LogSistema::query()->create([
                'ocorrido_em' => now(),
                'user_id' => $autenticado->getAuthIdentifier(),
                'user_nome' => (string) data_get($autenticado, 'name'),
                'modulo' => 'Auth',
                'objeto_id' => (string) $autenticado->getAuthIdentifier(),
                'alteracao' => 'Inicio de sessão',
                'ip' => request()->ip(),
                'browser' => (string) request()->userAgent(),
                'metodo' => request()->method(),
                'url' => request()->fullUrl(),
                'route_name' => $routeName,
            ]);
        });
    }
}



