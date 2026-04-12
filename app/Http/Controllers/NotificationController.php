<?php
namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

// Controlador para operações de leitura de notificações do utilizador autenticado.
class NotificationController extends Controller
{
    // Marca uma notificação individual como lida e, opcionalmente, redireciona para um destino seguro.
    public function markAsRead(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        // Garante que o utilizador só pode marcar as suas próprias notificações.
        if ((int) $notification->notifiable_id !== (int) Auth::id()) {
            abort(403);
        }

        // Marca a notificacao como lida no banco de dados.
        $notification->markAsRead();

        // Le parametro de redirecionamento da query string.
        $redirectTo = trim((string) $request->input('redirect_to', ''));

        // Se parametro foi fornecido, valida e redireciona com seguranca.
        if ($redirectTo !== '') {
            // Permite redirecionamento absoluto apenas dentro do mesmo domínio.
            if (Str::startsWith($redirectTo, url('/'))) {
                return redirect()->to($redirectTo);
            }

            // Permite URL absoluta interna quando o host coincide com o da app configurada
            // ou com o host do pedido atual (ambientes com domínios diferentes entre APP_URL e browser).
            if (filter_var($redirectTo, FILTER_VALIDATE_URL)) {
                // Extrai host da URL destino.
                $hostDestino = (string) parse_url($redirectTo, PHP_URL_HOST);
                // Obtem host atual do pedido HTTP.
                $hostAtual = (string) $request->getHost();
                // Obtem host configurado na aplicacao.
                $hostApp = (string) parse_url((string) config('app.url'), PHP_URL_HOST);

                // Redireciona apenas se host destino coincide com host atual ou app.
                if ($hostDestino !== '' && in_array($hostDestino, array_filter([$hostAtual, $hostApp]), true)) {
                    return redirect()->to($redirectTo);
                }
            }

            // Permite redirecionamento relativo interno (caminho começa com /).
            if (Str::startsWith($redirectTo, '/')) {
                return redirect()->to($redirectTo);
            }
        }

        // Volta para pagina anterior se nenhuma URL segura foi validada.
        return back();
    }

    // Marca todas as notificações não lidas do utilizador atual.
    public function markAllAsRead(): RedirectResponse
    {
        // Recupera usuario autenticado.
        $user = Auth::user();

        // Se usuario existe, marca todas notificacoes nao lidas como lidas.
        if ($user) {
            $user->unreadNotifications->markAsRead();
        }

        // Volta para pagina anterior.
        return back();
    }
}



