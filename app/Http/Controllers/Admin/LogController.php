<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogSistema;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(Request $request): View
    {
        $pesquisa = trim((string) $request->query('q', ''));
        $modulo = trim((string) $request->query('modulo', ''));

        if (! Schema::hasTable('logs')) {
            $logs = new LengthAwarePaginator([], 0, 20, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);

            return view('admin.logs.index', [
                'logs' => $logs,
                'pesquisa' => $pesquisa,
                'modulo' => $modulo,
                'modulos' => collect(),
            ]);
        }

        $query = LogSistema::query()->orderByDesc('ocorrido_em')->orderByDesc('id');

        if ($modulo !== '') {
            $query->where('modulo', $modulo);
        }

        if ($pesquisa !== '') {
            $query->where(function ($q) use ($pesquisa) {
                $q->where('user_nome', 'like', "%{$pesquisa}%")
                    ->orWhere('alteracao', 'like', "%{$pesquisa}%")
                    ->orWhere('objeto_id', 'like', "%{$pesquisa}%")
                    ->orWhere('ip', 'like', "%{$pesquisa}%")
                    ->orWhere('browser', 'like', "%{$pesquisa}%");
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        $modulos = LogSistema::query()
            ->select('modulo')
            ->whereNotNull('modulo')
            ->where('modulo', '!=', '')
            ->distinct()
            ->orderBy('modulo')
            ->pluck('modulo');

        return view('admin.logs.index', compact('logs', 'pesquisa', 'modulo', 'modulos'));
    }
}
