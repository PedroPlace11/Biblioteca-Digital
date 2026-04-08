<x-app-layout>
    <div class="p-6 max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-slate-900">Gerir Encomendas</h1>
            <p class="text-slate-500 mt-1">Listagem de encomendas pagas e pendentes de pagamento.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 mb-5">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                <div>
                    <label class="label"><span class="label-text">Pesquisar</span></label>
                    <input type="text" name="q" value="{{ $pesquisa }}" class="input input-bordered w-full" placeholder="Nome, email, N.º leitor ou N.º encomenda">
                </div>

                <div>
                    <label class="label"><span class="label-text">Estado</span></label>
                    <select name="estado" class="select select-bordered w-full">
                        <option value="todas" @selected($estado === 'todas')>Todas</option>
                        <option value="pendente_pagamento" @selected($estado === 'pendente_pagamento')>Pendente pagamento</option>
                        <option value="paga" @selected($estado === 'paga')>Paga</option>
                        <option value="enviado" @selected($estado === 'enviado')>Enviado</option>
                        <option value="pagamento_recusado" @selected($estado === 'pagamento_recusado')>Pagamento recusado</option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button class="btn bg-black text-white border-black hover:bg-neutral-800" type="submit">Filtrar</button>
                    <a href="{{ route('admin.encomendas.index') }}" class="btn btn-outline">Limpar</a>
                </div>
            </form>
        </div>

        <div class="space-y-4">
            @forelse ($encomendas as $encomenda)
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3">
                        <div>
                            <p class="text-sm text-slate-500">Encomenda #{{ $encomenda->id }}</p>
                            <p class="text-sm text-slate-500">Criada em {{ $encomenda->created_at?->format('d/m/Y H:i') }}</p>
                            <p class="text-sm text-slate-500">N.º leitor: {{ $encomenda->user?->numero_leitor ?? '-' }}</p>
                            <p class="text-sm text-slate-500">Cidadão: {{ $encomenda->user?->name ?? 'Utilizador removido' }}</p>
                            <p class="text-sm text-slate-500">Email: {{ $encomenda->user?->email ?? '-' }}</p>
                        </div>

                        <div class="text-left lg:text-right">
                            @if ($encomenda->estado === 'paga')
                                <span class="badge border-emerald-500 text-emerald-700 bg-emerald-50">Paga</span>
                                <p class="text-xs text-slate-500 mt-1">Pago em {{ $encomenda->pago_em?->format('d/m/Y H:i') ?? '-' }}</p>
                            @elseif ($encomenda->estado === 'enviado')
                                <span class="badge border-sky-500 text-sky-700 bg-sky-50">Enviado</span>
                                <p class="text-xs text-slate-500 mt-1">Rastreio: {{ $encomenda->numero_rastreio ?? '-' }}</p>
                            @elseif ($encomenda->estado === 'pagamento_recusado')
                                <span class="badge border-rose-500 text-rose-700 bg-rose-50">Recusada</span>
                            @else
                                <span class="badge border-amber-500 text-amber-700 bg-amber-50">Pendente pagamento</span>
                            @endif

                            <p class="text-xl font-bold text-slate-900 mt-2">{{ number_format((float) $encomenda->total, 2, ',', '.') }} &euro;</p>
                        </div>
                    </div>

                    <div class="mt-3 flex justify-end">
                        <a href="{{ route('admin.encomendas.show', $encomenda) }}" class="px-2 py-1 rounded bg-black text-white font-bold border border-black text-xs">
                            Ver
                        </a>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Livro</th>
                                    <th>Qtd</th>
                                    <th>Preço unitário</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($encomenda->itens as $item)
                                    <tr>
                                        <td>{{ $item->livro_nome }}</td>
                                        <td>{{ $item->quantidade }}</td>
                                        <td>{{ number_format((float) $item->preco_unitario, 2, ',', '.') }} &euro;</td>
                                        <td>{{ number_format((float) $item->subtotal, 2, ',', '.') }} &euro;</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-500">
                    Sem encomendas para os filtros selecionados.
                </div>
            @endforelse
        </div>

        <div class="pagination-custom mt-6">
            <div class="join grid grid-cols-2 w-56 mx-auto">
                @if ($encomendas->onFirstPage())
                    <button class="join-item btn bg-black text-white font-semibold w-full py-1 px-2 text-sm" disabled>Página anterior</button>
                @else
                    <a href="{{ $encomendas->previousPageUrl() }}" class="join-item btn bg-black text-white font-semibold w-full py-1 px-2 text-sm">Página anterior</a>
                @endif

                @if ($encomendas->hasMorePages())
                    <a href="{{ $encomendas->nextPageUrl() }}" class="join-item btn btn-outline font-semibold w-full py-1 px-2 text-sm">Próxima página</a>
                @else
                    <button class="join-item btn btn-outline font-semibold w-full py-1 px-2 text-sm" disabled>Próxima página</button>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
