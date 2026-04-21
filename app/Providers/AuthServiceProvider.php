<?php

namespace App\Providers;

use App\Models\DirectMessage;
use App\Models\Message;
use App\Models\Room;
use App\Policies\DirectMessagePolicy;
use App\Policies\MessagePolicy;
use App\Policies\RoomPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * As políticas de modelo mapeadas pela aplicação.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Room::class => RoomPolicy::class,
        Message::class => MessagePolicy::class,
        DirectMessage::class => DirectMessagePolicy::class,
    ];

    /**
     * Regista os serviços de autenticação/autorização.
     */
    public function register(): void
    {
        //
    }

    /**
     * Inicializa os serviços de autenticação/autorização.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
