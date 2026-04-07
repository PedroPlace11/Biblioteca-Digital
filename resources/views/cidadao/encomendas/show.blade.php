<x-app-layout>
    <div class="relative overflow-hidden py-8">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-br from-sky-50 via-white to-blue-50"></div>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white/90 shadow-sm backdrop-blur p-6 sm:p-8">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Encomenda</p>
                        <h1 class="mt-2 text-3xl sm:text-4xl font-semibold text-slate-900">#{{ $encomenda->id }}</h1>
                        <p class="mt-2 text-slate-600">Resumo completo da compra e estado de entrega.</p>
                    </div>

                    <div class="flex flex-col items-start sm:items-end gap-3">
                        <a href="{{ route('cidadao.encomendas.index') }}" class="inline-flex items-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                            Voltar
                        </a>

                        @if ($encomenda->estado === 'enviado')
                            <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">Enviado</span>
                        @elseif ($encomenda->estado === 'paga')
                            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Pago</span>
                        @elseif ($encomenda->estado === 'pagamento_recusado')
                            <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">Recusado</span>
                        @else
                            <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Pendente</span>
                        @endif
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Cliente</p>
                        <p class="mt-1 font-semibold text-slate-900 truncate">{{ $encomenda->user?->name ?? '-' }}</p>
                        <p class="text-sm text-slate-600 truncate">{{ $encomenda->user?->email ?? '-' }}</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Data</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $encomenda->created_at?->format('d/m/Y H:i') }}</p>
                        <p class="text-sm text-slate-600">N.o leitor: {{ $encomenda->user?->numero_leitor ?? '-' }}</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Total</p>
                        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format((float) $encomenda->total, 2, ',', '.') }} &euro;</p>
                        @if (in_array($encomenda->estado, ['paga', 'enviado'], true))
                            <p class="text-sm text-slate-600">Pago em {{ $encomenda->pago_em?->format('d/m/Y H:i') ?? '-' }}</p>
                        @endif
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Rastreio</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $encomenda->transportadora ?? 'CTT' }}</p>
                        <p class="text-sm text-slate-600 break-all">{{ $encomenda->numero_rastreio ?? 'Ainda sem código' }}</p>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-6 pt-6">
                        <h2 class="text-xl font-semibold text-slate-900">Itens da encomenda</h2>
                        <p class="mt-1 text-sm text-slate-500">Lista dos livros incluídos nesta compra.</p>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full min-w-[640px] text-sm">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th class="px-6 py-3 text-left font-semibold">Livro</th>
                                    <th class="px-6 py-3 text-left font-semibold">ISBN</th>
                                    <th class="px-6 py-3 text-left font-semibold">Qtd</th>
                                    <th class="px-6 py-3 text-left font-semibold">Preço unitário</th>
                                    <th class="px-6 py-3 text-left font-semibold">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @foreach ($encomenda->itens as $item)
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="px-6 py-4 font-medium text-slate-900">{{ $item->livro_nome }}</td>
                                        <td class="px-6 py-4">{{ $item->livro_isbn ?? '-' }}</td>
                                        <td class="px-6 py-4">{{ $item->quantidade }}</td>
                                        <td class="px-6 py-4">{{ number_format((float) $item->preco_unitario, 2, ',', '.') }} &euro;</td>
                                        <td class="px-6 py-4 font-semibold">{{ number_format((float) $item->subtotal, 2, ',', '.') }} &euro;</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <aside class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Entrega</h2>
                    <p class="mt-1 text-sm text-slate-500">Dados do destinatário e morada.</p>

                    <div class="mt-5 space-y-3 text-slate-700">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-500">Destinatário</p>
                            <p class="font-semibold text-slate-900">{{ $encomenda->nome_destinatario }}</p>
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-500">Contacto</p>
                            <p>{{ $encomenda->telemovel_destinatario }}</p>
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-500">Morada</p>
                            <p>{{ $encomenda->morada_linha_1 }}</p>
                            @if ($encomenda->morada_linha_2)
                                <p>{{ $encomenda->morada_linha_2 }}</p>
                            @endif
                            <p>{{ $encomenda->codigo_postal }} {{ $encomenda->cidade }} ({{ $encomenda->pais }})</p>
                        </div>
                    </div>
                </aside>
            </section>
        </div>
    </div>
</x-app-layout>
