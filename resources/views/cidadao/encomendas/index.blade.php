<x-app-layout>
    {{-- Página de histórico de encomendas com filtros e resumo por pedido. --}}
    <div class="p-6 max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-slate-900">Minhas Encomendas</h1>
            <p class="text-slate-500 mt-1">Acompanhe as suas compras e respetivos estados.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 mb-5">
            {{-- Filtros de ordenação e estado para refinar a listagem. --}}
            <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:items-end">
                <div>
                    <label class="label"><span class="label-text">Ordenação</span></label>
                    <select name="ordem" class="select select-bordered w-full sm:w-56">
                        <option value="mais_recente" @selected(($ordem ?? 'mais_recente') === 'mais_recente')>Mais recente</option>
                        <option value="mais_antigo" @selected(($ordem ?? 'mais_recente') === 'mais_antigo')>Mais antigo</option>
                    </select>
                </div>

                <div>
                    <label class="label"><span class="label-text">Estado</span></label>
                    <select name="estado" class="select select-bordered w-full sm:w-56">
                        <option value="todos" @selected(($estadoFiltro ?? 'todos') === 'todos')>Todos</option>
                        <option value="pendente" @selected(($estadoFiltro ?? 'todos') === 'pendente')>Pendente</option>
                        <option value="enviado" @selected(($estadoFiltro ?? 'todos') === 'enviado')>Enviado</option>
                        <option value="recusado" @selected(($estadoFiltro ?? 'todos') === 'recusado')>Recusado</option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button class="btn bg-black text-white border-black hover:bg-neutral-800" type="submit">Filtrar</button>
                    <a href="{{ route('cidadao.encomendas.index') }}" class="btn btn-outline">Limpar</a>
                </div>
            </form>
        </div>

        <div class="space-y-4">
            @forelse ($encomendas as $encomenda)
                @php
                    // Totais auxiliares para resumo visual de cada cartão de encomenda.
                    $subtotal = (float) $encomenda->itens->sum('subtotal');
                    $portes = $subtotal < 50 ? 1.99 : 0.0;
                    $quantidadeItens = (int) $encomenda->itens->sum('quantidade');
                    $descontoPercentual = (int) ($encomenda->desconto_percentual ?? 0);
                    $descontoValor = (float) ($encomenda->valor_desconto ?? 0);
                @endphp

                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3">
                        <div>
                            <p class="text-sm text-slate-500">Encomenda #{{ $encomenda->id }}</p>
                            <p class="text-sm text-slate-500">Criada em {{ $encomenda->created_at?->format('d/m/Y H:i') }}</p>
                            <p class="text-sm text-slate-500">Morada: {{ $encomenda->morada_linha_1 }}, {{ $encomenda->codigo_postal }} {{ $encomenda->cidade }} ({{ $encomenda->pais }})</p>
                        </div>

                        <div class="text-left lg:text-right">
                            {{-- Badge de estado com mensagem contextual por fase da encomenda. --}}
                            @if ($encomenda->estado === 'paga')
                                <span class="badge border-emerald-500 text-emerald-700 bg-emerald-50">Paga</span>
                                <p class="text-xs text-slate-500 mt-1">Pago em {{ $encomenda->pago_em?->format('d/m/Y H:i') ?? '-' }}</p>
                            @elseif ($encomenda->estado === 'enviado')
                                <span class="badge border-sky-500 text-sky-700 bg-sky-50">Enviado</span>
                                <p class="text-xs text-slate-500 mt-1">Pagamento validado e envio confirmado</p>
                            @elseif ($encomenda->estado === 'pagamento_recusado')
                                <span class="badge border-rose-500 text-rose-700 bg-rose-50">Pagamento recusado</span>
                            @else
                                <span class="badge border-amber-500 text-amber-700 bg-amber-50">Pendente pagamento</span>
                            @endif
                            <div class="mt-2 space-y-1 text-sm text-slate-600">
                                <div class="flex items-center justify-between gap-4">
                                    <span>Portes</span>
                                    <span class="font-semibold text-slate-900">{{ number_format($portes, 2, ',', '.') }} &euro;</span>
                                </div>
                                @if ($descontoPercentual > 0 && $quantidadeItens >= 2)
                                    <div class="flex items-center justify-between gap-4">
                                        <span class="text-emerald-700">-{{ $descontoPercentual }}% de desconto</span>
                                        <span class="font-semibold text-emerald-700">-{{ number_format($descontoValor, 2, ',', '.') }} &euro;</span>
                                    </div>
                                @endif
                                <div class="flex items-center justify-between gap-4 pt-1 border-t border-slate-100">
                                    <span class="font-medium text-slate-700">Total</span>
                                    <span class="text-xl font-bold text-slate-900">{{ number_format((float) $encomenda->total, 2, ',', '.') }} &euro;</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 flex justify-end gap-2">
                        {{-- Fatura apenas disponível após validação e envio da encomenda. --}}
                        @if ($encomenda->estado === 'enviado')
                            <a href="{{ route('cidadao.encomendas.fatura.pdf', $encomenda) }}" class="btn btn-sm border-transparent bg-transparent text-slate-700 hover:bg-slate-100">
                                Fatura
                            </a>
                        @endif
                        <a href="{{ route('cidadao.encomendas.show', $encomenda) }}" class="btn btn-sm bg-black text-white border-black hover:bg-neutral-800 hover:text-white">
                            Ver detalhe
                        </a>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        {{-- Tabela de itens para conferência de quantidades e preços. --}}
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
                {{-- Estado vazio quando o utilizador ainda não registou compras. --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-500">
                    Ainda não tem encomendas registadas.
                </div>
            @endforelse
        </div>

        @if ($encomendas->hasPages())
            {{-- Controlo de paginação simplificado para navegação sequencial. --}}
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
        @endif
    </div>
</x-app-layout>
