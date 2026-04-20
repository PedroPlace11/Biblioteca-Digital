<x-app-layout>
    <div class="p-6 max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-slate-900">Logs</h1>
            <p class="text-slate-500 mt-1">Rastreio de todas as ações feitas por utilizadores autenticados.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 mb-5">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                <div>
                    <label class="label"><span class="label-text">Pesquisar</span></label>
                    <input type="text" name="q" value="{{ $pesquisa }}" class="input input-bordered w-full" placeholder="Utilizador, alteração, IP, browser...">
                </div>

                <div>
                    <label class="label"><span class="label-text">Módulo</span></label>
                    <select name="modulo" class="select select-bordered w-full">
                        <option value="" {{ $modulo === '' ? 'selected' : '' }}>Todos</option>
                        @foreach ($modulos as $opcaoModulo)
                            <option value="{{ $opcaoModulo }}" {{ $modulo === $opcaoModulo ? 'selected' : '' }}>{{ $opcaoModulo }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <button class="btn bg-black text-white border-black hover:bg-neutral-800" type="submit">Filtrar</button>
                    <a href="{{ route('admin.logs.index') }}" class="btn btn-outline">Limpar</a>
                </div>
            </form>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Hora</th>
                        <th>User</th>
                        <th>Módulo</th>
                        <th>ID do objeto</th>
                        <th>Alteração</th>
                        <th>IP</th>
                        <th>Browser</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php
                            $alteracaoNormalizada = mb_strtolower((string) $log->alteracao);
                            $isModuloTestes = $log->modulo === 'Testes';
                            $statusTeste = null;

                            if ($isModuloTestes) {
                                if (str_contains($alteracaoNormalizada, 'sucesso')) {
                                    $statusTeste = ['texto' => 'Sucesso', 'classe' => 'bg-emerald-100 text-emerald-800'];
                                } elseif (str_contains($alteracaoNormalizada, 'falha')) {
                                    $statusTeste = ['texto' => 'Falha', 'classe' => 'bg-rose-100 text-rose-800'];
                                } elseif (str_contains($alteracaoNormalizada, 'iniciada')) {
                                    $statusTeste = ['texto' => 'Em execução', 'classe' => 'bg-amber-100 text-amber-800'];
                                }
                            }
                        @endphp
                        <tr>
                            <td>{{ $log->ocorrido_em?->format('d/m/Y') ?? '-' }}</td>
                            <td>{{ $log->ocorrido_em?->format('H:i:s') ?? '-' }}</td>
                            <td>{{ $log->user_nome ?? '-' }}</td>
                            <td>{{ $log->modulo }}</td>
                            <td>{{ $log->objeto_id ?? '-' }}</td>
                            <td>
                                <div class="text-sm font-medium text-slate-700">{{ $log->alteracao }}</div>
                                @if ($statusTeste)
                                    <span class="mt-1 inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $statusTeste['classe'] }}">
                                        {{ $statusTeste['texto'] }}
                                    </span>
                                @endif
                                <div class="text-xs text-slate-400 mt-1">{{ $log->metodo }} • {{ $log->route_name ?? '-' }}</div>
                            </td>
                            <td>{{ $log->ip ?: 'Nao disponivel' }}</td>
                            <td class="max-w-xs truncate" title="{{ $log->browser ?: 'Nao disponivel' }}">{{ $log->browser ?: 'Nao disponivel' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-slate-500 py-8">Sem logs para os filtros selecionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-custom mt-6">
            <div class="join grid grid-cols-2 w-56 mx-auto">
                @if ($logs->onFirstPage())
                    <button class="join-item btn bg-black text-white font-semibold w-full py-1 px-2 text-sm" disabled>Página anterior</button>
                @else
                    <a href="{{ $logs->previousPageUrl() }}" class="join-item btn bg-black text-white font-semibold w-full py-1 px-2 text-sm">Página anterior</a>
                @endif

                @if ($logs->hasMorePages())
                    <a href="{{ $logs->nextPageUrl() }}" class="join-item btn btn-outline font-semibold w-full py-1 px-2 text-sm">Próxima página</a>
                @else
                    <button class="join-item btn btn-outline font-semibold w-full py-1 px-2 text-sm" disabled>Próxima página</button>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
