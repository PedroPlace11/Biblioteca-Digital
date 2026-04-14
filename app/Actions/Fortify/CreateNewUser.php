<?php



namespace App\Actions\Fortify;

use App\Models\LogSistema;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'role' => 'cidadao',
        ]);

        if (Schema::hasTable('logs')) {
            $route = request()->route();
            $routeName = $route?->getName();

            LogSistema::query()->create([
                'ocorrido_em' => now(),
                'user_id' => $user->id,
                'user_nome' => $user->name,
                'modulo' => $routeName ? ucfirst(explode('.', $routeName)[0]) : 'Register',
                'objeto_id' => (string) $user->id,
                'alteracao' => 'Criacao de conta de cidadao',
                'ip' => request()->ip(),
                'browser' => (string) request()->userAgent(),
                'metodo' => request()->method(),
                'url' => request()->fullUrl(),
                'route_name' => $routeName,
            ]);
        }

        return $user;
    }
}



