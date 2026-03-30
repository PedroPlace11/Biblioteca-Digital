<?php
namespace App\Models;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * Os atributos que podem ser atribuidos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'numero_leitor',
        'numero_leitor_seq',
    ];

    /**
     * Os atributos que devem ficar ocultos na serializacao.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * Acessores para anexar ao formato de array do modelo.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Obtem os atributos que devem ser convertidos (cast).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            // Garante perfil padrão para novos registos quando role não vier definido.
            if (empty($user->role)) {
                $user->role = 'cidadao';
            }

            if (empty($user->numero_leitor)) {
                $sequencial = $user->numero_leitor_seq ?: static::proximoNumeroLeitorSequencial();
                $user->numero_leitor_seq = $sequencial;
                $user->numero_leitor = sprintf('L%06d', $sequencial);
            }

            if ($user->role === 'admin' && !app()->runningInConsole()) {
                if (!Auth::check() || Auth::user()->role !== 'admin') {
                    throw new AuthorizationException('Apenas administradores podem criar utilizadores admin.');
                }
            }
        });

        static::updating(function (User $user) {
            if ($user->isDirty('numero_leitor') && !is_null($user->getOriginal('numero_leitor'))) {
                throw new AuthorizationException('O número de leitor não pode ser alterado.');
            }

            // Corrige automaticamente utilizadores antigos sem número de leitor.
            if (empty($user->numero_leitor)) {
                $sequencial = $user->numero_leitor_seq ?: static::proximoNumeroLeitorSequencial();
                $user->numero_leitor_seq = $sequencial;
                $user->numero_leitor = sprintf('L%06d', $sequencial);
            }
        });
    }

    protected static function proximoNumeroLeitorSequencial(): int
    {
        return ((int) static::max('numero_leitor_seq')) + 1;
    }

    public function getNumeroLeitorAttribute($value): ?string
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString((string) $value);
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    public function setNumeroLeitorAttribute($value): void
    {
        if (is_null($value) || $value === '') {
            $this->attributes['numero_leitor'] = null;
            return;
        }

        try {
            Crypt::decryptString((string) $value);
            $this->attributes['numero_leitor'] = (string) $value;
        } catch (\Throwable $e) {
            $this->attributes['numero_leitor'] = Crypt::encryptString((string) $value);
        }
    }

    // Relacao 1:N com requisicoes realizadas pelo utilizador.
    public function requisicoes()
    {
        return $this->hasMany(Requisicao::class);
    }

    // Relação 1:N com reviews feitos pelo utilizador.
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}



