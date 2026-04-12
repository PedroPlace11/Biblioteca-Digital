<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Middleware que restringe o acesso a rotas exclusivas de administradores.
class AdminMiddleware
{
    /** Verifica se o utilizador autenticado tem o papel de admin; caso contrario, aborta com 403. */
    public function handle($request, Closure $next)
    {
        // Valida se usuario esta autenticado e tem role de admin.
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            // Bloqueia acesso com erro 403 (Forbidden) se nao for admin.
            abort(403);
        }

        // Passa requisicao para proximo middleware ou controlador.
        return $next($request);
    }
}



