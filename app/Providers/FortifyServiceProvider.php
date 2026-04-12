<?php



namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
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
        // Define as classes responsaveis pelas acoes de autenticacao/conta no Fortify.
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        // Controla o redirecionamento quando autenticacao de dois fatores e necessaria.
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        // Limite de tentativas de login por identificador unico (username + IP).
        RateLimiter::for('login', function (Request $request) {
            // Normaliza a chave para evitar variacoes de maiusculas/acentos.
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            // Permite ate 5 tentativas por minuto para esta chave.
            return Limit::perMinute(5)->by($throttleKey);
        });

        // Limite para validacao do segundo fator por sessao de login.
        RateLimiter::for('two-factor', function (Request $request) {
            // Permite ate 5 tentativas por minuto para o ID de login da sessao atual.
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}



