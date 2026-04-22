<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SyncOnlineStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Se o utilizador está autenticado, sincroniza o status para online
        if (Auth::check()) {
            Auth::user()->update([
                'is_online' => true,
                'last_seen_at' => now(),
            ]);
        }

        return $next($request);
    }
}
