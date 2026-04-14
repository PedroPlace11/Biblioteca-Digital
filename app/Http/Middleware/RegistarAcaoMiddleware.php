<?php

namespace App\Http\Middleware;

use App\Models\LogSistema;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class RegistarAcaoMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        if (! $user) {
            return $response;
        }

        static $logsTableExists;

        if ($logsTableExists === null) {
            $logsTableExists = Schema::hasTable('logs');
        }

        if (! $logsTableExists) {
            return $response;
        }

        $route = $request->route();
        $routeName = $route?->getName();

        if (in_array($routeName, ['register', 'admins.store'], true)) {
            return $response;
        }

        LogSistema::query()->create([
            'ocorrido_em' => now(),
            'user_id' => $user->id,
            'user_nome' => $user->name,
            'modulo' => $this->resolverModulo($routeName, $request->path()),
            'objeto_id' => $this->resolverObjetoId($route),
            'alteracao' => $this->resolverAlteracao($request, $response),
            'ip' => $request->ip(),
            'browser' => (string) $request->userAgent(),
            'metodo' => $request->method(),
            'url' => $request->fullUrl(),
            'route_name' => $routeName,
        ]);

        return $response;
    }

    private function resolverModulo(?string $routeName, string $path): string
    {
        if (! empty($routeName)) {
            $partes = explode('.', $routeName);

            if (($partes[0] ?? '') === 'admin' && ! empty($partes[1])) {
                return ucfirst((string) $partes[1]);
            }

            return ucfirst((string) $partes[0]);
        }

        $primeiroSegmento = explode('/', trim($path, '/'))[0] ?? 'sistema';

        return ucfirst((string) $primeiroSegmento);
    }

    private function resolverObjetoId(mixed $route): ?string
    {
        if (! $route) {
            return null;
        }

        foreach ($route->parameters() as $valor) {
            if (is_scalar($valor)) {
                return (string) $valor;
            }

            if (is_object($valor) && method_exists($valor, 'getKey')) {
                return (string) $valor->getKey();
            }
        }

        return null;
    }

    private function resolverAlteracao(Request $request, Response $response): string
    {
        $acao = match ($request->method()) {
            'POST' => 'Criacao',
            'PUT', 'PATCH' => 'Atualizacao',
            'DELETE' => 'Remocao',
            default => 'Consulta',
        };

        return sprintf(
            '%s | %s %s | HTTP %d',
            $acao,
            $request->method(),
            $request->path(),
            $response->getStatusCode()
        );
    }
}
