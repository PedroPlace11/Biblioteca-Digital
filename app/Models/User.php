<?php
namespace App\Models;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use App\Models\LogSistema;
use Illuminate\Support\Facades\Schema;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    // Habilita autenticacao com API tokens via Sanctum.
    use HasApiTokens;

    // Habilita factory para testes e seeders.
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    // Adiciona suporte a fotos de perfil via Jetstream.
    use HasProfilePhoto;
    // Habilita sistema de notificacoes (email, database, etc).
    use Notifiable;
    // Adiciona suporte a autenticacao de dois fatores.
    use TwoFactorAuthenticatable;

    /**
     * Os atributos que podem ser atribuidos em massa (mass assignment).
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
     * Os atributos que devem ficar ocultos na serializacao (JSON, array).
     * Protege dados sensiveis quando modelo e retornado em API ou JSON.
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
     * Acessores (atributos calculados) para anexar automaticamente ao formato de array.
     * Estes atributos nao existem na tabela mas sao inclusos em serializacao.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Obtem os atributos que devem ser convertidos (cast) para tipos especificos.
     * Password e automaticamente hashado devido a trait Fortify; dates sao Carbon instances.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Email verificado armazenado como timestamp.
            'email_verified_at' => 'datetime',
            // Password e automaticamente hashada por Fortify.
            'password' => 'hashed',
        ];
    }

    // Hooks de ciclo de vida do modelo - executados antes de criar ou atualizar.
    protected static function booted(): void
    {
        // Hook antes de criar novo utilizador.
        static::creating(function (User $user) {
            // Define papel padrao para novos utilizadores se nao especificado.
            if (empty($user->role)) {
                // Papel padrao: cidadao (utilizador comum da biblioteca).
                $user->role = 'cidadao';
            }

            // Auto-gera numero de leitor se nao foi fornecido.
            if (empty($user->numero_leitor)) {
                // Obtem proximo numero sequencial disponivel.
                $sequencial = $user->numero_leitor_seq ?: static::proximoNumeroLeitorSequencial();
                $user->forceFill([
                    'numero_leitor_seq' => $sequencial,
                    // Formata como 'L' + 6 digitos (ex: L000001, L000002).
                    'numero_leitor' => sprintf('L%06d', $sequencial),
                ]);
            }

            // Autoriza criacao de admin apenas por admin existente.
            if ($user->role === 'admin' && !app()->runningInConsole()) {
                // Garante que apenas admins podem criar outros admins (previne escalacao).
                if (!Auth::check() || Auth::user()->role !== 'admin') {
                    throw new AuthorizationException('Apenas administradores podem criar utilizadores admin.');
                }
            }
        });

        // Hook antes de atualizar utilizador existente.
        static::updating(function (User $user) {
            // Previne alteracao do numero de leitor apos criacao (immutable field).
            if ($user->isDirty('numero_leitor') && !is_null($user->getOriginal('numero_leitor'))) {
                throw new AuthorizationException('O número de leitor não pode ser alterado.');
            }

            // Corrige utilizadores antigos migrados sem numero de leitor.
            if (empty($user->numero_leitor)) {
                // Se numero ainda estiver vazio, gera automaticamente.
                $sequencial = $user->numero_leitor_seq ?: static::proximoNumeroLeitorSequencial();
                $user->forceFill([
                    'numero_leitor_seq' => $sequencial,
                    'numero_leitor' => sprintf('L%06d', $sequencial),
                ]);
            }
        });

    }

    // Gera proximo numero sequencial disponivel para numero de leitor.
    protected static function proximoNumeroLeitorSequencial(): int
    {
        // Obtem maximo numero sequencial atual e adiciona 1.
        return ((int) static::max('numero_leitor_seq')) + 1;
    }

    // Accessor: descriptografa numero de leitor quando lido, com fallback para valor nao criptografado.
    public function getNumeroLeitorAttribute($value): ?string
    {
        // Se numero nao foi informado, retorna null.
        if (is_null($value) || $value === '') {
            return null;
        }

        // Tenta descriptografar valor armazenado (pode estar criptografado ou nao).
        try {
            return Crypt::decryptString((string) $value);
        } catch (\Throwable $e) {
            // Se falhar descriptografia, retorna como esta (legacy data).
            return (string) $value;
        }
    }

    // Mutator: criptografa numero de leitor quando atribuido para armazenamento seguro.
    public function setNumeroLeitorAttribute($value): void
    {
        // Se numero nao foi informado, armazena null.
        if (is_null($value) || $value === '') {
            $this->attributes['numero_leitor'] = null;
            return;
        }

        // Tenta detectar se ja estava criptografado.
        try {
            Crypt::decryptString((string) $value);
            // Se consegue descriptografar, ja estava criptografado - armazena como esta.
            $this->attributes['numero_leitor'] = (string) $value;
        } catch (\Throwable $e) {
            // Se nao consegue descriptografar, criptografa antes de armazenar.
            $this->attributes['numero_leitor'] = Crypt::encryptString((string) $value);
        }
    }

    // Relacao 1:N com requisicoes realizadas pelo utilizador.
    public function requisicoes()
    {
        // Um utilizador pode ter multiplas requisicoes de livros.
        return $this->hasMany(Requisicao::class);
    }

    // Relação 1:N com reviews feitos pelo utilizador.
    public function reviews()
    {
        // Um utilizador pode ter multiplas reviews de livros.
        return $this->hasMany(Review::class);
    }

    // Relação 1:1 com o carrinho de compras do utilizador.
    public function carrinho()
    {
        // Cada utilizador tem exatamente um carrinho de compras.
        return $this->hasOne(Carrinho::class);
    }

    // Relação 1:N com encomendas feitas pelo utilizador.
    public function encomendas()
    {
        // Um utilizador pode ter multiplas encomendas (historico de compras).
        return $this->hasMany(Encomenda::class);
    }

    // Relação 1:N com moradas guardadas pelo cidadão.
    public function moradas(): HasMany
    {
        // Um utilizador pode guardar multiplas moradas (casa, trabalho, etc).
        return $this->hasMany(Morada::class);
    }
}



